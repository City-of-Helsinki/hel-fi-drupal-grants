<?php

namespace Drupal\Tests\grants_metadata\Kernel;

/**
 * Class containing metadata for tests.
 */
class Mappings {
  const DEFINITIONS = [
    'yleisavustushakemus' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition',
      'parameter' => 'grants_metadata_yleisavustushakemus',
    ],
    'kasvatus_ja_koulutus_yleisavustu' => [
      'className' => 'KaskoYleisavustusDefinition',
      'parameter' => 'grants_metadata_kaskoyleis',
    ],
    'kuva_projekti' => [
      'className' => 'Drupal\grants_metadata\TypedData\Definition\KuvaProjektiDefinition',
      'parameter' => 'grants_metadata_kuvaprojekti',
    ],
    'liikunta_tapahtuma' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaTapahtumaDefinition',
      'parameter' => 'grants_metadata_liikuntatapahtuma',
    ],
    'kuva_toiminta' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KuvaToimintaDefinition',
      'parameter' => 'grants_metadata_kuvatoiminta',
    ],
    'liikunta_toiminta_ja_tilankaytto' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaTilankayttoDefinition',
      'parameter' => 'grants_metadata_liikuntatilankaytto',
    ],
    'failed' => [
      'class' => 'Drupal\grants_metadata_test_webforms\TypedData\Definition\FailedDataDefinition',
      'parameter' => 'grants_metadata_yleisavustushakemus',
    ],

  ];

}
