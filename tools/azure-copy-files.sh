#!/usr/bin/env bash

if [[ -z ${AZ_SOURCE_CONTAINER} ]]; then
  echo "AZ_SOURCE_CONTAINER not set"
  read -p 'Azure source container name: ' AZ_SOURCE_CONTAINER
fi

if [[ -z ${AZ_SOURCE_SAS_TOKEN} ]]; then
  echo "AZ_SOURCE_SAS_TOKEN not set"
  read -p 'Azure source SAS TOKEN: ' AZ_SOURCE_SAS_TOKEN
fi


if [[ -z ${DESTINATION_ENV} ]]; then
  echo "DESTINATION_ENV not set"
  read -p 'Azure destination environment: ' DESTINATION_ENV
fi

if [[ "${DESTINATION_ENV}" == "dev"* || "${DESTINATION_ENV}" == "test"* ]]; then
  AZURE_DESTINATION_ACCOUNT=stplattadevtest
fi

if [[ "${DESTINATION_ENV}" == "stag"* ]]; then
  AZURE_DESTINATION_ACCOUNT=stplattastaging
fi

if [[ "${DESTINATION_ENV}" == "prod"* ]]; then
  AZURE_DESTINATION_ACCOUNT=stplattaprod
fi

if [[ -z ${AZURE_DESTINATION_ACCOUNT} ]]; then
  echo "AZURE_DESTINATION_ACCOUNT is not set."
  exit 1;
fi

if [[ -z ${AZ_DESTINATION_SAS_TOKEN} ]]; then
  echo "AZ_DESTINATION_SAS_TOKEN not set"
  read -p 'Azure destination SAS TOKEN: ' AZ_DESTINATION_SAS_TOKEN
fi

if [[ -z ${AZ_DESTINATION_CONTAINER} ]]; then
  echo "AZ_DESTINATION_CONTAINER not set"
  read -p 'Azure destination container name: ' AZ_DESTINATION_CONTAINER
fi

az storage azcopy blob download -c ${AZ_SOURCE_CONTAINER} --account-name stplattaprod --sas-token "${AZ_SOURCE_SAS_TOKEN}" -d . --recursive

if [[ ! -d "${AZ_SOURCE_CONTAINER}/" ]]; then
  echo "Source folder not found"
  exit 1;
fi

az storage azcopy blob upload -c ${AZ_DESTINATION_CONTAINER} --account-name ${AZURE_DESTINATION_ACCOUNT} --sas-token "${AZ_DESTINATION_SAS_TOKEN}" -s "${AZ_SOURCE_CONTAINER}/*" --recursive
