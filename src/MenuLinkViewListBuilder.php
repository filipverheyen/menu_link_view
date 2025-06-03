<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Menu link view entities.
 */
class MenuLinkViewListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Title');
    $header['menu_name'] = $this->t('Menu');
    $header['view'] = $this->t('View');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\menu_link_view\Entity\MenuLinkView $entity */
    $row['title'] = $entity->label();
    $row['menu_name'] = $entity->getMenuName();
    $row['view'] = $entity->getViewId() . ':' . $entity->getDisplayId();
    $row['weight'] = $entity->getWeight();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add a link to go to the menu this link belongs to.
    $operations['menu'] = [
      'title' => $this->t('View menu'),
      'weight' => 100,
      'url' => Url::fromRoute('entity.menu.edit_form', ['menu' => $entity->getMenuName()]),
    ];

    return $operations;
  }

}
