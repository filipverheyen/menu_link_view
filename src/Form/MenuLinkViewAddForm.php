<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the menu link view add form.
 */
class MenuLinkViewAddForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu being edited.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * Constructs a MenuLinkViewAddForm object.
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
  public function getFormId() {
    return 'menu_link_view_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Menu $menu = NULL) {
    // Store the menu for later use.
    $this->menu = $menu;

    if (!$menu) {
      $this->messenger()->addError($this->t('No menu specified.'));
      return $form;
    }

    $form['#title'] = $this->t('Add view menu link to %menu', ['%menu' => $menu->label()]);
    $form['#tree'] = TRUE;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#description' => $this->t('The text to be used for this link in the menu.'),
      '#required' => TRUE,
    ];

    // Machine name field for the entity ID.
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique machine-readable name for this menu link view. It must only contain lowercase letters, numbers, and underscores.'),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['title'],
      ],
    ];

    // Get all available views with entity reference displays.
    $view_options = [];
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    foreach ($views as $view) {
      if (!$view->status()) {
        continue;
      }

      foreach ($view->get('display') as $display_id => $display) {
        if ($display['display_plugin'] === 'entity_reference') {
          $view_options[$view->id() . ':' . $display_id] = $view->label() . ' (' . $display['display_title'] . ')';
        }
      }
    }

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View reference'),
      '#options' => $view_options,
      '#empty_option' => $this->t('- Select a view -'),
      '#required' => TRUE,
      '#description' => $this->t('Select a view with an entity reference display.'),
    ];

    // Hidden menu name field.
    $form['menu_name'] = [
      '#type' => 'hidden',
      '#value' => $menu->id(),
    ];

    $form['parent'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => 50,
      '#default_value' => 0,
      '#description' => $this->t('Menu links with lower weights are displayed before links with higher weights.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Shown when hovering over the menu link.'),
      '#rows' => 2,
    ];

    // Note about how this link will work.
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This menu link will dynamically expand into multiple menu items based on the results from the selected view.') . '</p>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save view menu link'),
    ];

    return $form;
  }

  /**
   * Determines if the menu link view already exists.
   *
   * @param string $id
   *   The menu link view ID.
   *
   * @return bool
   *   TRUE if the menu link view exists, FALSE otherwise.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('menu_link_view')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Make sure the view exists and has an entity reference display.
    if ($form_state->getValue('view')) {
      [$view_id, $display_id] = explode(':', $form_state->getValue('view'));

      $view = $this->entityTypeManager->getStorage('view')->load($view_id);
      if (!$view) {
        $form_state->setErrorByName('view', $this->t('The selected view is invalid.'));
      }

      // Ensure it's an entity reference display.
      if ($view && isset($view->get('display')[$display_id]) &&
          $view->get('display')[$display_id]['display_plugin'] !== 'entity_reference') {
        $form_state->setErrorByName('view',
          $this->t('The selected view display must be of type "Entity Reference".'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extract view information.
    [$view_id, $display_id] = explode(':', $form_state->getValue('view'));

    // Create entity.
    $values = [
      'id' => $form_state->getValue('id'),
      'title' => $form_state->getValue('title'),
      'view_id' => $view_id,
      'display_id' => $display_id,
      'menu_name' => $form_state->getValue('menu_name'),
      'parent' => $form_state->getValue('parent'),
      'weight' => $form_state->getValue('weight'),
      'description' => $form_state->getValue('description'),
    ];

    $entity = $this->entityTypeManager->getStorage('menu_link_view')->create($values);
    $status = $entity->save();

    $args = ['%label' => $entity->label()];
    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the %label Menu link view.', $args));
    }
    else {
      $this->messenger()->addStatus($this->t('Saved the %label Menu link view.', $args));
    }

    // Redirect to the menu edit form.
    $form_state->setRedirect('entity.menu.edit_form', [
      'menu' => $entity->getMenuName(),
    ]);
  }

}
