#!/usr/bin/env bash

echo $(pwd)

. /app/test/.test_env

# first loop user uuids
for uuid in "${USER_IDT[@]}"; do
  # create url
  user_url="$ATV_BASE_URL/$ATV_VERSION/documents/?lookfor=appenv%3A$APP_ENV&service_name=$ATV_SERVICE&user_id=$uuid"
  echo "$user_url"
  # loop while there's next result
  while [ "$user_url" != "null" ]; do
    # Fetch the next page of results
    RESPONSE=$(curl -s --location "$user_url" \
      --header 'Accept-Encoding: utf8' \
      --header "X-Api-Key: $ATV_API_KEY")

    new_results=()

    # Extract the results from the response and append them to the array
    while IFS= read -r result; do
      new_results+=("$result")
    done < <(echo "$RESPONSE" | jq -r --arg filter "$APP_ENV" '.results[] | "\(.id) \(.transaction_id) \(.type) \(.business_id)"')

    echo "UUID RESULTS: ${#new_results[@]}"

    # loop parsed results
    for result in "${new_results[@]}"; do
      # Split the result into individual variables
      read -r id transaction_id type business_id <<<"$result"
      # create delete url
      delete_by_user_url="$ATV_BASE_URL/$ATV_VERSION/documents/$id"
      echo "DELETE BY UUID -> $delete_by_user_url"

      DELETERESPONSE=$(curl -s --location "$delete_by_user_url" --request DELETE \
        --header 'Accept-Encoding: utf8' \
        --header "X-Api-Key: $ATV_API_KEY")
    done

    # Get the URL of the next page of results, or set it to "null" if there are no more pages
    user_url=$(echo "$RESPONSE" | jq -r '.next')
  done

done

for business_id in "${Y_TUNNUKSET[@]}"; do
  business_url="$ATV_BASE_URL/$ATV_VERSION/documents/?lookfor=appenv%3A$APP_ENV&service_name=$ATV_SERVICE&business_id=$business_id"
  echo "GET -> $business_url"

  while [ "$business_url" != "null" ]; do
    # Fetch the next page of results
    RESPONSE=$(curl -s --location "$business_url" \
      --header 'Accept-Encoding: utf8' \
      --header "X-Api-Key: $ATV_API_KEY")

    new_results=()

    # Extract the results from the response and append them to the array
    while IFS= read -r result; do
      new_results+=("$result")
    done < <(echo "$RESPONSE" | jq -r --arg filter "$APP_ENV" '.results[] | "\(.id) \(.transaction_id) \(.type) \(.business_id)"')

    echo "BUSINESSID RESULTS: ${#new_results[@]}"

    for result in "${new_results[@]}"; do
      # Split the result into individual variables
      read -r id transaction_id type business_id <<<"$result"

      delete_by_user_url="$ATV_BASE_URL/$ATV_VERSION/documents/$id"
      echo "DELETE BY BUSINESSID -> $delete_by_user_url"

      DELETERESPONSE=$(curl -s --location "$delete_by_user_url" --request DELETE \
        --header 'Accept-Encoding: utf8' \
        --header "X-Api-Key: $ATV_API_KEY")
      echo "$DELETERESPONSE"
    done

    # Get the URL of the next page of results, or set it to "null" if there are no more pages
    business_url=$(echo "$RESPONSE" | jq -r '.next')
  done

done
