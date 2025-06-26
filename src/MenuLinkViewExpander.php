<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MenuLinkViewExpander.
 *
 * Expands menu items that reference views by replacing them with
 * menu items generated from the view results.
 */
class MenuLinkViewExpander {
  use StringTranslationTrait;

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
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var string
   */
  protected $currentPath;

  /**
   * MenuLinkViewExpander constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MenuLinkManagerInterface $menu_link_manager,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache_backend,
    LoggerChannelFactoryInterface $logger_factory,
    PathMatcherInterface $path_matcher,
    RequestStack $request_stack
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->languageManager = $language_manager;
    $this->cacheBackend = $cache_backend;
    $this->loggerFactory = $logger_factory;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;

    // Get the current path.
    $request = $this->requestStack->getCurrentRequest();
    $this->currentPath = $request ? $request->getPathInfo() : '';
  }

  /**
   * Expand view menu items.
   *
   * @param array $menu_items
   *   The menu items to expand.
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   The expanded menu items.
   */
  public function expandTreeItems(array $menu_items, string $menu_name) {
    $expanded_items = [];

    foreach ($menu_items as $key => $item) {
      // Check if this is a view menu item.
      if ($this->isMenuLinkViewItem($item)) {
        // Get the view information.
        $view_info = $this->getViewInfoFromItem($item);

        if (!empty($view_info['view_id'])) {
          // Get the view results - no caching as requested.
          $view_results = $this->getViewResults($view_info['view_id'], $view_info['display_id'] ?? 'default');

          if (!empty($view_results)) {
            // Create menu items from the view results.
            $view_menu_items = $this->createMenuItemsFromResults($view_results, $item, $menu_name);

            // Add the view menu items to the expanded items.
            foreach ($view_menu_items as $view_key => $view_item) {
              $expanded_items[$view_key] = $view_item;
            }

            // Skip the original item.
            continue;
          }
        }
      }

      // Keep the original menu item.
      $expanded_items[$key] = $item;

      // Process children if any.
      if (!empty($item['below'])) {
        $expanded_items[$key]['below'] = $this->expandTreeItems($item['below'], $menu_name);
      }
    }

    return $expanded_items;
  }

  /**
   * Check if an item is a view menu item.
   */
  protected function isMenuLinkViewItem(array $item) {
    if (empty($item['original_link'])) {
      return FALSE;
    }

    $link = $item['original_link'];

    // Check if this is a content menu link with view metadata.
    if ($link->getProvider() == 'menu_link_content') {
      $options = $link->getOptions();

      // Check if our module's metadata is present.
      if (!empty($options['menu_link_view'])) {
        return TRUE;
      }

      // Try to load the original entity to check.
      $metadata = $link->getMetaData();
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
   * Get view information from a menu item.
   */
  protected function getViewInfoFromItem(array $item) {
    $link = $item['original_link'] ?? NULL;
    if (!$link) {
      return [];
    }

    $options = $link->getOptions();
    $view_info = $options['menu_link_view'] ?? [];

    if (empty($view_info) && $link->getProvider() == 'menu_link_content') {
      // Try to load the original entity to get the view info.
      $metadata = $link->getMetaData();
      if (!empty($metadata['entity_id'])) {
        try {
          $menu_link_content = $this->entityTypeManager
            ->getStorage('menu_link_content')
            ->load($metadata['entity_id']);

          if ($menu_link_content) {
            $options = $menu_link_content->link->options ?? [];
            $view_info = $options['menu_link_view'] ?? [];
          }
        }
        catch (\Exception $e) {
          // Unable to load the entity.
          return [];
        }
      }
    }

    return $view_info;
  }

  /**
   * Get results from a view.
   */
  protected function getViewResults($view_id, $display_id = 'default') {
    $results = [];

    // Load and execute the view - no caching for immediate updates.
    $view = Views::getView($view_id);
    if (!$view || !$view->access($display_id)) {
      return [];
    }

    // Make sure this is an entity reference display.
    $view->setDisplay($display_id);
    if ($view->getDisplay()->getPluginId() !== 'entity_reference') {
      return [];
    }

    // Clear any existing arguments and execute.
    $view->setArguments([]);
    $view->preExecute();
    $view->execute();

    // Get the results - only entity references.
    if (!empty($view->result)) {
      foreach ($view->result as $row_index => $row) {
        if (!empty($row->_entity)) {
          $results[] = [
            'entity' => $row->_entity,
            'index' => $row_index,
          ];
        }
      }
    }

    return $results;
  }

  /**
   * Create menu items from view results.
   */
  protected function createMenuItemsFromResults(array $results, array $parent_item, string $menu_name) {
    $menu_items = [];
    $weight = 0;

    // Copy attributes from parent item for consistent rendering.
    $parent_attributes = $parent_item['attributes'] ?? ['class' => []];

    foreach ($results as $result) {
      $entity = $result['entity'];
      $index = $result['index'];

      if (!$entity) {
        continue;
      }

      try {
        // Generate URL and title for the entity.
        $url = $entity->toUrl();
        $title = $entity->label();

        if (empty($title)) {
          continue;
        }

        // Create a unique key for this menu item.
        $key = 'menu_link_view_' . md5($parent_item['original_link']->getPluginId() . '_' . $index);

        // Copy the parent item structure.
        $menu_item = $parent_item;

        // Update with entity-specific data.
        $menu_item['title'] = $title;
        $menu_item['url'] = $url;
        $menu_item['below'] = [];

        // Create attributes for this item.
        $menu_item['attributes'] = $parent_attributes;
        if (!isset($menu_item['attributes']['class'])) {
          $menu_item['attributes']['class'] = [];
        }

        // Add our special class while preserving all others.
        $menu_item['attributes']['class'][] = 'menu-item--view-generated';

        // Check if this item is in the active trail.
        $is_active = $this->isUrlInActiveTrail($url);
        $menu_item['in_active_trail'] = $is_active;

        if ($is_active) {
          $menu_item['attributes']['class'][] = 'menu-item--active-trail';
        }

        // Replace the original link with our synthetic link.
        $menu_item['original_link'] = new SyntheticMenuLink([
          'title' => $title,
          'route_name' => $url->getRouteName(),
          'route_parameters' => $url->getRouteParameters(),
          'url' => $url->toString(),
          'menu_name' => $menu_name,
          'parent' => $parent_item['original_link']->getPluginId(),
          'weight' => $weight,
          'expanded' => FALSE,
          'provider' => 'menu_link_view',
          'metadata' => [
            'entity_id' => $entity->id(),
            'entity_type' => $entity->getEntityTypeId(),
            'view_row_index' => $index,
            'parent_link_id' => $parent_item['original_link']->getPluginId(),
          ],
        ]);

        $menu_items[$key] = $menu_item;
        $weight++;
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('menu_link_view')->error('Error creating menu item from entity: @message', ['@message' => $e->getMessage()]);
      }
    }

    return $menu_items;
  }

  /**
   * Check if a URL is in the active trail.
   */
  protected function isUrlInActiveTrail(Url $url) {
    if ($url->isRouted()) {
      try {
        $url_path = $url->toString();
        $current_path = $this->currentPath;

        // Direct match.
        if ($url_path == $current_path) {
          return TRUE;
        }

        // Check if the current path starts with this URL (child path)
        if (strpos($current_path, $url_path) === 0) {
          return TRUE;
        }
      }
      catch (\Exception $e) {
        // If we can't generate the URL, it's not active.
        return FALSE;
      }
    }

    return FALSE;
  }

}
