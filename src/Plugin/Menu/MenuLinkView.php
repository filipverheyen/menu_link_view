<?php

namespace Drupal\menu_link_view\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Menu Link View entity.
 *
 * @ConfigEntityType(
 *   id = "menu_link_view",
 *   label = @Translation("Menu Link View"),
 *   handlers = {
 *     "list_builder" = "Drupal\menu_link_view\MenuLinkViewListBuilder",
 *     "form" = {
 *       "add" = "Drupal\menu_link_view\Form\MenuLinkViewForm",
 *       "edit" = "Drupal\menu_link_view\Form\MenuLinkViewForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "menu_link_view",
 *   admin_permission = "administer menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "description",
 *     "menu_name",
 *     "parent",
 *     "weight",
 *     "view_id",
 *     "display_id",
 *     "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/menu_link_view/{menu_link_view}/edit",
 *     "delete-form" = "/admin/structure/menu_link_view/{menu_link_view}/delete",
 *     "collection" = "/admin/structure/menu_link_view",
 *   }
 * )
 */
class MenuLinkView extends ConfigEntityBase {

  /**
   * The Menu Link View ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Menu Link View title.
   *
   * @var string
   */
  protected $title;

  /**
   * The description.
   *
   * @var string
   */
  protected $description;

  /**
   * The menu name.
   *
   * @var string
   */
  protected $menu_name;

  /**
   * The parent menu link ID.
   *
   * @var string
   */
  protected $parent;

  /**
   * The weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The View ID.
   *
   * @var string
   */
  protected $view_id;

  /**
   * The View display ID.
   *
   * @var string
   */
  protected $display_id;

  /**
   * Gets the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Gets the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription() {
    return $this->description ?? '';
  }

  /**
   * Gets the menu name.
   *
   * @return string
   *   The menu name.
   */
  public function getMenuName() {
    return $this->menu_name;
  }

  /**
   * Gets the parent.
   *
   * @return string
   *   The parent.
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Gets the view ID.
   *
   * @return string
   *   The view ID.
   */
  public function getViewId() {
    return $this->view_id;
  }

  /**
   * Gets the display ID.
   *
   * @return string
   *   The display ID.
   */
  public function getDisplayId() {
    return $this->display_id;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add dependency on the view.
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    if ($view = $view_storage->load($this->getViewId())) {
      $this->addDependency('config', $view->getConfigDependencyName());
    }

    // Add dependency on the menu.
    $this->addDependency('config', 'system.menu.' . $this->getMenuName());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), [
      'config:system.menu.' . $this->getMenuName(),
      'menu:' . $this->getMenuName(),
    ]);
  }

}
