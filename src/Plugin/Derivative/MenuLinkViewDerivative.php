<?php

namespace Drupal\menu_link_view\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for menu link views.
 */
class MenuLinkViewDerivative extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MenuLinkViewDerivative.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    try {
      // Get all menu link view config entities.
      $menu_link_view_storage = $this->entityTypeManager->getStorage('menu_link_view');
      $menu_link_views = $menu_link_view_storage->loadMultiple();

      foreach ($menu_link_views as $menu_link_view) {
        $id = $menu_link_view->id();
        $links[$id] = [
          'title' => $menu_link_view->label(),
          'description' => $menu_link_view->getDescription(),
          'menu_name' => $menu_link_view->getMenuName(),
          'parent' => $menu_link_view->getParent() ?: '',
          'weight' => $menu_link_view->getWeight(),
          'metadata' => [
            'entity_id' => $id,
            'view_id' => $menu_link_view->getViewId(),
            'display_id' => $menu_link_view->getDisplayId(),
          ],
        ] + $base_plugin_definition;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('menu_link_view')->error('Error getting derivative definitions: @message', ['@message' => $e->getMessage()]);
    }

    return $links;
  }

}
