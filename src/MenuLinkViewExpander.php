<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Views;

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
   * Debug mode flag.
   *
   * @var bool
   */
  protected $debugMode = TRUE;

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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MenuLinkManagerInterface $menu_link_manager,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache_backend,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
    $this->languageManager = $language_manager;
    $this->cacheBackend = $cache_backend;
    $this->loggerFactory = $logger_factory;
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
      // Debug information.
      if ($this->debugMode) {
        $this->log('Processing menu item: @key', ['@key' => $key]);
      }

      // Check if this is a view menu item.
      if ($this->isMenuLinkViewItem($item)) {
        // This is a view menu item, get its config.
        $view_info = $this->getViewInfoFromItem($item);

        if (!empty($view_info) && !empty($view_info['view_id'])) {
          // Get the view results.
          $view_results = $this->getViewResults($view_info['view_id'], $view_info['display_id'] ?? 'default');

          if (!empty($view_results)) {
            // Create menu items from the view results.
            $view_menu_items = $this->createMenuItemsFromResults($view_results, $item, $menu_name);

            // Add the view menu items to the expanded items.
            $expanded_items = array_merge($expanded_items, $view_menu_items);

            // Skip the original item.
            continue;
          }
        }
      }

      // If we get here, keep the original menu item.
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
   *
   * @param array $item
   *   The menu item to check.
   *
   * @return bool
   *   TRUE if this is a view menu item, FALSE otherwise.
   */
  protected function isMenuLinkViewItem(array $item) {
    if (empty($item['original_link'])) {
      return FALSE;
    }

    $link = $item['original_link'];

    // Check if this is one of our menu link view derivatives.
    if ($link->getProvider() == 'menu_link_view') {
      if ($this->debugMode) {
        $this->log('Found view menu item by provider: @provider', ['@provider' => $link->getProvider()]);
      }
      return TRUE;
    }

    // Or check if this is a content menu link with view metadata.
    if ($link->getProvider() == 'menu_link_content') {
      $options = $link->getOptions();

      // Check if our module's metadata is present.
      if (!empty($options['menu_link_view'])) {
        if ($this->debugMode) {
          $this->log('Found view menu item by options: @options', ['@options' => print_r($options, TRUE)]);
        }
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
              if ($this->debugMode) {
                $this->log('Found view menu item by entity options: @options', ['@options' => print_r($options, TRUE)]);
              }
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
   *
   * @param array $item
   *   The menu item.
   *
   * @return array
   *   The view information.
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

    if ($this->debugMode && !empty($view_info)) {
      $this->log('View info: @info', ['@info' => print_r($view_info, TRUE)]);
    }

    return $view_info;
  }

  /**
   * Get results from a view.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   *
   * @return array
   *   The view results.
   */
  protected function getViewResults($view_id, $display_id = 'default') {
    $results = [];

    // Load and execute the view.
    $view = Views::getView($view_id);
    if (!$view || !$view->access($display_id)) {
      if ($this->debugMode) {
        $this->log('Could not load view @view_id:@display_id or access denied', [
          '@view_id' => $view_id,
          '@display_id' => $display_id,
        ]);
      }
      return [];
    }

    // Clear any existing arguments.
    $view->setArguments([]);
    $view->setDisplay($display_id);
    $view->preExecute();
    $view->execute();

    // Get the results.
    if (!empty($view->result)) {
      foreach ($view->result as $row_index => $row) {
        // If this is an entity reference field.
        if (!empty($row->_entity)) {
          $results[] = [
            'entity' => $row->_entity,
            'index' => $row_index,
          ];
        }
      }
    }

    if ($this->debugMode) {
      $this->log('View @view_id:@display_id returned @count results', [
        '@view_id' => $view_id,
        '@display_id' => $display_id,
        '@count' => count($results),
      ]);
    }

    return $results;
  }

  /**
   * Create menu items from view results.
   *
   * @param array $results
   *   The view results.
   * @param array $parent_item
   *   The parent menu item.
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   The menu items.
   */
  protected function createMenuItemsFromResults(array $results, array $parent_item, string $menu_name) {
    $menu_items = [];
    $weight = 0;

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

        // Build the menu item.
        $menu_items[$key] = [
          'title' => $title,
          'url' => $url,
          'below' => [],
          'original_link' => new SyntheticMenuLink([
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
          ]),
          'in_active_trail' => $parent_item['in_active_trail'] ?? FALSE,
          'attributes' => [
            'class' => [
              'menu-item',
              'menu-link-view-generated',
            ],
          ],
        ];

        $weight++;
      }
      catch (\Exception $e) {
        if ($this->debugMode) {
          $this->log('Error creating menu item from entity: @message', ['@message' => $e->getMessage()]);
        }
      }
    }

    if ($this->debugMode) {
      $this->log('Created @count menu items from view results', ['@count' => count($menu_items)]);
    }

    return $menu_items;
  }

  /**
   * Log a message if debug mode is on.
   *
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The context for the message.
   */
  protected function log($message, array $context = []) {
    $this->loggerFactory->get('menu_link_view')->notice($message, $context);
  }

}
