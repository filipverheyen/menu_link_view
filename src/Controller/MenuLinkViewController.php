<?php

namespace Drupal\menu_link_view\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for menu link view actions.
 */
class MenuLinkViewController extends ControllerBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MenuLinkViewController object.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the menu link view add form.
   *
   * @param \Drupal\system\Entity\Menu $menu
   *   The menu to add the menu link view to.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(Menu $menu) {
    // Create a new menu link view entity.
    $menu_link_view = $this->entityTypeManager->getStorage('menu_link_view')->create([
      'menu_name' => $menu->id(),
    ]);

    return $this->entityFormBuilder->getForm($menu_link_view, 'default');
  }

}
