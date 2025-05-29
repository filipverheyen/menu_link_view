<?php

namespace Drupal\menu_link_view\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;

/**
 * @Menu(
 *   id = "menu_link_view",
 *   deriver = "Drupal\menu_link_view\Plugin\Derivative\MenuLinkViewDeriver"
 * )
 */
class MenuLinkViewPlugin extends MenuLinkDefault {

  /**
   *
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   *
   */
  public function getUrlObject() {
    return $this->pluginDefinition['url'] ?? Url::fromRoute('<none>');
  }

  /**
   *
   */
  public function getMenuName() {
    return $this->pluginDefinition['menu_name'];
  }

  /**
   *
   */
  public function getParentId() {
    return $this->pluginDefinition['parent'] ?? '';
  }

  /**
   *
   */
  public function isEnabled() {
    return TRUE;
  }

}
