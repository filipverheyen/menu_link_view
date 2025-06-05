<?php

namespace Drupal\menu_link_view\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

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
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->menu_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->parent ?: '';
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
  public function getViewId() {
    return $this->view_id;
  }

  /**
   * {@inheritdoc}
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Invalidate cache tags for this menu.
    Cache::invalidateTags([
      'menu:' . $this->getMenuName(),
      'config:system.menu.' . $this->getMenuName(),
    ]);
  }

}
