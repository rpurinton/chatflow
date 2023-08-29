<?php
$response = [];
if ($_SERVER["REQUEST_METHOD"] != "POST") error(400);
$json_string_input = file_get_contents("php://input");
if (!$json_string_input) error(400);
if (!($json_input = json_decode($json_string_input, true))) error(400);
try {
    require_once(__DIR__ . "/../../../SqlClient.php");
    $sql = new RPurinton\ChatFlow\SqlClient();
} catch (\Exception $e) {
    error(500);
} catch (\Error $e) {
    error(500);
} catch (\Throwable $e) {
    error(500);
}
$token = $_SERVER["HTTP_AUTHORIZATION"];
if (strlen($token) != 64) error(400);
if (substr($token, 0, 9) != "chatflow-") error(400);
$token = $sql->escape($token);
extract($sql->single("SELECT count(1) as `valid`, `token_id`, `token` FROM `api_tokens` WHERE `token` = '$token'"));
if (!$valid) error(401);
if (!$token_id) error(401);
if (!isset($json_input["session"])) error(400);
if ($json_input["session"] == "new") {
    // create a new session
    // check if collection is set
    if (!isset($json_input["collection"])) error(400);
    $collection_id = $json_input["collection"];
    // check if collection is valid
    if (!is_numeric($collection_id)) error(400);
    // check if collection exists
    extract($sql->single("SELECT count(1) as `valid` FROM `collections` WHERE `collection_id` = '$collection_id'"));
    if (!$valid) error(400);
    // check if this api token has access to this collection
    extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
    if (!$valid) error(401);
    // create the new session
    $sql->query("INSERT INTO `sessions` (`collection_id`) VALUES ('$collection_id')");
    $session_id = $sql->insert_id();
} else {
    $session_id = $json_input["session"];
    if (!is_numeric($session_id)) error(400);
    extract($sql->single("SELECT count(1) as `valid` FROM `sessions` WHERE `session_id` = '$session_id'"));
    if (!$valid) error(400);
    extract($sql->single("SELECT count(1) as `valid` FROM `collection_api_tokens` WHERE `collection_id` = (SELECT `collection_id` FROM `sessions` WHERE `session_id` = '$session_id') AND `token_id` = '$token_id'"));
    if (!$valid) error(401);
}

$response["result"] = "ok";
$response["session"] = $session_id;
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);


function error($code)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    include(__DIR__ . "/../../errordocs/$code.json");
    exit;
}
