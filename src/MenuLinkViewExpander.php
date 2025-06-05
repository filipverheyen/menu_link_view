<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a service for expanding menu items with views.
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new MenuLinkViewExpander.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->renderer = $renderer;
  }

  /**
   * Expands menu items with view content.
   *
   * @param array $items
   *   The menu items to expand.
   *
   * @return array
   *   The expanded menu items.
   */
  public function expandTree(array $items) {
    foreach ($items as $key => $item) {
      // Check if this is a view menu link by title pattern.
      if (strpos($item['title'], '[View]') !== FALSE) {
        $this->expandMenuItem($item);
      }

      // Process child items.
      if (!empty($item['below'])) {
        $items[$key]['below'] = $this->expandTree($item['below']);
      }
    }

    return $items;
  }

  /**
   * Expands a single menu item with view content.
   *
   * @param array $item
   *   The menu item to expand.
   */
  protected function expandMenuItem(array &$item) {
    // Find the menu link content entity.
    $menu_link_content = $this->findMenuLinkContentByUrl($item);

    if (!$menu_link_content) {
      return;
    }

    // Extract metadata.
    $metadata = $menu_link_content->get('metadata')->getValue();
    if (empty($metadata[0])) {
      return;
    }

    $metadata = $metadata[0];

    // Check if we have view information.
    if (empty($metadata['view_id']) || empty($metadata['display_id'])) {
      return;
    }

    // Load and render the view.
    $view_storage = $this->entityTypeManager->getStorage('view');
    $view = $view_storage->load($metadata['view_id']);

    if ($view) {
      $view_output = $view->getExecutable()
        ->buildRenderable($metadata['display_id']);

      // Remove the default wrappers from the view.
      $view_output['#theme_wrappers'] = [];

      // Custom class.
      $item['attributes']['class'][] = 'menu-item--view';

      // Replace link content with view content.
      $item['content_below'] = $view_output;

      // Clean up title.
      $item['title'] = str_replace(' [View]', '', $item['title']);
    }
  }

  /**
   * Finds a menu link content entity by URL.
   *
   * @param array $item
   *   The menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
   *   The menu link content entity, or NULL if not found.
   */
  protected function findMenuLinkContentByUrl(array $item) {
    $menu_link_content_storage = $this->entityTypeManager->getStorage('menu_link_content');

    // Try to find by title first.
    $menu_links = $menu_link_content_storage->loadByProperties([
      'title' => $item['title'],
    ]);

    if (!empty($menu_links)) {
      return reset($menu_links);
    }

    // If we couldn't find by title, try by plugin ID if available.
    if (isset($item['original_link']) && method_exists($item['original_link'], 'getPluginId')) {
      $plugin_id = $item['original_link']->getPluginId();
      if (strpos($plugin_id, 'menu_link_content:') === 0) {
        $uuid = substr($plugin_id, strlen('menu_link_content:'));
        $menu_links = $menu_link_content_storage->loadByProperties(['uuid' => $uuid]);
        if (!empty($menu_links)) {
          return reset($menu_links);
        }
      }
    }

    return NULL;
  }

}
