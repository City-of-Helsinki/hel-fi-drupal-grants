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
