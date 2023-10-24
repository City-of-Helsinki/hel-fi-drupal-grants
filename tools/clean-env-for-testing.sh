#!/usr/bin/env bash

# Description: Script for deleting documents based on user and business IDs

# Get the directory of the currently executing script
script_dir="$(dirname "$0")"

# Source the environment variables file
source "$script_dir/.test_env"

# Initialize an array to keep track of successfully deleted documents
declare -a deleted_documents=()

# Output file for successfully deleted documents
output_file="deleted_documents.txt"

# Function to fetch and process results
fetch_and_process_results() {
  local url=$1
  local identifier=$2

  while [ "$url" != "null" ]; do
    local response=$(curl -s --location "$url" \
      --header 'Accept-Encoding: utf8' \
      --header "X-Api-Key: $ATV_API_KEY")

    if echo "$response" | jq -e '.results' >/dev/null; then
      local new_results=()
      while IFS= read -r result; do
        new_results+=("$result")
      done < <(echo "$response" | jq -r '.results[] | "\(.id) \(.transaction_id) \(.type) \(.business_id)"')

      echo "${identifier} RESULTS: ${#new_results[@]}"

      for result in "${new_results[@]}"; do
        read -r id transaction_id type business_id <<<"$result"

        if [ "$type" = "grants_profile" ]; then
          continue
        fi

        local delete_url="$ATV_BASE_URL/$ATV_VERSION/documents/$id"
        echo "DELETE by ${identifier} -> $delete_url"

        local DELETERESPONSE=$(curl -s --location "$delete_url" --request DELETE \
          --header 'Accept-Encoding: utf8' \
          --header "X-Api-Key: $ATV_API_KEY")

        # Check the HTTP status code in the response
        HTTP_STATUS=$(echo "$DELETERESPONSE" | head -n 1 | awk '{print $2}')

        if [ "$HTTP_STATUS" -ge 200 ] && [ "$HTTP_STATUS" -lt 300 ]; then
          deleted_documents+=("$transaction_id")
          echo "$transaction_id" >>"$output_file"
        else
          echo "DELETE request failed. HTTP Status Code: $HTTP_STATUS"
        fi

      done
    else
      echo "${identifier} RESULTS: ${#new_results[@]}"
    fi

    url=$(echo "$response" | jq -r '.next')
  done
}

# Function to process IDs
process_ids() {
  local ids=("${!1}")
  local identifier=$2
  local query_param=$3

  for id in "${ids[@]}"; do
    local url="$ATV_BASE_URL/$ATV_VERSION/documents/?lookfor=appenv%3A$APP_ENV&service_name=$ATV_SERVICE&$query_param=$id"
    echo "Processing ${identifier} URL: $url"
    fetch_and_process_results "$url" "$identifier"
  done
}

# Main script starts here

# Check and process USER_IDS
[ -z "${USER_IDS[*]}" ] && echo "USER_IDS is empty, skipping.." || process_ids USER_IDS[@] "UUID" "user_id"

# Check and process BUSINESS_IDS
[ -z "${BUSINESS_IDS[*]}" ] && echo "BUSINESS_IDS is empty, skipping.." || process_ids BUSINESS_IDS[@] "BUSINESS_ID" "business_id"

if [ ${#deleted_documents[@]} -eq 0 ]; then
  echo "No documents deleted."
else
  echo "The list of successfully deleted documents can be found in ${output_file}"
fi
