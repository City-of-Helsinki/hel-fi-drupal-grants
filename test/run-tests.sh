#!/usr/bin/env bash

source env/bin/activate
TEST_BASEURL=https://hel-fi-drupal-grant-applications.docker.so/ robot -d logs/ tests/*.robot
