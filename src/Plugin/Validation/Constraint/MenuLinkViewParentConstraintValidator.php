<?php

namespace Drupal\menu_link_view\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MenuLinkViewParentConstraint constraint.
 */
class MenuLinkViewParentConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MenuLinkViewParentConstraintValidator object.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Only apply to menu_link_content entities.
    if (!isset($value) || $value->getEntityTypeId() !== 'menu_link_content') {
      return;
    }

    // Check if the parent is a view-based menu link.
    $parent_id = $value->getParentId();

    if (!$parent_id || strpos($parent_id, 'menu_link_view:') !== 0) {
      return;
    }

    // If parent is a view-based menu link, show the constraint message.
    $this->context->addViolation($constraint->message);
  }

}
