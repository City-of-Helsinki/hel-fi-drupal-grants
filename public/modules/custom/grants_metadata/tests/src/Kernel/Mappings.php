<?php

namespace Drupal\Tests\grants_metadata\Kernel;

/**
 * Class containing metadata for tests.
 */
class Mappings {
  const DEFINITIONS = [
    'asukapiena' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\AsukaPienaDefinition',
      'parameter' => 'grants_metadata_asukaspien',
    ],
    'hyvinyleis' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\HyvinYleisDefinition',
      'parameter' => 'grants_metadata_hyvinyleis',
    ],
    'kansliatyo' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KansliatyoDefinition',
      'parameter' => 'grants_metadata_kansliatyo',
    ],
    'kaskoiplisa' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KaskoIltapaivaLisaDefinition',
      'parameter' => 'grants_metadata_kaskoiplisa',
    ],
    'kaskotoiminta' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KaskoToimintaDefinition',
      'parameter' => 'grants_metadata_kaskotoiminta',
    ],
    'kasvatus_ja_koulutus_yleisavustu' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KaskoYleisavustusDefinition',
      'parameter' => 'grants_metadata_kaskoyleis',
    ],
    'kuvakeha' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KuvaKehaDefinition',
      'parameter' => 'grants_metadata_kuvakeha',
    ],
    'kuvaperus' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KuvaPerusDefinition',
      'parameter' => 'grants_metadata_kuvaperus',
    ],
    'kuva_toiminta' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KuvaToimintaDefinition',
      'parameter' => 'grants_metadata_kuvatoiminta',
    ],
    'kuva_projekti' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\KuvaProjektiDefinition',
      'parameter' => 'grants_metadata_kuvaprojekti',
    ],
    'liikuntalaitos' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaLaitosDefinition',
      'parameter' => 'grants_metadata_liikuntalaitos',
    ],
    'liikuntasuunnistus' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaSuunnistusDefinition',
      'parameter' => 'grants_metadata_liikuntasuunnistus',
    ],
    'liikunta_tapahtuma' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaTapahtumaDefinition',
      'parameter' => 'grants_metadata_liikuntatapahtuma',
    ],
    'liikunta_toiminta_ja_tilankaytto' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaTilankayttoDefinition',
      'parameter' => 'grants_metadata_liikuntatilankaytto',
    ],
    'liikuntayleis' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\LiikuntaYleisDefinition',
      'parameter' => 'grants_metadata_liikuntayleis',
    ],
    'nuorisoloma' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\NuorisoLomaDefinition',
      'parameter' => 'grants_metadata_nuorisoloma',
    ],
    'nuorisoprojekti' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\NuorisoProjektiDefinition',
      'parameter' => 'grants_metadata_nuorisoprojekti',
    ],
    'nuorisotoiminta' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\NuorisoToimintaDefinition',
      'parameter' => 'grants_metadata_nuorisotoiminta',
    ],
    'nuortoimennakko' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\NuorisoToimintaEnnakkoDefinition',
      'parameter' => 'grants_metadata_nuortoimennakko',
    ],
    'tyollisyysavustushakemus' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\TyollisyysavustusHakemusDefinition',
      'parameter' => 'grants_metadata_tyollisyysavustushakemus',
    ],
    'yleisavustushakemus' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition',
      'parameter' => 'grants_metadata_yleisavustushakemus',
    ],
    'ymparisto_yleis' => [
      'class' => 'Drupal\grants_metadata\TypedData\Definition\YmparistoYleisDefinition',
      'parameter' => 'grants_metadata_ymparisto_yleis',
    ],
    'failed' => [
      'class' => 'Drupal\grants_metadata_test_webforms\TypedData\Definition\FailedDataDefinition',
      'parameter' => 'grants_metadata_yleisavustushakemus',
    ],

  ];

}
