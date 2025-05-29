<?php

namespace Drupal\menu_link_view\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class MenuLinkViewDeriver extends DeriverBase implements ContainerDeriverInterface {

  protected string $basePluginId;
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(string $base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   *
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    \Drupal::logger('menu_link_view')->notice('Deriver called.');

    $definitions = [];
    $entities = $this->entityTypeManager->getStorage('menu_link_view')->loadMultiple();

    foreach ($entities as $entity) {
      $uuid = $entity->uuid();

      $definitions[$uuid] = [
        'title' => $entity->label(),
        'description' => 'View-based menu item',
        'menu_name' => $entity->get('menu_name')->value,
        'weight' => (int) $entity->get('weight')->value,
        'parent' => $entity->get('parent')->value ?? '',
        'route_name' => '<none>',
        'url' => Url::fromRoute('<none>'),
        'class' => 'Drupal\menu_link_view\Plugin\Menu\MenuLinkViewPlugin',
        'provider' => 'menu_link_view',
      ] + $base_plugin_definition;
    }

    return $definitions;
  }

}
