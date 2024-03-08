#!/bin/bash

echo "================== RUN FORM CONFIGS ==================="

# Skip deployment script if ENV var is true
if [ "$SKIP_DEPLOY_SCRIPTS" = "true" ]; then
    echo "SKIP_DEPLOY_SCRIPTS is true. Stopping script."
    exit 0
fi

cd /var/www/html/public

function output_error_message {
  echo ${1}
  php ../docker/openshift/notify.php "${1}" || true
}

function get_deploy_id {
  echo $(drush state:get deploy_id_config)
}

PREFIXED_OC_BUILD_NAME="GRANTS-$OPENSHIFT_BUILD_NAME"
DRUSH_GET_VAR=$(get_deploy_id)

echo "Drush variable: $DRUSH_GET_VAR"
echo "Prefixed build name: $PREFIXED_OC_BUILD_NAME"

# This script is run every time a container is spawned and certain environments might
# start more than one Drupal container. This is used to make sure we run deploy
# tasks only once per deploy.
if [ "$DRUSH_GET_VAR" != "$PREFIXED_OC_BUILD_NAME" ]; then

  echo "Skip deploy commands to allow manual commands."


  echo "Set varible to state: $PREFIXED_OC_BUILD_NAME "
  drush state:set deploy_id_config $PREFIXED_OC_BUILD_NAME

  if [ $? -ne 0 ]; then
    output_error_message "Deployment failed: Failed set deploy_id_config"
    exit 1
  fi

  # Put site in maintenance mode
  echo "Site to maintenance"
  drush state:set system.maintenance_mode 1 --input-format=integer
  echo "DONE: Site to maintenance"

  APP_ENV=${APP_ENV:-default}

  echo "Import configs"
  # import configs & overrides.
  echo "DONE: Import configs"

  echo "Import webform configs"
  if [ "$APP_ENV" == 'staging' ] || [ "$APP_ENV" == 'development' ] || [ "$APP_ENV" == 'testing' ]; then
    drush gwi --force
  fi

  if [ "$APP_ENV" == 'production' ] || [ "$APP_ENV" == 'default' ]; then
    drush gwi
  fi
  echo "DONE: Import webform configs"

  echo "Import overrides."
  drush gwco
  echo "DONE: Import overrides."

  echo "Disable Maintenance"
  # Disable maintenance mode
  drush state:set system.maintenance_mode 0 --input-format=integer
  echo "DONE: Disable maintenance."

  if [ $? -ne 0 ]; then
    output_error_message "Deployment failure: Failed to disable maintenance_mode"
  fi
fi

echo "================== END FORM CONFIGS ==================="

echo "================== RUN TRANSLATION IMPORT ==================="
#drush locale:check; drush locale:update; drush cr
echo "================== END TRANSLATION IMPORT ==================="
