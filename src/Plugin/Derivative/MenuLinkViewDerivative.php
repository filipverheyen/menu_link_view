<?php

namespace Drupal\menu_link_view\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutableFactory;
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
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutableFactory;

  /**
   * Constructs a new MenuLinkViewDerivative.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\ViewExecutableFactory $view_executable_factory
   *   The view executable factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ViewExecutableFactory $view_executable_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->viewExecutableFactory = $view_executable_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('views.executable')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    // Get all menu link view config entities.
    $menu_link_view_storage = $this->entityTypeManager->getStorage('menu_link_view');
    $menu_link_views = $menu_link_view_storage->loadMultiple();

    foreach ($menu_link_views as $menu_link_view) {
      $id = $menu_link_view->id();
      $links[$id] = [
        'id' => 'menu_link_view:' . $id,
        'title' => $menu_link_view->label(),
        'description' => $menu_link_view->getDescription(),
        'menu_name' => $menu_link_view->getMenuName(),
        'expanded' => TRUE,
        'parent' => $menu_link_view->getParent(),
        'weight' => $menu_link_view->getWeight(),
        'provider' => 'menu_link_view',
        'class' => 'Drupal\menu_link_view\Plugin\Menu\MenuLinkViewLink',
        'options' => [],
        'metadata' => [
          'entity_id' => $id,
          'view_id' => $menu_link_view->getViewId(),
          'display_id' => $menu_link_view->getDisplayId(),
        ],
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
