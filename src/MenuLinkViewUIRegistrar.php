<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Service to register menu links with the UI.
 */
class MenuLinkViewUIRegistrar implements EventSubscriberInterface {

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
   * Constructs a new MenuLinkViewUIRegistrar.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 50];
    return $events;
  }

  /**
   * Reacts to the kernel request event.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $route_name = $event->getRequest()->attributes->get('_route');

    // Only act on menu UI routes.
    if ($route_name && (strpos($route_name, 'entity.menu.edit_form') === 0 ||
        strpos($route_name, 'entity.menu.') === 0)) {
      try {
        // Register our menu links with the menu system.
        $this->registerMenuLinks();
      }
      catch (\Exception $e) {
        \Drupal::logger('menu_link_view')->error('Error registering menu links: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Registers menu links with the menu system.
   */
  public function registerMenuLinks() {
    // Load all menu link view entities.
    $menu_link_views = $this->entityTypeManager->getStorage('menu_link_view')->loadMultiple();

    foreach ($menu_link_views as $id => $menu_link_view) {
      $plugin_id = 'menu_link_view:' . $id;

      // Force the menu link manager to recognize our plugin.
      $this->menuLinkManager->rebuild();

      // Make sure the plugin is in the active tree.
      if ($this->menuLinkManager->hasDefinition($plugin_id)) {
        $instance = $this->menuLinkManager->getInstance(['id' => $plugin_id]);

        // Log for debugging.
        \Drupal::logger('menu_link_view')->info('Plugin @id registered with menu system', [
          '@id' => $plugin_id,
        ]);
      }
    }
  }

}
