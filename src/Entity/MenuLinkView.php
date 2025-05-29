<?php

namespace Drupal\menu_link_view\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the MenuLinkView entity.
 *
 * @ContentEntityType(
 *   id = "menu_link_view",
 *   label = @Translation("Menu Link View"),
 *   base_table = "menu_link_view",
 *   admin_permission = "administer menu link view",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class MenuLinkView extends ContentEntityBase {

  /**
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE);

    $fields['menu_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu name'))
      ->setRequired(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent'))
      ->setRequired(FALSE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDefaultValue(0);

    $fields['view_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('View ID'))
      ->setRequired(TRUE);

    $fields['view_display'] = BaseFieldDefinition::create('string')
      ->setLabel(t('View display ID'))
      ->setRequired(TRUE);

    return $fields;
  }

}
