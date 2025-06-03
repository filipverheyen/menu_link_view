<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Controller for menu link view add form.
 */
class MenuLinkViewAddForm extends MenuLinkViewForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Menu $menu = NULL) {
    // Create a new entity with the provided menu.
    $this->entity = $this->entityTypeManager->getStorage('menu_link_view')->create([
      'menu_name' => $menu->id(),
    ]);

    // Build the form.
    $form = parent::buildForm($form, $form_state);

    // Set the title.
    $form['#title'] = $this->t('Add view menu link to %menu', ['%menu' => $menu->label()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save view menu link');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // Redirect to the menu edit form.
    $form_state->setRedirect('entity.menu.edit_form', [
      'menu' => $this->entity->getMenuName(),
    ]);
  }

}
