<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\menu_link_view\Entity\MenuLinkView;
use Drupal\views\Views;

/**
 *
 */
class MenuLinkViewAddForm extends FormBase {

  /**
   *
   */
  public function getFormId() {
    return 'menu_link_view_add_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple();
    $menu_options = [];
    foreach ($menus as $menu_id => $menu) {
      $menu_options[$menu_id] = $menu->label();
    }

    $views = Views::getAllViews();
    $view_options = [];
    foreach ($views as $view_id => $view) {
      if ($view->get('base_table') && $view->get('display')) {
        $view_options[$view_id] = $view->label();
      }
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Internal title'),
      '#required' => TRUE,
    ];

    $form['menu_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => $menu_options,
      '#required' => TRUE,
    ];

    $form['parent'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent menu plugin ID'),
      '#description' => $this->t('Leave empty for top-level item.'),
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => 0,
    ];

    $form['view_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#options' => $view_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateDisplayOptions',
        'wrapper' => 'display-wrapper',
        'event' => 'change',
      ],
    ];

    $selected_view = $form_state->getValue('view_id');
    $display_options = [];

    if ($selected_view && $view = Views::getView($selected_view)) {
      foreach ($view->storage->get('display') as $id => $display) {
        $display_options[$id] = $id;
      }
    }

    $form['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display ID'),
      '#options' => $display_options,
      '#prefix' => '<div id="display-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   *
   */
  public function updateDisplayOptions(array &$form, FormStateInterface $form_state) {
    return $form['view_display'];
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $entity = MenuLinkView::create([
      'title' => $values['title'],
      'menu_name' => $values['menu_name'],
      'parent' => $values['parent'],
      'weight' => $values['weight'],
      'view_id' => $values['view_id'],
      'view_display' => $values['view_display'],
    ]);
    $entity->save();

    $this->messenger()->addMessage($this->t('Dynamic view menu item saved.'));
    $form_state->setRedirectUrl(Url::fromRoute('entity.menu.edit_form', ['menu' => $values['menu_name']]));
  }

}
