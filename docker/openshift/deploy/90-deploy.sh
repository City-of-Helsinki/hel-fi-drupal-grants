#!/bin/bash

echo "================== RUN FORM CONFIGS ==================="

# Skip deployment script if ENV var is true
if [ "$SKIP_DEPLOY_SCRIPTS" = "true" ]; then
    echo "SKIP_DEPLOY_SCRIPTS is true. Skipping the steps."
    return
fi
source /init.sh

echo "Skip deploy commands to allow manual commands."


echo "Set varible to state: $PREFIXED_OC_BUILD_NAME "
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
if [ $? -ne 0 ]; then
  output_error_message "Deployment failure: Failed to disable maintenance_mode"
fi
echo "DONE: Disable maintenance."

echo "================== END FORM CONFIGS ==================="

echo "================== RUN TRANSLATION IMPORT ==================="
#drush locale:check; drush locale:update; drush cr
echo "================== END TRANSLATION IMPORT ==================="
