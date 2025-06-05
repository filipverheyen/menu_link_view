<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles menu link view operations and actions.
 */
class MenuLinkViewMenuHandler {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MenuLinkViewMenuHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets all view menu item IDs.
   *
   * @return array
   *   An array of menu link content plugin IDs that are view menu items.
   */
  public function getViewMenuItemIds() {
    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $query = $storage->getQuery()
      ->condition('title', '%[View]', 'LIKE')
      ->accessCheck(FALSE);
    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $menu_links = $storage->loadMultiple($ids);
    $view_menu_items = [];

    foreach ($menu_links as $menu_link) {
      $options = $menu_link->get('link')->options ?? [];
      if (!empty($options['menu_link_view'])) {
        $plugin_id = 'menu_link_content:' . $menu_link->uuid();
        $view_menu_items[] = $plugin_id;
      }
    }

    return $view_menu_items;
  }

  /**
   * Checks if a menu link is a view menu item.
   *
   * @param string $plugin_id
   *   The plugin ID of the menu link.
   *
   * @return bool
   *   TRUE if the menu link is a view menu item, FALSE otherwise.
   */
  public function isViewMenuItem($plugin_id) {
    if (strpos($plugin_id, 'menu_link_content:') === 0) {
      $uuid = substr($plugin_id, strlen('menu_link_content:'));

      $storage = $this->entityTypeManager->getStorage('menu_link_content');
      $entities = $storage->loadByProperties(['uuid' => $uuid]);
      $menu_link = reset($entities);

      if ($menu_link) {
        $options = $menu_link->get('link')->options ?? [];
        if (!empty($options['menu_link_view'])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
