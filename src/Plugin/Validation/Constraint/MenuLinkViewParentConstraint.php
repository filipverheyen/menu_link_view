<?php

namespace Drupal\menu_link_view\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that menu items don't have a view-based menu link as parent.
 *
 * @Constraint(
 *   id = "MenuLinkViewParentConstraint",
 *   label = @Translation("Menu Link View parent constraint")
 * )
 */
class MenuLinkViewParentConstraint extends Constraint {

  /**
   * Message shown when a user tries to add a child to a view-based menu link.
   *
   * @var string
   */
  public $message = 'View-based menu links cannot have children.';

}
