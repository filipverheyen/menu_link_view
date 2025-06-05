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
  public function getTitle(): string {
    return $this->definition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->definition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return $this->definition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return $this->definition['route_parameters'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE): Url {
    return Url::fromRoute($this->getRouteName(), $this->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->definition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return 'menu_link_view_synthetic_' . $this->definition['metadata']['entity_id'] . '_' . $this->definition['metadata']['view_row_index'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName(): string {
    return $this->definition['menu_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(): string {
    return $this->definition['parent'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded(): bool {
    return $this->definition['expanded'];
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider(): string {
    return $this->definition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData(): array {
    return $this->definition['metadata'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    return $this->definition['options'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute(): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute(): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute(): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getResetRoute(): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeId(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseId(): string {
    return 'menu_link_view_synthetic';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): array {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getNoTranslate(): ?bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalizationOptions(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
