<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\views\ViewExecutableFactory;

/**
 * Service for expanding menu link views into individual menu items.
 */
class MenuLinkViewExpander {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a MenuLinkViewExpander object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\ViewExecutableFactory $view_executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ViewExecutableFactory $view_executable_factory,
    CacheBackendInterface $cache,
    RendererInterface $renderer,
    AdminContext $admin_context
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->viewExecutableFactory = $view_executable_factory;
    $this->cache = $cache;
    $this->renderer = $renderer;
    $this->adminContext = $admin_context;
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
    $view_storage = $this->entityTypeManager->getStorage('view');
    $view = $view_storage->load($view_id);

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
    $this->cache->set($cid, $entities, CacheBackendInterface::CACHE_PERMANENT, [
      'menu_link_view',
      'view:' . $view_id,
    ]);

    return $entities;
  }

  /**
   * Expands menu link views in a menu tree.
   *
   * @param array $items
   *   The menu items to expand.
   */
  public function expandMenuItems(array &$items) {
    // Skip if we're in the admin UI.
    if ($this->adminContext->isAdminRoute()) {
      return;
    }

    foreach ($items as $key => &$item) {
      // Check if this is a menu link view item.
      if (isset($item['original_link']) && $item['original_link']->getProvider() === 'menu_link_view') {
        // Get metadata from the original link.
        $metadata = $item['original_link']->getMetaData();

        if (!empty($metadata['view_id']) && !empty($metadata['display_id'])) {
          // Get entities from the view.
          $entities = $this->getEntitiesFromView($metadata['view_id'], $metadata['display_id']);

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
                'class' => ['menu-item', 'menu-item-view-result'],
              ],
              'weight' => $weight + ($delta / 1000),
            ];

            $new_items[] = $new_item;
          }

          // Replace the original item with the new items.
          array_splice($items, $key, 1, $new_items);
        }
      }

      // Process children recursively.
      if (!empty($item['below'])) {
        $this->expandMenuItems($item['below']);
      }
    }
  }

}
