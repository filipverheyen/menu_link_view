<?php

namespace Drupal\menu_link_view\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom menu link for view references.
 *
 * @MenuLink(
 *   id = "menu_link_view",
 *   deriver = "Drupal\menu_link_view\Plugin\Derivative\MenuLinkViewDerivative"
 * )
 */
class MenuLinkViewLink extends MenuLinkBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

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
    return $this->pluginDefinition['title'] . ($this->isAdminRoute() ? ' [View Reference]' : '');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return '<nolink>';
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    return Url::fromRoute('<nolink>');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    // Get the enabled state from overrides.
    // Default to TRUE if not explicitly set to FALSE.
    $enabled = $this->getOverrideValue('enabled');
    return $enabled === NULL || $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    // Get the expanded state from overrides, default to TRUE for view links.
    $expanded = $this->getOverrideValue('expanded');
    return $expanded === NULL ? TRUE : (bool) $expanded;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    $parent = $this->getOverrideValue('parent');
    return $parent ?? $this->pluginDefinition['parent'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    $weight = $this->getOverrideValue('weight');
    return $weight ?? $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // We only want to store values regarding enabled state, weight, expanded, and parent.
    $overrides = array_intersect_key($new_definition_values, [
      'parent' => 1,
      'weight' => 1,
      'expanded' => 1,
      'enabled' => 1,
    ]);

    if ($persist) {
      try {
        $plugin_id = $this->getPluginId();
        if (is_string($plugin_id) && !empty($plugin_id)) {
          $this->staticOverride->saveOverride($plugin_id, $overrides);
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('menu_link_view')->error('Error saving override: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Update the plugin definition for this instance.
    $this->pluginDefinition = array_merge($this->pluginDefinition, $overrides);
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    $entity_id = $this->extractEntityIdFromPluginId();
    if ($entity_id) {
      return new Url('entity.menu_link_view.edit_form', ['menu_link_view' => $entity_id]);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    $entity_id = $this->extractEntityIdFromPluginId();
    if ($entity_id) {
      return new Url('entity.menu_link_view.delete_form', ['menu_link_view' => $entity_id]);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    $options['attributes']['class'][] = 'menu-link-view';

    // Add specific class when in admin route.
    if ($this->isAdminRoute()) {
      $options['attributes']['class'][] = 'menu-link-view-admin';
    }

    return $options;
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
    // Nothing to do here, the entity will be deleted separately.
  }

  /**
   * Gets the value for a definition override.
   *
   * @param string $key
   *   The definition key.
   *
   * @return mixed
   *   The override value or NULL if no override exists.
   */
  protected function getOverrideValue($key) {
    $overrides = [];

    try {
      $plugin_id = $this->getPluginId();
      if (is_string($plugin_id) && !empty($plugin_id)) {
        $overrides = $this->staticOverride->loadOverride($plugin_id) ?: [];
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('menu_link_view')->error('Error loading override: @message', [
        '@message' => $e->getMessage(),
      ]);
      $overrides = [];
    }

    return $overrides[$key] ?? NULL;
  }

  /**
   * Extracts the entity ID from the plugin ID.
   *
   * @return string|null
   *   The entity ID, or NULL if it could not be extracted.
   */
  protected function extractEntityIdFromPluginId() {
    $plugin_id = $this->getPluginId();
    if (is_string($plugin_id) && strpos($plugin_id, 'menu_link_view:') === 0) {
      return substr($plugin_id, strlen('menu_link_view:'));
    }
    return NULL;
  }

  /**
   * Checks if we're on an admin route.
   *
   * @return bool
   *   TRUE if we're on an admin route, FALSE otherwise.
   */
  protected function isAdminRoute() {
    return \Drupal::service('router.admin_context')->isAdminRoute();
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
  public function getCacheContexts() {
    return ['url.path.is_admin'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $entity_id = $this->extractEntityIdFromPluginId();
    return ['config:menu_link_view.menu_link_view.' . $entity_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getManipulationPluginId() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function inActiveTrail() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatedTitle() {
    return $this->t('@title', ['@title' => $this->getTitle()]);
  }

}
