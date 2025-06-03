<?php

namespace Drupal\menu_link_view\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines menu links provided by the menu_link_view module.
 *
 * @MenuLinkDefault(
 *   id = "menu_link_view",
 *   deriver = "Drupal\menu_link_view\Plugin\Derivative\MenuLinkViewDerivative"
 * )
 */
class MenuLinkViewLink extends MenuLinkDefault implements ContainerFactoryPluginInterface {

  /**
   * The static menu link service.
   *
   * @var \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  protected $staticOverride;

  /**
   * Constructs a new MenuLinkViewLink.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static menu link override service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->staticOverride = $static_override;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($this->isInAdminContext()) {
      // In the admin UI, append [View Reference] to make it clear this is a special link.
      return $this->pluginDefinition['title'] . ' [View Reference]';
    }
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();

    // Add special class for styling.
    $options['attributes']['class'][] = 'menu-link-view';

    if ($this->isInAdminContext()) {
      $options['attributes']['class'][] = 'menu-link-view-admin';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    // Use <nolink> route for the placeholder item.
    return '<nolink>';
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    $overrides = array_intersect_key($new_definition_values, [
      'weight' => 1,
      'expanded' => 1,
      'parent' => 1,
      'enabled' => 1,
    ]);

    if ($persist) {
      $this->staticOverride->saveOverride($this->getPluginId(), $overrides);
    }

    $this->pluginDefinition = array_merge($this->pluginDefinition, $overrides);
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    // Nothing to do here since the entity deletion will handle this.
  }

  /**
   * Determines if we're in an admin context.
   *
   * @return bool
   *   TRUE if we're in an admin context, FALSE otherwise.
   */
  protected function isInAdminContext() {
    // Use the route admin context service to check if we're in admin context.
    return \Drupal::service('router.admin_context')->isAdminRoute();
  }

}
