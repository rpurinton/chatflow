<?php
$json = json_decode(file_get_contents(__DIR__ . "/tester.json")) or die("Unable to open ./tester.json file!");
$response = json_decode(file_get_contents($json->url, false, stream_context_create(["http" => ["method" => "POST", "header" => "Content-Type: application/json\r\nAuthorization: Bearer " . $json->token . "\r\n", "content" => json_encode($json)]])), true) or die("Unable to decode response!");
echo (isset($response["choices"][0]["message"]["content"]) ? "Press CTRL+C to exit...\n" . $response["choices"][0]["message"]["content"] . "\n> " : die("Unable to find response text!\n"));
while (true) {
    $fp = fopen($json->url, "r", false, stream_context_create(["http" => ["method" => "POST", "header" => "Content-Type: application/json\r\nAuthorization: Bearer " . $json->token . "\r\n", "content" => json_encode(["session" => $response["session_id"], "messages" => [["role" => "user", "content" => trim(fgets(fopen("php://stdin", "r")))]], "passthru" => true, "prompt_tokens" => 512, "stream" => true])]])) or die("Unable to open stream!");
    while (!feof($fp)) echo str_replace("\0", "", fgetc($fp));
    fclose($fp);
    echo ("\n> ");
}
