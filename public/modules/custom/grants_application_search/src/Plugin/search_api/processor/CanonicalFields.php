<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Canonical fields processor.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[SearchApiProcessor(
  id: 'grants_application_search_canonical_fields',
  label: new TranslatableMarkup('Canonical fields (grants_application_search)'),
  description: new TranslatableMarkup('Canonical fields that merge multiple indexed fields.'),
  stages: [
    'add_properties' => 0,
    'add_field_values' => 0,
  ],
)]
final class CanonicalFields extends FieldsProcessorPluginBase {

  /**
   * Hardcoded mappings for subvention types.
   */
  public const array SUBVENTION_TYPE_MAP = [
    // phpcs:disable
    '45' => '1',  // Toiminta-avustus
    '46' => '2',  // Palkkausavustus
    '47' => '4',  // Projektiavustus
    '48' => '5',  // Vuokra-avustus
    '49' => '6',  // Yleisavustus
    '51' => '8',  // Korot ja lyhennykset
    '52' => '9',  // Muu
    '53' => '11', // Kuljetusavustus
    '54' => '12', // Leiriavustus
    '56' => '14', // Lisäavustus
    '57' => '15', // Suunnistuskartta-avustus
    '58' => '17', // Toiminnan kehittämisavustus
    '59' => '29', // Kehittämisavustukset / Helsingin malli
    '60' => '31', // Starttiavustus
    '61' => '32', // Tilankäyttöavustus
    '62' => '34', // Taiteen perusopetus
    '63' => '35', // Varhaiskasvatus
    '64' => '36', // Vapaa sivistystyö
    '65' => '37', // Tapahtuma-avustus
    '66' => '38', // Pienavustus
    '67' => '39', // Kotouttamisavustus
    '68' => '40', // Harrastushaku
    '69' => '41', // Laitosavustus
    '70' => '42', // Muiden liikuntaa edistävien yhteisöjen avustus
    '71' => '43', // Kohdeavustus
    '72' => '44', // Kehittämisavustus
    '73' => '45', // Helsingin mallin kehittämisavustus
    '74' => '46', // Taiteen perusopetuksen kehittämisavustus
    '76' => '47', // Kulttuurin erityisavustus 1
    '77' => '48', // Kulttuurin erityisavustus 2
    '78' => '53', // Kulttuurin ja vapaa-ajan erityisavustus 1
    '79' => '54', // Kulttuurin ja vapaa-ajan erityisavustus 2
    '81' => '51', // Liikunnan erityisavustus 1
    '82' => '52', // Liikunnan erityisavustus 2
    '83' => '49', // Nuorison erityisavustus 1
    '84' => '50', // Nuorison erityisavustus 2
    '2121' => '55', // Työllisyysavustus
    // phpcs:enable
  ];

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL): array {
    $definitions = [];

    $definitions['canonical_subvention_type'] = new ProcessorProperty([
      'label' => $this->t('Canonical subvention type'),
      'description' => $this->t('Merged canonical values from field_avustuslaji and application_subvention_type.'),
      'type' => 'string',
      'is_list' => TRUE,
      'processor_id' => $this->getPluginId(),
    ]);

    $definitions['canonical_applicant_type'] = new ProcessorProperty([
      'label' => $this->t('Canonical applicant type'),
      'description' => $this->t('Merged canonical values from field_hakijatyyppi and applicant_types.'),
      'type' => 'string',
      'is_list' => TRUE,
      'processor_id' => $this->getPluginId(),
    ]);

    $definitions['canonical_target_group'] = new ProcessorProperty([
      'label' => $this->t('Canonical target group'),
      'description' => $this->t('Merged canonical values from field_target_group and application_target_group.'),
      'type' => 'string',
      'is_list' => TRUE,
      'processor_id' => $this->getPluginId(),
    ]);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $fields = $item->getFields();
    $has_react_form = $this->hasReactForm($fields);

    // If React form is present, prefer the "application" fields.
    $subvention_sources = $has_react_form ? ['application_subvention_type'] : ['field_avustuslaji'];
    $applicant_sources = $has_react_form ? ['applicant_types'] : ['field_hakijatyyppi'];
    $target_group_sources = $has_react_form ? ['application_target_group'] : ['field_target_group'];

    // React side already contains canonical IDs; webform side uses term IDs.
    $subvention_map = $has_react_form
      ? $this->selfMap(array_values(self::SUBVENTION_TYPE_MAP))
      : self::SUBVENTION_TYPE_MAP;

    $this->populateCanonicalField($fields, 'canonical_subvention_type', $subvention_sources, $subvention_map);

    // Applicant and target group fields do not need mappings.
    // The canonical value equals source value.
    $this->populateCanonicalField($fields, 'canonical_applicant_type', $applicant_sources);
    $this->populateCanonicalField($fields, 'canonical_target_group', $target_group_sources);
  }

  /**
   * Checks if the current indexed item has a React form reference.
   *
   * @param array $fields
   *   The fields to check.
   *
   * @return bool
   *   Returns true or false.
   */
  private function hasReactForm(array $fields): bool {
    if (!isset($fields['field_react_form'])) {
      return FALSE;
    }

    foreach ($fields['field_react_form']->getValues() as $value) {
      if ((string) $value !== '') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Populates the canonical fields with values from the source fields.
   *
   * @param array $fields
   *   The indexed fields to process.
   * @param string $target_property
   *   The target canonical field name.
   * @param array $source_field_ids
   *   The source field to collect values from.
   * @param array|null $map
   *   The mapping array for transforming source values to canonical values.
   */
  private function populateCanonicalField(
    array $fields,
    string $target_property,
    array $source_field_ids,
    ?array $map = NULL,
  ): void {
    $target_fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, $target_property);
    if ($target_fields === []) {
      return;
    }

    $source_values = $this->collectValues($fields, $source_field_ids);
    if ($source_values === []) {
      return;
    }

    $canonical = [];

    // Build canonical values array using the mapping.
    foreach ($source_values as $value) {
      $key = (string) $value;
      $canonical_value = $map[$key] ?? $key;

      if ($canonical_value === '') {
        continue;
      }

      $canonical[$canonical_value] = $canonical_value;
    }

    if ($canonical === []) {
      return;
    }

    // Add canonical values to all target fields.
    foreach ($target_fields as $target_field) {
      foreach ($canonical as $value) {
        $target_field->addValue($value);
      }
    }
  }

  /**
   * Collects values from multiple source fields.
   *
   * @param array $fields
   *   The indexed fields to collect values from.
   * @param array $source_field_ids
   *   The fields to collect.
   *
   * @return array
   *   Collected values from all source fields.
   */
  private function collectValues(array $fields, array $source_field_ids): array {
    $values = [];

    foreach ($source_field_ids as $field_id) {
      if (!isset($fields[$field_id])) {
        continue;
      }

      $field_values = $fields[$field_id]->getValues();

      if ($field_values === []) {
        continue;
      }

      $values = array_merge($values, $field_values);
    }

    return $values;
  }

  /**
   * Build a self-mapping array.
   *
   * For example: ['a', 'b'] -> ['a' => 'a', 'b' => 'b'].
   *
   * @param array $values
   *   The values to map.
   *
   * @return array
   *   Mapped values.
   */
  private function selfMap(array $values): array {
    $map = [];

    foreach ($values as $value) {
      $key = (string) $value;
      if ($key === '') {
        continue;
      }
      $map[$key] = $key;
    }

    return $map;
  }

}
