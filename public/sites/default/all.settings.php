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

// AD roles <-> Drupal roles mapping.
$config['openid_connect.client.tunnistamoadmin']['settings']['ad_roles'] = [
  [
    'ad_role' => '[sl_avustustest_paakayttajat]',
    'roles' => ['ad_user', 'grants_admin'],
  ],
  [
    'ad_role' => '[sl_avustustest_pk_kanslia_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustustest_ta_kasko_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustustest_pk_pel_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustustest_ta_kymp_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustustest_ta_kuva_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustustest_ta_sote_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_kanslia_paakayttajat]',
    'roles' => ['ad_user', 'grants_admin'],
  ],
  [
    'ad_role' => '[sl_avustus_pk_kanslia]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_ta_kasko]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_ta_kymp]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_pk_pel]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_ta_kuva]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_avustus_ta_sote]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sl_kanslia_owakayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
  [
    'ad_role' => '[sg_kanslia_kayttajat]',
    'roles' => ['ad_user', 'content_producer_industry'],
  ],
];
