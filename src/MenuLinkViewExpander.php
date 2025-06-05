<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\views\Views;

/**
 * Class MenuLinkViewExpander.
 *
 * Expands menu items that reference views by replacing them with
 * menu items generated from the view results.
 */
class MenuLinkViewExpander {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * MenuLinkViewExpander constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MenuLinkManagerInterface $menu_link_manager,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache_backend
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->languageManager = $language_manager;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Expand view menu items.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to expand.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The expanded menu link tree.
   */
  public function expandViewMenuItems(array $tree) {
    $expanded_tree = [];

    foreach ($tree as $key => $element) {
      // Safety check - make sure we have a valid element with a link.
      if (!($element instanceof MenuLinkTreeElement) || !$element->link) {
        $expanded_tree[$key] = $element;
        continue;
      }

      $link = $element->link;

      // Now we know $link is not null, so it's safe to pass to isViewMenuItem.
      $is_view_menu_item = $this->isViewMenuItem($link);

      if ($is_view_menu_item) {
        // Get view items and replace this menu item with them.
        $view_items = $this->getViewMenuItems($link);

        if (!empty($view_items)) {
          // Add the generated items to the tree.
          foreach ($view_items as $view_item_key => $view_item) {
            $expanded_tree[$view_item_key] = $view_item;
          }
        }
        else {
          // If no results, keep the original item as a fallback.
          $expanded_tree[$key] = $element;
        }
      }
      else {
        // For normal menu items, process their subtrees if any.
        $expanded_tree[$key] = $element;

        if ($element->hasChildren) {
          $expanded_subtree = $this->expandViewMenuItems($element->subtree);
          $expanded_tree[$key]->subtree = $expanded_subtree;
        }
      }
    }

    return $expanded_tree;
  }

  /**
   * Check if a menu link is a view menu item.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link to check.
   *
   * @return bool
   *   TRUE if this is a view menu item, FALSE otherwise.
   */
  protected function isViewMenuItem(MenuLinkInterface $link) {
    // Check if this is one of our menu link view derivatives.
    if ($link->getProvider() == 'menu_link_view') {
      return TRUE;
    }

    // Or check if this is a content menu link with view metadata.
    if ($link->getProvider() == 'menu_link_content') {
      $metadata = $link->getMetaData();
      $options = $link->getOptions();

      // Check if our module's metadata is present.
      if (!empty($options['menu_link_view'])) {
        return TRUE;
      }

      // Try to load the original entity to check.
      if (!empty($metadata['entity_id'])) {
        try {
          $menu_link_content = $this->entityTypeManager
            ->getStorage('menu_link_content')
            ->load($metadata['entity_id']);

          if ($menu_link_content) {
            $options = $menu_link_content->link->options ?? [];
            if (!empty($options['menu_link_view'])) {
              return TRUE;
            }
          }
        }
        catch (\Exception $e) {
          // If we can't load the entity, assume it's not a view menu item.
          return FALSE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get menu items generated from a view.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link that references a view.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The menu items generated from the view.
   */
  protected function getViewMenuItems(MenuLinkInterface $link) {
    $menu_items = [];
    $metadata = $link->getMetaData();
    $options = $link->getOptions();
    $view_config = $options['menu_link_view'] ?? [];

    // Get view info from the menu link.
    $view_id = $view_config['view_id'] ?? '';
    $display_id = $view_config['display_id'] ?? 'default';

    if (empty($view_id)) {
      return [];
    }

    // Try to get cached results first.
    $cache_key = 'menu_link_view:' . $link->getPluginId();
    $cached_data = $this->cacheBackend->get($cache_key);

    if ($cached_data) {
      return $cached_data->data;
    }

    // Load and execute the view.
    $view = Views::getView($view_id);
    if (!$view || !$view->access($display_id)) {
      return [];
    }

    $view->setDisplay($display_id);
    $view->execute();

    // Get the results and create menu items.
    if (!empty($view->result)) {
      $weight = 0;

      foreach ($view->result as $row_index => $row) {
        // Extract entity data from the result.
        if (!empty($row->_entity)) {
          $entity = $row->_entity;

          // Create menu item from the entity.
          $menu_item = $this->createEntityMenuItem(
            $entity,
            $link,
            $row_index,
            $weight
          );

          if ($menu_item) {
            $key = 'menu_link_view_' . $link->getPluginId() . '_' . $row_index;
            $menu_items[$key] = $menu_item;
            $weight++;
          }
        }
      }

      // Cache the results.
      $cache_tags = ['menu:' . $link->getMenuName()];
      if ($view->storage) {
        $cache_tags = array_merge($cache_tags, $view->storage->getCacheTags());
      }

      $this->cacheBackend->set(
        $cache_key,
        $menu_items,
        CacheBackendInterface::CACHE_PERMANENT,
        $cache_tags
      );
    }

    return $menu_items;
  }

  /**
   * Create a menu item from an entity.
   *
   * @param object $entity
   *   The entity to create a menu item for.
   * @param \Drupal\Core\Menu\MenuLinkInterface $parent_link
   *   The parent menu link.
   * @param int $row_index
   *   The row index in the view results.
   * @param int $weight
   *   The weight of the menu item.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement|null
   *   The menu link tree element, or null if it could not be created.
   */
  protected function createEntityMenuItem($entity, MenuLinkInterface $parent_link, $row_index, $weight) {
    try {
      // Generate a URL for the entity.
      $url = $entity->toUrl();

      // Create a dynamic menu link definition.
      $menu_link_definition = [
        'title' => $entity->label(),
        'route_name' => $url->getRouteName(),
        'route_parameters' => $url->getRouteParameters(),
        'url' => $url->toString(),
        'menu_name' => $parent_link->getMenuName(),
        'parent' => $parent_link->getPluginId(),
        'weight' => $weight,
        'expanded' => FALSE,
        'provider' => 'menu_link_view',
        'metadata' => [
          'entity_id' => $entity->id(),
          'entity_type' => $entity->getEntityTypeId(),
          'view_row_index' => $row_index,
          'parent_link_id' => $parent_link->getPluginId(),
        ],
      ];

      // Create a synthetic menu link.
      $synthetic_menu_link = new SyntheticMenuLink($menu_link_definition);

      // Create a tree element for this menu link.
      $tree_element = new MenuLinkTreeElement(
        $synthetic_menu_link,
        FALSE,
        0,
        FALSE,
        []
      );

      return $tree_element;
    }
    catch (\Exception $e) {
      // If we can't create a menu item from this entity, return null.
      return NULL;
    }
  }

}
