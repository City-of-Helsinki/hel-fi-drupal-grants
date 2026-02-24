<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\views\Attribute\ViewsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Canonical applicant type filter.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[ViewsFilter('canonical_applicant_type_select')]
final class CanonicalApplicantType extends CanonicalSelectFilterBase {

  private const string ENTITY_TYPE = 'node';
  private const string FIELD_NAME = 'field_hakijatyyppi';

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalField(): string {
    return 'canonical_applicant_type';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSelectOptions(): array {
    $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions(self::ENTITY_TYPE);
    if (!isset($storage_definitions[self::FIELD_NAME])) {
      return [];
    }

    $storage_definition = $storage_definitions[self::FIELD_NAME];

    // Provided by the Options module. Handles allowed_values_function and
    // returns translated labels (as rendered in the current language context).
    $allowed_values = options_allowed_values($storage_definition);

    $options = [];
    foreach ($allowed_values as $key => $label) {
      $options[(string) $key] = (string) $label;
    }

    return $options;
  }

}
