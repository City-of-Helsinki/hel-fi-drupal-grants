#!/usr/bin/env bash

# Description: Script to clear all documents from the environment.

# Get the directory of the currently executing script
script_dir="$(dirname "$0")"

# Source the environment variables file
source "$script_dir/.test_env"

settings_location="$script_dir/../public/sites/default/local.settings.php"

while IFS= read -r line; do
    # Extracting key-value pairs using sed
    if [[ "$line" == putenv* ]]; then
      key=$(echo "$line" | sed "s/putenv('\(.*\)=\(.*\)');/\1/")
      value=$(echo "$line" | sed "s/putenv('\(.*\)=\(.*\)');/\2/")
      # Setting environment variable
      export "$key"="$value"
    fi
done < "$settings_location"

# Initialize an array to keep track of successfully deleted documents
declare -a deleted_documents=()

# Output file for successfully deleted documents
output_file="deleted_documents.txt"

while [[ $# -gt 0 ]]; do
  case "$1" in
      --draft)
      echo "Removing drafts only"
      drafts=true
  esac
  shift
done

# Function to fetch and process results
fetch_and_process_results() {
  local url=$1
  declare -a document_data=()

  # Loop listing to get all document ids that we going to delete.
  while [ "$url" != "null" ]; do
    local response=$(curl -s --location "$url" \
      --header 'Accept-Encoding: utf8' \
      --header "X-Api-Key: $ATV_API_KEY")

    if echo "$response" | jq -e '.results' >/dev/null; then
      local new_results=()
      while IFS= read -r result; do
        new_results+=("$result")
      done < <(echo "$response" | jq -r '.results[] | "\(.id) \(.transaction_id) \(.status.value)"')

      echo "RESULTS: ${#new_results[@]}"

      for result in "${new_results[@]}"; do
        read -r id transaction_id status <<<"$result"

        if [ -n "$drafts" -a "$status" != "DRAFT" ]; then
          continue
        fi
        document_data+=("$id:$transaction_id")
      done
    else
      echo "${identifier} RESULTS: ${#new_results[@]}"
    fi

    url=$(echo "$response" | jq -r '.next')
  done

  # Loop the list and delete each document from ATV.
  for data in ${document_data[@]}; do

        IFS=':' read -r id transaction_id <<< "$data"
        local delete_url="$ATV_BASE_URL/v1/documents/$id/"
        echo "DELETE by $delete_url"

        local DELETERESPONSE=$(curl -s -i --location "$delete_url" --request DELETE \
          --header 'Accept-Encoding: utf8' \
          --header "X-Api-Key: $ATV_API_KEY")

        #Check the HTTP status code in the response
        HTTP_STATUS=$(echo "$DELETERESPONSE" | head -n 1 | awk '{print $2}')

        if [ "$HTTP_STATUS" -ge 200 ] && [ "$HTTP_STATUS" -lt 300 ]; then
          deleted_documents+=("$transaction_id")
          echo "$transaction_id" >>"$output_file"
        else
          echo "DELETE request failed. HTTP Status Code: $HTTP_STATUS"
        fi
  done
}

# Function to process IDs
process_ids() {
  local url="$ATV_BASE_URL/v1/documents/?lookfor=appenv%3A$APP_ENV&service_name=AvustushakemusIntegraatio"
  fetch_and_process_results "$url" "$identifier"
}

process_ids

if [ ${#deleted_documents[@]} -eq 0 ]; then
  echo "No documents deleted."
else
  echo "The list of successfully deleted documents can be found in ${output_file}"
fi
