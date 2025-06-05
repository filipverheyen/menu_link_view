<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Url;

/**
 * Class SyntheticMenuLink.
 *
 * Implements a synthetic menu link for view results.
 */
class SyntheticMenuLink implements MenuLinkInterface {

  /**
   * The menu link definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a new SyntheticMenuLink.
   *
   * @param array $definition
   *   The menu link definition.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->definition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->definition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return $this->definition['route_parameters'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject() {
    return Url::fromRoute($this->getRouteName(), $this->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->definition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'menu_link_view_synthetic_' . $this->definition['metadata']['entity_id'] . '_' . $this->definition['metadata']['view_row_index'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->definition['menu_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->definition['parent'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return $this->definition['expanded'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->definition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData() {
    return $this->definition['metadata'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return [];
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
  public function isResettable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    return TRUE;
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
    return TRUE;
  }

}
