<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Url;

/**
 * Represents a menu link created from a view result.
 */
class ViewResultMenuLink implements MenuLinkInterface {

  /**
   * The title of the menu link.
   *
   * @var string
   */
  protected $title;

  /**
   * The URL of the menu link.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * The ID of the menu link.
   *
   * @var string
   */
  protected $id;

  /**
   * The weight of the menu link.
   *
   * @var int
   */
  protected $weight;

  /**
   * Constructs a new ViewResultMenuLink.
   *
   * @param string $title
   *   The title of the menu link.
   * @param \Drupal\Core\Url $url
   *   The URL of the menu link.
   * @param string $id
   *   The ID of the menu link.
   * @param int $weight
   *   The weight of the menu link.
   */
  public function __construct($title, Url $url, $id, $weight = 0) {
    $this->title = $title;
    $this->url = $url;
    $this->id = $id;
    $this->weight = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->url->getRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return $this->url->getRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->url->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return 'menu_link_view';
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Cannot update these menu items.
    return $this->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    return NULL;
  }

}
