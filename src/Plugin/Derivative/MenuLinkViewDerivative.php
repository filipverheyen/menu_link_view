<?php

namespace Drupal\menu_link_view\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for menu link view plugins.
 */
class MenuLinkViewDerivative extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkViewStorage;

  /**
   * Constructs a new MenuLinkViewDerivative.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_link_view_storage
   *   The menu_link_view entity storage.
   */
  public function __construct(EntityStorageInterface $menu_link_view_storage) {
    $this->menuLinkViewStorage = $menu_link_view_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('menu_link_view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    // Load all menu link view entities.
    $menu_link_views = $this->menuLinkViewStorage->loadMultiple();

    foreach ($menu_link_views as $id => $menu_link_view) {
      // Create a derivative definition for each menu link view.
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['id'] = 'menu_link_view:' . $id;
      $this->derivatives[$id]['title'] = $menu_link_view->label();
      $this->derivatives[$id]['description'] = $menu_link_view->getDescription() ?: '';
      $this->derivatives[$id]['menu_name'] = $menu_link_view->getMenuName();
      $this->derivatives[$id]['parent'] = $menu_link_view->getParent() ?: '';
      $this->derivatives[$id]['weight'] = $menu_link_view->getWeight();
      $this->derivatives[$id]['expanded'] = TRUE;
      $this->derivatives[$id]['metadata'] = [
        'entity_id' => $id,
        'view_id' => $menu_link_view->getViewId(),
        'display_id' => $menu_link_view->getDisplayId(),
      ];
    }

    return $this->derivatives;
  }

}
