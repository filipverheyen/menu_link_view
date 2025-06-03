<?php

namespace Drupal\menu_link_view\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\views\ViewsData;

/**
 * Service for resolving menu links from views.
 */
class MenuLinkViewResolver {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a MenuLinkViewResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data service.
   * @param \Drupal\views\ViewExecutableFactory $view_executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ViewsData $views_data,
    ViewExecutableFactory $view_executable_factory,
    RendererInterface $renderer,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->viewsData = $views_data;
    $this->viewExecutableFactory = $view_executable_factory;
    $this->renderer = $renderer;
    $this->cache = $cache;
  }

  /**
   * Gets entities from a view.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function getEntitiesFromView($view_id, $display_id) {
    $cid = 'menu_link_view:' . $view_id . ':' . $display_id;

    // Check if we have this in cache.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $entities = [];

    // Load and execute the view.
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if (!$view) {
      return $entities;
    }

    $view_executable = $this->viewExecutableFactory->get($view);
    if ($view_executable && $view_executable->access($display_id)) {
      $view_executable->setDisplay($display_id);

      // Make sure this is an entity reference display.
      if ($view_executable->getDisplay()->getPluginId() === 'entity_reference') {
        $view_executable->execute($display_id);

        // Get the entity type from the view.
        $entity_type = $view_executable->getBaseEntityType()->id();

        // Get the result IDs.
        $result = $view_executable->result;
        $entity_ids = [];

        foreach ($result as $row) {
          if (isset($row->_entity) && $row->_entity) {
            $entities[] = $row->_entity;
          }
          elseif (isset($row->{$view_executable->base_field}) && !empty($row->{$view_executable->base_field})) {
            $entity_ids[] = $row->{$view_executable->base_field};
          }
        }

        // If we have entity IDs without loaded entities, load them.
        if (!empty($entity_ids) && empty($entities)) {
          $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);
        }
      }
    }

    // Cache the result.
    $this->cache->set($cid, $entities, CacheBackendInterface::CACHE_PERMANENT, ['menu_link_view', 'entity:' . $view_id]);

    return $entities;
  }

  /**
   * Alters the discovered menu links.
   *
   * @param array $links
   *   The menu links being discovered.
   */
  public function alterMenuLinks(array &$links) {
    // Find all menu link content items with view references.
    $query = $this->entityTypeManager->getStorage('menu_link_content')->getQuery()
      ->condition('is_view_reference', TRUE)
      ->sort('weight');
    $ids = $query->accessCheck(TRUE)->execute();

    if (!empty($ids)) {
      $menu_links = $this->entityTypeManager->getStorage('menu_link_content')->loadMultiple($ids);

      foreach ($menu_links as $id => $menu_link) {
        // Only alter links in the admin UI that should display a placeholder.
        if (\Drupal::service('router.admin_context')->isAdminRoute()) {
          $plugin_id = 'menu_link_content:' . $menu_link->uuid();
          if (isset($links[$plugin_id])) {
            // Keep the original link but mark it as a placeholder.
            $links[$plugin_id]['title'] = $menu_link->label() . ' [View Reference]';
            $links[$plugin_id]['options']['attributes']['class'][] = 'menu-link-view-placeholder';
            // Add special metadata to identify it as a view reference.
            $links[$plugin_id]['metadata']['view_reference'] = TRUE;
          }
        }
      }
    }
  }

  /**
   * Preprocesses menu items to expand view references.
   *
   * @param array $items
   *   The menu items to preprocess.
   */
  public function preprocessMenuItems(array &$items) {
    // Skip if we're in the admin UI.
    if (\Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    foreach ($items as $key => &$item) {
      // Check if this is a view reference menu item.
      if (isset($item['original_link']) && $item['original_link']->getProvider() === 'menu_link_content') {
        $uuid = $item['original_link']->getDerivativeId();
        $menu_link = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
        $menu_link = reset($menu_link);

        if ($menu_link && $menu_link->hasField('is_view_reference') && $menu_link->get('is_view_reference')->value) {
          $view_reference = $menu_link->get('view_reference')->value;
          if (!empty($view_reference)) {
            [$view_id, $display_id] = explode(':', $view_reference);

            // Get entities from the view.
            $entities = $this->getEntitiesFromView($view_id, $display_id);

            // Generate menu items for each entity.
            $new_items = [];
            $weight = $item['original_link']->getWeight();

            foreach ($entities as $delta => $entity) {
              $new_item = [
                'title' => $entity->label(),
                'url' => $entity->toUrl(),
                'below' => [],
                'original_link' => $item['original_link'],
                'is_expanded' => FALSE,
                'is_collapsed' => FALSE,
                'in_active_trail' => FALSE,
                'attributes' => [
                  'class' => ['menu-item'],
                ],
                'weight' => $weight + ($delta / 1000),
              ];

              // Support for menu_item_extras module.
              if (isset($item['entity']) && \Drupal::moduleHandler()->moduleExists('menu_item_extras')) {
                $new_item['entity'] = $item['entity'];
              }

              $new_items[] = $new_item;
            }

            // Replace the original item with the new items.
            array_splice($items, $key, 1, $new_items);
          }
        }
      }

      // Process children recursively.
      if (!empty($item['below'])) {
        $this->preprocessMenuItems($item['below']);
      }
    }
  }

}
