<?php

$config['grants_mandate.settings']['extra_access_roles'] = ['NIMKO','ELI'];
// Allow multiple sessions for smoother test process.
$config['session_limit.settings']['session_limit_roles']['helsinkiprofiili'] = 5;


$config['openid_connect.client.tunnistamo']['settings']['environment_url'] = 'https://tunnistus.test.hel.ninja/auth/realms/helsinki-tunnistus'; // NOSONAR
$config['openid_connect.client.tunnistamoadmin']['settings']['environment_url'] = 'https://tunnistus.test.hel.ninja/auth/realms/helsinki-tunnistus'; //NOSONAR
