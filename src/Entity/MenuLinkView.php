<?php

namespace Drupal\menu_link_view\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\menu_link_view\MenuLinkViewInterface;

/**
 * Defines the Menu link view entity.
 *
 * @ConfigEntityType(
 *   id = "menu_link_view",
 *   label = @Translation("Menu link view"),
 *   handlers = {
 *     "list_builder" = "Drupal\menu_link_view\MenuLinkViewListBuilder",
 *     "form" = {
 *       "default" = "Drupal\menu_link_view\Form\MenuLinkViewForm",
 *       "add" = "Drupal\menu_link_view\Form\MenuLinkViewAddForm",
 *       "delete" = "Drupal\menu_link_view\Form\MenuLinkViewDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   config_prefix = "menu_link_view",
 *   admin_permission = "administer menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/menu/view-link/{menu_link_view}",
 *     "edit-form" = "/admin/structure/menu/view-link/{menu_link_view}/edit",
 *     "delete-form" = "/admin/structure/menu/view-link/{menu_link_view}/delete",
 *     "collection" = "/admin/structure/menu/view-links"
 *   },
 *   config_export = {
 *     "id",
 *     "title",
 *     "view_id",
 *     "display_id",
 *     "menu_name",
 *     "parent",
 *     "weight",
 *     "description"
 *   }
 * )
 */
class MenuLinkView extends ConfigEntityBase implements MenuLinkViewInterface {

  /**
   * The Menu link view ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Menu link view title.
   *
   * @var string
   */
  protected $title;

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
   * The menu name.
   *
   * @var string
   */
  protected $menu_name;

  /**
   * The parent menu link plugin ID.
   *
   * @var string
   */
  protected $parent = '';

  /**
   * The weight of the menu link.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The description of the menu link.
   *
   * @var string
   */
  protected $description = '';

  /**
   * {@inheritdoc}
   */
  public function getViewId() {
    return $this->view_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewId($view_id) {
    $this->view_id = $view_id;
    return $this;
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
  public function setDisplayId($display_id) {
    $this->display_id = $display_id;
    return $this;
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
  public function setMenuName($menu_name) {
    $this->menu_name = $menu_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent) {
    $this->parent = $parent;
    return $this;
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
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Rebuild the menu link plugin definitions.
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    // Invalidate menu and render cache tags.
    \Drupal::service('cache_tags.invalidator')->invalidateTags([
      'menu:' . $this->getMenuName(),
      'menu_link_view',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Rebuild the menu link plugin definitions.
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    // Invalidate menu and render cache tags.
    $tags = ['menu_link_view'];
    foreach ($entities as $entity) {
      $tags[] = 'menu:' . $entity->getMenuName();
    }
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
  }

}
