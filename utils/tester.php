<?php
$json = file_get_contents(__DIR__ . "/tester.json") or die("Unable to open ./tester.json file!");
$response = json_decode(file_get_contents(json_decode($json)->url, false, stream_context_create(["http" => ["method" => "POST", "header" => "Content-Type: application/json\r\nAuthorization: Bearer " . json_decode($json)->token . "\r\n", "content" => $json]])), true) or die("Unable to decode response!");
echo (isset($response["choices"][0]["text"]) ? $response["choices"][0]["text"] . "\nchatflow >" : die("Unable to find response text!\n"));
$user_input = fgets(fopen("php://stdin", "r"));
echo ("user_input was " . $user_input . "\n");
