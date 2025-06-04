<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\views\Views;

/**
 * Menu link view expander service.
 */
class MenuLinkViewExpander {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Constructs a new MenuLinkViewExpander.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkTreeInterface $menu_link_tree) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkTree = $menu_link_tree;
  }

  /**
   * Expands menu links based on view results.
   *
   * @param array $tree
   *   The menu tree.
   *
   * @return array
   *   The modified menu tree.
   */
  public function expandTree(array $tree) {
    // Look for menu link view items in the tree and expand them.
    foreach ($tree as $key => $element) {
      // Check if this is one of our menu link view elements.
      if (isset($element->link) && $element->link->getProvider() === 'menu_link_view') {
        $plugin_id = $element->link->getPluginId();

        // Extract entity ID from plugin ID.
        $entity_id = str_replace('menu_link_view:', '', $plugin_id);
        $menu_link_view = $this->entityTypeManager->getStorage('menu_link_view')->load($entity_id);

        if ($menu_link_view) {
          $view_id = $menu_link_view->getViewId();
          $display_id = $menu_link_view->getDisplayId();

          // Load and execute the view.
          $view = Views::getView($view_id);
          if ($view && $view->access($display_id)) {
            $view->setDisplay($display_id);
            $view->execute();

            // Get the view results.
            $results = [];
            if (!empty($view->result)) {
              // Get entity type from view base table.
              $entity_type = $view->baseEntityType->id();
              $entity_storage = $this->entityTypeManager->getStorage($entity_type);

              // Get entity IDs from view result.
              $entity_ids = [];
              foreach ($view->result as $row) {
                $entity_ids[] = $row->_entity->id();
              }

              // Load the entities.
              if (!empty($entity_ids)) {
                $entities = $entity_storage->loadMultiple($entity_ids);
                foreach ($entities as $entity) {
                  // Create a virtual menu item for each entity.
                  $results[] = [
                    'title' => $entity->label(),
                    'url' => $entity->toUrl(),
                    'weight' => $element->link->getWeight(),
                  ];
                }
              }
            }

            // If we have results, add them as children of the view menu link.
            if (!empty($results)) {
              // Create a sub-tree of items from the results.
              $subtree = [];
              foreach ($results as $index => $item) {
                // IMPORTANT: Make sure the index is an integer.
                // Cast to integer to fix the TypeError.
                $position = (int) $index;

                $subtree[$position] = new \stdClass();
                $subtree[$position]->link = new ViewResultMenuLink(
                  $item['title'],
                  $item['url'],
                  $element->link->getPluginId() . ':' . $position,
                  $element->link->getWeight() + ($position / 1000)
                );
                $subtree[$position]->subtree = [];
                $subtree[$position]->depth = $element->depth + 1;
                $subtree[$position]->inActiveTrail = FALSE;
                $subtree[$position]->access = TRUE;
                $subtree[$position]->hasChildren = FALSE;
                $subtree[$position]->expanded = FALSE;
              }

              // Insert the subtree into the tree.
              if (!empty($subtree)) {
                // Make sure $key is an integer for array_splice.
                $current_key = is_numeric($key) ? (int) $key : 0;

                // Remove the original menu link view element from the tree.
                array_splice($tree, $current_key, 1);

                // Insert the subtree items at the same position.
                foreach (array_reverse($subtree) as $item) {
                  array_splice($tree, $current_key, 0, [$item]);
                }
              }
            }
          }
        }
      }

      // Process subtrees recursively.
      if (!empty($element->subtree)) {
        $element->subtree = $this->expandTree($element->subtree);
      }
    }

    return $tree;
  }

}
