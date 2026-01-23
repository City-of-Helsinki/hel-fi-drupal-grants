<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition;

/**
 * Define KuvaErillisDefinition data.
 */
class KuvaErillisDefinition extends ComplexDataDefinitionBase {

  use ApplicationDefinitionTrait;

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      foreach ($this->getBaseProperties() as $key => $property) {
        $info[$key] = $property;
      }
    }

    $info['compensation_purpose'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'generalInfoArray',
        'purpose',
      ]);

    $customQuestions = [
      'hankesuunnitelma_avustuksen_kesto' => [
        // This valueCallback implements default values.
        //
        // Default values don't actually do anything useful? Default values
        // are only used when value is NULL, but NULL values are sanitized
        // to empty string before that. Default values in Webform config do
        // nothing, since the fields are always initialized from typed data,
        // when the form is opened.
        //
        // @see \Drupal\grants_metadata\AtvSchema::getItemValue.
        'valueCallback' => static fn (mixed $value) => match($value) {
          "" => '1',
          default => $value
        },
        'defaultValue' => '1',
      ],
      // This field is read only / fully computed. However, the field must
      // be sent to ATV / avust2 or else the preview feature breaks. Field
      // values are saved/loaded from ATV when draft is saved/opened, and
      // the underlying component does not know how to recalculate its values
      // at that point. This can be removed if computed fields have better
      // support in the future.
      'haettava_avustussumma_2026' => [
        'valueCallback' => static fn (mixed $value) => $value['compensation'] ?? $value,
        'webformDataExtracter' => [
          'service' => 'grants_budget_components.service',
          'method' => 'extractToWebformData',
        ],
      ],
      'haettava_avustussumma_2027' => [],
      'ensisijainen_taiteen_ala' => [],
      'avustuksen_kohde_yhdistys_toimintaryhma' => [],
      'avustuksen_kohde_tiivistelma' => [],
      'hankkeen_nimi' => [],
      'hankkeen_tarkoitus_tavoitteet' => [],
      'hankkeen_monivuotisuuden_tarve' => [],
      'hankkeen_toimenpiteet_aikataulu' => [],
      'hankkeen_toimenpiteet_aikataulu_2026' => [],
      'hankkeen_toimenpiteet_aikataulu_2027' => [],
      'hankkeen_toimenpiteet_alkupvm' => [
        'typeOverride' => [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ],
        'valueCallback' => [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'Y-m-d',
          ],
        ],
      ],
      'hankkeen_toimenpiteet_loppupvm' => [
        'typeOverride' => [
          'dataType' => 'string',
          'jsonType' => 'datetime',
        ],
        'valueCallback' => [
          'service' => 'grants_metadata.converter',
          'method' => 'convertDates',
          'arguments' => [
            'dateFormat' => 'Y-m-d',
          ],
        ],
      ],
      'hankkeen_toiminnan_laajuus' => [],
      'hankkeen_kohtaamiset' => [],
      'hankkeen_keskeisimmat_kumppanit' => [],
      'hankkeen_uudet_kumppanit' => [],
      'haun_painopisteet_liikkumis_kehitys' => [],
      'haun_painopisteet_digi_kehitys' => [],
      'haun_painopisteet_vertais_kehitys' => [],
      'haun_painopisteet_kulttuuri_kehitys' => [],
      'hankkeen_kohderyhmat_kenelle' => [],
      'hankkeen_kohderyhmat_erityisryhmat' => [],
      'hankkeen_kohderyhmat_tavoitus' => [],
      'hankkeen_kohderyhmat_konkretia' => [],
      'hankkeen_kohderyhmat_osallisuus' => [],
      'hankkeen_kohderyhmat_osaaminen' => [],
      'hankkeen_kohderyhmat_lapset_9_12' => [],
      'hankkeen_kohderyhmat_lapset_13_15' => [],
      'hankkeen_kohderyhmat_lapset_16_18' => [],
      'hankkeen_kohderyhmat_nuoret_18_24' => [],
      'hankkeen_kohderyhmat_uudet' => [],
      'hankkeen_kohderyhmat_saavutettavuus' => [],
      'hankkeen_kohderyhmat_hinta' => [],
      'hankkeen_kohderyhmat_konkretia_liikunta' => [],
      'hankkeen_kohderyhmat_postinrot' => [],
      'hankkeen_kohderyhmat_tarve' => [],
      'hankkeen_kohderyhmat_tavoitus_seg' => [],
      'hankkeen_kohderyhmat_konkretia_seg' => [],
      'hankkeen_kohderyhmat_osallisuus_seg' => [],
      'hankkeen_keskeisimmat_kumppanit_seg' => [],
      'hankkeen_uudet_kumppanit_seg' => [],
      'hankkeen_kohderyhmat_miksi_alue' => [],
      'hankkeen_kohderyhmat_tavoitus_liikunta' => [],
      'hankkeen_kohderyhmat_osallisuus_liikunta' => [],
      'hankkeen_keskeisimmat_kumppanit_liikunta' => [],
      'hankkeen_uudet_kumppanit_liikunta' => [],
      'hankkeen_riskit_keskeisimmat' => [],
      'hankkeen_riskit_seuranta' => [],
      'hankkeen_riskit_vakiinnuttaminen' => [],
      'arviointi_toteuma' => [],
      'arviointi_muutokset_talous' => [],
      'arviointi_muutokset_toiminta' => [],
      'arviointi_muutokset_aikataulu' => [],
      'arviointi_haasteet' => [],
      'arviointi_saavutettavuus' => [],
      'arviointi_avustus_kaytto' => [],
      'onko_kyseessa_jatkohakemus' => [
        'type' => 'string',
        'valueCallback' => [
          'service' => 'grants_metadata.converter',
          'method' => 'convertBooleanToYesNo',
        ],
        'webformValueExtracter' => [
          'service' => 'grants_metadata.converter',
          'method' => 'extractBooleanYesNoValue',
        ],
      ],
      'hankkeen_mennyt_tavoite' => [],
      'hankkeen_kohderyhma_ja_keinot' => [],
      'hankkeen_jo_osallistuneet_9_12' => [],
      'hankkeen_jo_osallistuneet_18_24' => [],
      'hankkeen_jo_osalistuneet_uudet' => [],
      'hankkeen_kaytetyt_tunnit_per_postinumero' => [],
      'hankeen_toteutuneet_tavoitteet' => [],
      'hankkeen_tulevan_vuoden_tavoitteet' => [],
      'hankkeen_kumppanuudet_ja_toimintamuodot' => [],
      'hankkeen_vanhat_yhteistyokumppanit' => [],
      'hankkeen_lasten_ja_nuorten_osallistaminen_suunnitteluun' => [],
      'hankkeen_talous_ja_toiminta_section' => [],
      'hankkeen_kaytetty_avustuksen_maara' => [],
      'hankkeen_edellisen_vuoden_avustuksen_kaytto' => [],
      'hankkeen_talous_toiminta_aikataulu_muutokset' => [],
      'hankkeen_jatkokausi_suunnitelma' => [],
      'hankesuunnitelma_jatkohakemus' => [],
      'onko_jatkohakemus' => [],
      'toteutunut_kohderyhma' => [],
      'hankkeen_kohderyhmat_lapset_arvio' => [],
      'arvio_perustelu' => [],
      'toteutuneet_tunnit_eriteltyna' => [],
      'hankkeen_harrastuskerta_hinta' => [],
      'hankkeen_tekeminen_kuvaus' => [],
      'hankkeen_muutokset_talous' => [],
      'hankkeen_muutokset_toiminta' => [],
      'hankkeen_muutokset_aikataulu' => [],
      'hankkeen_muutokset_toimintamallit' => [],
      'hankkeen_yhteistyökumppanit' => [],
      'nuorten_osallistuminen_paatoksiin' => [],
      'hanke_haasteet' => [],
      'hankekaudelle_myonnetyn_avustuksen_kaytto' => [],
      'jatkokausi_alkuperainen_suunnitelma' => [],
      'jatkokausi_summa_muutos' => [],
      'hankkeen_kohderyhmat_lapset_9_12_arvio' => [],
      'hankkeen_kohderyhmat_lapset_13_15_arvio' => [],
      'hankkeen_kohderyhmat_lapset_16_18_arvio' => [],
      'hankkeen_kohderyhmat_uudet_arvio' => [],
    ];

    foreach ($customQuestions as $key => $value) {
      $this->createCustomQuestionDefinitions($key, $value, $info);
    }

    $info['budgetInfo'] = GrantsBudgetInfoDefinition::create('grants_budget_info')
      ->setSetting('propertyStructureCallback', [
        'service' => 'grants_budget_components.service',
        'method' => 'processBudgetInfo',
        'webform' => TRUE,
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_budget_components.service',
        'method' => 'extractToWebformData',
        'mergeResults' => TRUE,
      ])
      ->setSetting('jsonPath', ['compensation', 'budgetInfo'])
      ->setPropertyDefinition(
        'tulot',
        GrantsBudgetInfoDefinition::getStaticIncomeDefinition()
          ->setSetting('fieldsForApplication', [
            'compensation',
          ])
      )
      ->setPropertyDefinition(
        'talous_tulon_tyyppi',
        GrantsBudgetInfoDefinition::getOtherIncomeDefinition()
      )
      ->setPropertyDefinition(
        'talous_menon_tyyppi',
        GrantsBudgetInfoDefinition::getOtherCostDefinition()
      );

    $info['additional_information'] = DataDefinition::create('string')
      ->setSetting('jsonPath', ['compensation', 'additionalInformation'])
      ->setSetting('defaultValue', "");

    return $this->propertyDefinitions;
  }

  /**
   * Helper function generate repetitive definition.
   *
   * @param string $key
   *   Webform element key.
   * @param array $value
   *   Additional settings for given field.
   * @param array $info
   *   Data definitions.
   */
  private function createCustomQuestionDefinitions(string $key, array $value, array &$info): void {
    // Create initial definition with position in JSON.
    $info[$key] = DataDefinition::create($value['type'] ?? 'string')
      ->setSetting('jsonPath', [
        'compensation',
        'customQuestionsInfo',
        'customQuestionsArray',
        $key,
      ]);
    // Add type override if set.
    if (isset($value['typeOverride'])) {
      $info[$key]->setSetting('typeOverride', $value['typeOverride']);
    }
    // Add value callback if set.
    if (isset($value['valueCallback'])) {
      $info[$key]->setSetting('valueCallback', $value['valueCallback']);
    }
    // Add value extractor if set.
    if (isset($value['webformValueExtracter'])) {
      $info[$key]->setSetting('webformValueExtracter', $value['webformValueExtracter']);
    }
    if (isset($value['webformDataExtracter'])) {
      $info[$key]->setSetting('webformDataExtracter', $value['webformDataExtracter']);
    }
    // Add default value if set or empty value.
    if (isset($value['defaultValue'])) {
      $info[$key]->setSetting('defaultValue', $value['defaultValue']);
    }
    // DO not add defaultValue if not set, this makes all fields inserted into
    // data, this is not an issue, but if there's lot of fields
    // data may get confusing.
    // The negative is that if field is not set required in form, it will not
    // be added to data. If an empty field & value is wanted, the defaultValue
    // can be added to specific field in array above.
  }

}
