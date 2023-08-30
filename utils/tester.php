<?php
$json = file_get_contents(__DIR__ . "/tester.json");
$result = file_get_contents(json_decode($json)->url, false, stream_context_create(["http" => ["method" => "POST", "header" => "Content-Type: application/json\r\nAuthorization: Bearer {json_decode($json)->token}\r\n", "content" => $json]]));
echo ($result);
