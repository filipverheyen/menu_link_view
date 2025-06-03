<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a Menu link view.
 */
class MenuLinkViewDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.menu_link_view.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $menu_name = $this->entity->getMenuName();
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('The %menu menu link %label has been deleted.', [
      '%menu' => $menu_name,
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.menu.edit_form', ['menu' => $menu_name]);
  }

}
