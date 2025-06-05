<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Service for rendering views in menu items.
 */
class MenuLinkViewRenderer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new MenuLinkViewRenderer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Renders views in menu items.
   *
   * @param array $items
   *   The menu items.
   *
   * @return array
   *   The processed menu items.
   */
  public function renderViewsInMenuItems(array $items) {
    foreach ($items as $key => $item) {
      // Check if this is a view menu link by title pattern.
      if (isset($item['title']) && strpos($item['title'], '[View]') !== FALSE) {
        $this->processViewMenuItem($items[$key]);
      }

      // Process child items recursively.
      if (!empty($item['below'])) {
        $items[$key]['below'] = $this->renderViewsInMenuItems($item['below']);
      }
    }

    return $items;
  }

  /**
   * Process a single menu item that contains a view reference.
   *
   * @param array &$item
   *   The menu item to process.
   */
  protected function processViewMenuItem(array &$item) {
    // Extract menu link entity from the menu item.
    $menu_link = $this->findMenuLinkContentEntity($item);

    if (!$menu_link) {
      return;
    }

    // Extract options with view information.
    $link = $menu_link->get('link')->first();
    if (!$link) {
      return;
    }

    $options = $link->options ?? [];
    if (empty($options['menu_link_view'])) {
      return;
    }

    $view_info = $options['menu_link_view'];

    // Check if we have view information.
    if (empty($view_info['view_id']) || empty($view_info['display_id'])) {
      return;
    }

    // Load and render the view.
    $view_storage = $this->entityTypeManager->getStorage('view');
    $view = $view_storage->load($view_info['view_id']);

    if ($view) {
      // Build renderable view.
      $view_output = $view->getExecutable()
        ->buildRenderable($view_info['display_id']);

      // Alter view rendering.
      $view_output['#theme_wrappers'] = ['menu_link_view'];
      $view_output['#title'] = str_replace(' [View]', '', $item['title']);

      // Add special class.
      $item['attributes']['class'][] = 'menu-item--view';

      // Clean up title without [View] suffix.
      $item['title'] = str_replace(' [View]', '', $item['title']);

      // Add view content.
      $item['view_content'] = $view_output;
    }
  }

  /**
   * Find the menu link content entity associated with a menu item.
   *
   * @param array $item
   *   The menu item.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
   *   The menu link content entity or NULL if not found.
   */
  protected function findMenuLinkContentEntity(array $item) {
    // Check if we have an original link object with UUID.
    if (isset($item['original_link']) && method_exists($item['original_link'], 'getPluginId')) {
      $plugin_id = $item['original_link']->getPluginId();
      if (strpos($plugin_id, 'menu_link_content:') === 0) {
        $uuid = substr($plugin_id, strlen('menu_link_content:'));
        $menu_links = $this->entityTypeManager->getStorage('menu_link_content')
          ->loadByProperties(['uuid' => $uuid]);

        if (!empty($menu_links)) {
          return reset($menu_links);
        }
      }
    }

    // Fallback: try to find by title.
    if (isset($item['title'])) {
      $menu_links = $this->entityTypeManager->getStorage('menu_link_content')
        ->loadByProperties(['title' => $item['title']]);

      if (!empty($menu_links)) {
        return reset($menu_links);
      }
    }

    return NULL;
  }

}
