<?php

namespace Drupal\menu_link_view;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Menu link view entities.
 */
interface MenuLinkViewInterface extends ConfigEntityInterface {

  /**
   * Gets the View ID.
   *
   * @return string
   *   The View ID.
   */
  public function getViewId();

  /**
   * Sets the View ID.
   *
   * @param string $view_id
   *   The View ID.
   *
   * @return $this
   */
  public function setViewId($view_id);

  /**
   * Gets the Display ID.
   *
   * @return string
   *   The Display ID.
   */
  public function getDisplayId();

  /**
   * Sets the Display ID.
   *
   * @param string $display_id
   *   The Display ID.
   *
   * @return $this
   */
  public function setDisplayId($display_id);

  /**
   * Gets the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function getMenuName();

  /**
   * Sets the menu name.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return $this
   */
  public function setMenuName($menu_name);

  /**
   * Gets the parent menu link plugin ID.
   *
   * @return string
   *   The parent menu link plugin ID.
   */
  public function getParent();

  /**
   * Sets the parent menu link plugin ID.
   *
   * @param string $parent
   *   The parent menu link plugin ID.
   *
   * @return $this
   */
  public function setParent($parent);

  /**
   * Gets the weight of the menu link.
   *
   * @return int
   *   The weight of the menu link.
   */
  public function getWeight();

  /**
   * Sets the weight of the menu link.
   *
   * @param int $weight
   *   The weight of the menu link.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the description of the menu link.
   *
   * @return string
   *   The description of the menu link.
   */
  public function getDescription();

  /**
   * Sets the description of the menu link.
   *
   * @param string $description
   *   The description of the menu link.
   *
   * @return $this
   */
  public function setDescription($description);

}
