<?php

/**
 * @file
 * Contains site specific overrides.
 */


$config['openid_connect.client.tunnistamo']['settings']['client_id'] = getenv('TUNNISTAMO_CLIENT_ID');
$config['openid_connect.client.tunnistamo']['settings']['client_secret'] = getenv('TUNNISTAMO_CLIENT_SECRET');
$config['openid_connect.client.tunnistamo']['settings']['client_scopes'] = getenv('TUNNISTAMO_CLIENT_SCOPES');

$config['openid_connect.client.tunnistamoadmin']['settings']['client_id'] = getenv('TUNNISTAMOADMIN_CLIENT_ID');
$config['openid_connect.client.tunnistamoadmin']['settings']['client_secret'] = getenv('TUNNISTAMOADMIN_CLIENT_SECRET');
$config['openid_connect.client.tunnistamoadmin']['settings']['client_scopes'] = getenv('TUNNISTAMOADMIN_CLIENT_SCOPES');

$settings['error_page']['template_dir'] = '../error_templates';

// Level of Assurance <-> Drupal roles mapping.
$config['openid_connect.client.tunnistamo']['settings']['loa_roles'] = [
  [
    'loa' => 'substantial',
    'roles' => ['helsinkiprofiili'],
  ],
  [
    'loa' => 'high',
    'roles' => ['helsinkiprofiili'],
  ],
];

$config['openid_connect.client.tunnistamoadmin']['settings']['client_roles'] = ['ad_user'];

// AD roles <-> Drupal roles mapping.
$config['openid_connect.client.tunnistamoadmin']['settings']['ad_roles'] = [
  // Old mappings.
  [
    'ad_role' => 'sl_avustustest_paakayttajat',
    'roles' => ['grants_admin'],
  ],
  [
    'ad_role' => 'sl_avustustest_pk_kanslia_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustustest_ta_kasko_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustustest_pk_pel_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustustest_ta_kymp_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustustest_ta_kuva_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustustest_ta_sote_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_kanslia_paakayttajat',
    'roles' => ['grants_admin'],
  ],
  [
    'ad_role' => 'sl_avustus_pk_kanslia',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_ta_kasko',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_ta_kymp',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_pk_pel',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_ta_kuva',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_avustus_ta_sote',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sl_kanslia_owakayttajat',
    'roles' => ['content_producer_industry'],
  ],
  [
    'ad_role' => 'sg_kanslia_kayttajat',
    'roles' => ['content_producer_industry'],
  ],
  // New mappings.
  [
    'ad_role' => '947058f4-697e-41bb-baf5-f69b49e5579a',
    'roles' => ['super_administrator'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_kaupunkitaso_paakayttajat',
    'roles' => ['grants_admin'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_paakayttajat',
    'roles' => ['grants_admin'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea',
    'roles' => ['content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kanslia',
    'roles' => ['content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kanslia',
    'roles' => ['content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kasko',
    'roles' => ['content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kasko',
    'roles' => ['content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kuva',
    'roles' => ['content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kuva',
    'roles' => ['content_producer'],
  ],
  // New mappings.
  [
    'ad_role' => 'Drupal_Helfi_kaupunkitaso_paakayttajat',
    'roles' => ['ad_user', 'grants_admin'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_paakayttajat',
    'roles' => ['ad_user', 'grants_admin'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea',
    'roles' => ['ad_user', 'content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kanslia',
    'roles' => ['ad_user', 'content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kanslia',
    'roles' => ['ad_user', 'content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kasko',
    'roles' => ['ad_user', 'content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kasko',
    'roles' => ['ad_user', 'content_producer'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_laaja_Kuva',
    'roles' => ['ad_user', 'content_producer', 'grants_producer_industry'],
  ],
  [
    'ad_role' => 'Drupal_Helfi_Avustukset_sisallontuottajat_suppea_Kuva',
    'roles' => ['ad_user', 'content_producer'],
  ],
];
