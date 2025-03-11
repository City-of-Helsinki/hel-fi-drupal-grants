#!/bin/sh

if [ ! "$APP_ENV" = 'testing' ]; then
  exit 0
fi

# User UUID of the user whose test applications are deleted.
# This batch job cleans test applications for the e2e test user.
# See e2e/README.md for more details.
TEST_USER_UUID="13cb60ae-269a-46da-9a43-da94b980c067"

while true
do
  drush grants-tools:clean-test-applications "$TEST_USER_UUID"

  # Sleep 1 day.
  sleep 86400
done
