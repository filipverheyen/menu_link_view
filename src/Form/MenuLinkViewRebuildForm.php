<?php

namespace Drupal\menu_link_view\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for rebuilding menu link views.
 */
class MenuLinkViewRebuildForm extends ConfirmFormBase {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->menuLinkManager = $container->get('plugin.manager.menu.link');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_view_rebuild_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to rebuild the menu links?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.menu.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will rebuild all menu link view items. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Invalidate the plugin cache.
    $this->menuLinkManager->rebuild();

    // Invalidate menu render cache.
    $cache_tags = ['menu_link_view'];
    \Drupal::service('cache_tags.invalidator')->invalidateTags($cache_tags);

    // Message the user.
    $this->messenger()->addStatus($this->t('The menu links have been rebuilt.'));

    // Redirect back to the menu collection page.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
