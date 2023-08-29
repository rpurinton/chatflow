<?php
if (!isset($_SERVER["HTTP_AUTHORIZATION"])) error(400, "No token specified. You must specify a token in the HTTP Authorization header.");
$token = $_SERVER["HTTP_AUTHORIZATION"];
if (strlen($token) != 64) error(400, "Invalid token length. Must be 64 characters.");
if (substr($token, 0, 9) != "chatflow-") error(400, "Invalid token prefix. Must be 'chatflow-...'.");

try {
    require_once(__DIR__ . "/../../../SqlClient.php");
    $sql = new RPurinton\ChatFlow\SqlClient();
} catch (\Exception $e) {
    error(500, $e->getMessage());
} catch (\Error $e) {
    error(500, $e->getMessage());
} catch (\Throwable $e) {
    error(500, $e->getMessage());
}

$token = $sql->escape($token);
extract($sql->single("SELECT count(1) as `valid`, `token_id`, `token` FROM `api_tokens` WHERE `token` = '$token'"));
if (!$valid) error(401, "Invalid token. Check your token and try again.");
if (!$token_id) error(401, "Invalid token. Check your token and try again.");

$response = [];
if ($_SERVER["REQUEST_METHOD"] != "POST") error(400, "Invalid request method. Must be POST, not " . $_SERVER["REQUEST_METHOD"]);
$json_string_input = file_get_contents("php://input");
if (!$json_string_input) error(400, "No input data. You must POST a valid JSON string in the body.");
if (!($json_input = json_decode($json_string_input, true))) error(400, "The string in body cannot be decoded as JSON. Check your syntax and try again.");
if (!isset($json_input["session"])) error(400, "No session specified. You must specify a session ID or 'new' to create a new session.");
if ($json_input["session"] == "new") {
    if (!isset($json_input["collection"])) error(400, "No collection specified. You must specify a collection ID or 'new' to create a new collection.");
    $collection_id = $json_input["collection"];
    if (!is_numeric($collection_id)) error(400, "Invalid collection ID. Must be numeric.");
    extract($sql->single("SELECT count(1) as `valid` FROM `collections` WHERE `collection_id` = '$collection_id'"));
    if (!$valid) error(400, "Invalid collection ID. Check your collection ID and try again.");
    extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
    if (!$valid) error(401, "Invalid token for this collection. Check your token and try again.");
    $sql->query("INSERT INTO `sessions` (`collection_id`) VALUES ('$collection_id')");
    $session_id = $sql->insert_id();
} else {
    $session_id = $json_input["session"];
    if (!is_numeric($session_id)) error(400, "Invalid session ID. Must be numeric.");
    extract($sql->single("SELECT count(1) as `valid` FROM `sessions` WHERE `session_id` = '$session_id'"));
    if (!$valid) error(400, "Invalid session ID. Check your session ID and try again.");
    extract($sql->single("SELECT count(1) as `valid` FROM `collection_api_tokens` WHERE `collection_id` = (SELECT `collection_id` FROM `sessions` WHERE `session_id` = '$session_id') AND `token_id` = '$token_id'"));
    if (!$valid) error(401, "Invalid token for this session. Check your token and try again.");
}
$stream = isset($json_input["stream"]) && $json_input["stream"] === true ? true : false;
if ($stream) {
    header('Content-Type: plain/text; charset=utf-8');
    echo ("Starting stream test...\n");
    for ($i = 0; $i < 10; $i++) {
        pad_echo("$i...");
        sleep(1);
    }
} else {
    $response["result"] = "ok";
    $response["session"] = $session_id;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_PRETTY_PRINT);
}

function error($code, $message = null)
{
    header('Content-Type: application/json; charset=utf-8');
    $result = [];
    $result["result"] = "error";
    $result["code"] = $code;
    $result["error"] = $message;
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

function pad_echo($str)
{
    echo (str_pad($str, 4096, "\0"));
    flush();
    @ob_flush();
}
