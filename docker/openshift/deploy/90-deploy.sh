#!/bin/bash

# Skip deployment script if ENV var is true
if [ "$SKIP_DEPLOY_SCRIPTS" = "true" ]; then
    echo "SKIP_DEPLOY_SCRIPTS is true. Skipping the steps."
    return
fi
source /init.sh

# Enable maintenance mode
drush state:set system.maintenance_mode 1 --input-format=integer

APP_ENV=${APP_ENV:-default}

echo "Starting to import webform configurations"
if [ "$APP_ENV" == 'staging' ] || [ "$APP_ENV" == 'development' ] || [ "$APP_ENV" == 'testing' ]; then
  drush gwi --force
fi

if [ "$APP_ENV" == 'production' ] || [ "$APP_ENV" == 'default' ]; then
  drush gwi
fi

echo "Starting to import webform configuration overrides"
drush gwco

# Disable maintenance mode
drush state:set system.maintenance_mode 0 --input-format=integer
