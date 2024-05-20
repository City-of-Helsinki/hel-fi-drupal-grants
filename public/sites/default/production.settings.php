<?php
$config['grants_mandate.settings']['extra_access_roles'] = ['NIMKO','ELI'];

if (getenv('APP_ENV') == 'production') {
  $config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
  $config['openid_connect.client.tunnistamoadmin']['settings']['is_production'] = TRUE;
}

if ($tunnistamo_environment_url = getenv('TUNNISTAMO_ENVIRONMENT_URL')) {
  $config['openid_connect.client.tunnistamo']['settings']['environment_url'] = $tunnistamo_environment_url;
}

if ($tunnistamoAdmin_environment_url = getenv('TUNNISTAMOADMIN_ENVIRONMENT_URL')) {
  $config['openid_connect.client.tunnistamoadmin']['settings']['environment_url'] = $tunnistamoAdmin_environment_url;
}
