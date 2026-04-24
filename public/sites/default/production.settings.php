<?php
$config['grants_mandate.settings']['extra_access_roles'] = ['NIMKO','ELI'];

if (getenv('APP_ENV') == 'production') {
  $config['openid_connect.client.tunnistamo']['settings']['is_production'] = TRUE;
  $config['openid_connect.client.tunnistamoadmin']['settings']['is_production'] = TRUE;
}

// The non-admin client is configured in settings.php.
if ($tunnistamoAdmin_environment_url = getenv('TUNNISTAMOADMIN_ENVIRONMENT_URL')) {
  $config['openid_connect.client.tunnistamoadmin']['settings']['environment_url'] = $tunnistamoAdmin_environment_url;
}
