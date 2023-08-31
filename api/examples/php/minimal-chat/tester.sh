#!/bin/bash

json=$(cat tester.json)
response=$(curl -X POST $json.url \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $json.token" \
            -d "$json")

echo "$response" | jq -r '.choices[0].text'
while true; do
    read -p "> " input
    response=$(curl -X POST $json.url \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $json.token" \
                -d "{
                    \"session\": \"$response.session_id\",
                    \"messages\": [
                        {
                            \"role\": \"user\",
                            \"content\": \"$input\"
                        }
                    ],
                    \"passthru\": true,
                    \"stream\": true
                }")
    echo "$response" | jq -r .
done