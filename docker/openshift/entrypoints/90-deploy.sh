#!/bin/bash

# This script is run every time a container is spawned and certain environments might
# start more than one Drupal container. This is used to make sure we run deploy
# tasks only once per deploy.
if [ "$(get_deploy_id)" != "$OPENSHIFT_BUILD_NAME" ]; then
  drush state:set deploy_id $OPENSHIFT_BUILD_NAME

  if [ $? -ne 0 ]; then
    output_error_message "Deployment failed: Failed set deploy_id"
    exit 1
  fi
  # Put site in maintenance mode
  drush state:set system.maintenance_mode 1 --input-format=integer

  # import configs & overrides.
  drush gwi && drush gwco

  # Disable maintenance mode
  drush state:set system.maintenance_mode 0 --input-format=integer

  if [ $? -ne 0 ]; then
    output_error_message "Deployment failure: Failed to disable maintenance_mode"
  fi
fi
