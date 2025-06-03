<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Url;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Menu link view add and edit forms.
 */
class MenuLinkViewForm extends EntityForm {

  /**
   * The menu parent form selector.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MenuLinkViewForm object.
   *
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector
   *   The menu parent form selector service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MenuParentFormSelectorInterface $menu_parent_selector, EntityTypeManagerInterface $entity_type_manager) {
    $this->menuParentSelector = $menu_parent_selector;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.parent_form_selector'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\menu_link_view\Entity\MenuLinkView $menu_link_view */
    $menu_link_view = $this->entity;
    $form['#tree'] = TRUE;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $menu_link_view->label(),
      '#description' => $this->t('The text to be used for this link in the menu.'),
      '#required' => TRUE,
    ];

    // Machine name field for the entity ID.
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $menu_link_view->id(),
      '#maxlength' => 64,
      '#description' => $this->t('A unique machine-readable name for this menu link view. It must only contain lowercase letters, numbers, and underscores.'),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['title'],
      ],
      '#disabled' => !$menu_link_view->isNew(),
    ];

    // Get all available views with entity reference displays.
    $view_options = [];
    $views = Views::getAllViews();
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
      '#default_value' => $menu_link_view->getViewId() ? $menu_link_view->getViewId() . ':' . $menu_link_view->getDisplayId() : NULL,
      '#required' => TRUE,
      '#description' => $this->t('Select a view with an entity reference display.'),
    ];

    // Get the menu parent options.
    $menu_name = $menu_link_view->getMenuName() ?: $form_state->getValue('menu_name');
    $parent = $menu_link_view->getParent() ?: '';

    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement(
      $menu_name ? $menu_name . ':' . $parent : '',
      $menu_link_view->id(),
      ['menu_link_view' => FALSE]
    );
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t('The parent menu link of this menu link. View menu links cannot have children.');
    $form['menu_parent']['#attributes']['class'][] = 'menu-parent-select';

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => 50,
      '#default_value' => $menu_link_view->getWeight(),
      '#description' => $this->t('Menu links with lower weights are displayed before links with higher weights.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $menu_link_view->getDescription(),
      '#description' => $this->t('Shown when hovering over the menu link.'),
      '#rows' => 2,
    ];

    // Note about how this link will work.
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This menu link will dynamically expand into multiple menu items based on the results from the selected view.') . '</p>',
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
    parent::validateForm($form, $form_state);

    // Make sure the view exists and has an entity reference display.
    if ($form_state->getValue('view')) {
      [$view_id, $display_id] = explode(':', $form_state->getValue('view'));

      $view = Views::getView($view_id);
      if (!$view || !$view->access($display_id)) {
        $form_state->setErrorByName('view', $this->t('The selected view is invalid or you do not have access to it.'));
      }

      // Ensure it's an entity reference display.
      if ($view && $view->setDisplay($display_id) && $view->getDisplay()->getPluginId() !== 'entity_reference') {
        $form_state->setErrorByName('view', $this->t('The selected view display must be of type "Entity Reference".'));
      }
    }

    // Extract menu parent information.
    if ($form_state->getValue('menu_parent')) {
      [$menu_name, $parent] = explode(':', $form_state->getValue('menu_parent'));
      $form_state->setValue('menu_name', $menu_name);
      $form_state->setValue('parent', $parent);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\menu_link_view\Entity\MenuLinkView $entity */
    $entity = $this->entity;

    // Extract view information.
    [$view_id, $display_id] = explode(':', $form_state->getValue('view'));
    $entity->setViewId($view_id);
    $entity->setDisplayId($display_id);

    // Set menu information.
    $entity->setMenuName($form_state->getValue('menu_name'));
    $entity->setParent($form_state->getValue('parent'));

    $status = $entity->save();

    $args = ['%label' => $entity->label()];
    if ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the %label Menu link view.', $args));
    }
    else {
      $this->messenger()->addStatus($this->t('Saved the %label Menu link view.', $args));
    }

    // Redirect to the menu edit form instead of the collection.
    $form_state->setRedirectUrl(Url::fromRoute('entity.menu.edit_form', ['menu' => $entity->getMenuName()]));
  }

}
