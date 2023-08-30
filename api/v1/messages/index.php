<?php
// Look for a token in the HTTP Authorization header
if (!isset($_SERVER["HTTP_AUTHORIZATION"])) error(400, "No token specified. You must specify a token in the HTTP Authorization header.");

// Validate the token
$token = trim(str_replace("Bearer", "", $_SERVER["HTTP_AUTHORIZATION"]));
if (strlen($token) != 64) error(400, "Invalid token length. Must be 64 characters.");
if (substr($token, 0, 9) != "chatflow-") error(400, "Invalid token prefix. Must be 'chatflow-...'.");

// Connect to the database
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

// Continue to Validate the token
$token = $sql->escape($token);
extract($sql->single("SELECT count(1) as `valid`, `token_id`, `token`, `user_id` FROM `api_tokens` WHERE `token` = '$token'"));
if (!$valid) error(401, "Invalid token. Check your token and try again.");
if (!$token_id) error(401, "Invalid token. Check your token and try again.");

// Continue to Validate the request
$response = [];

// Validate the request method
if ($_SERVER["REQUEST_METHOD"] != "POST") error(400, "Invalid request method. Must be POST, not " . $_SERVER["REQUEST_METHOD"]);

// Validate the request body
$json_string_input = file_get_contents("php://input");

// Validate the JSON string
if (!$json_string_input) error(400, "No input data. You must POST a valid JSON string in the body.");
if (!($json_input = json_decode($json_string_input, true))) error(400, "The string in body cannot be decoded as JSON. Check your syntax and try again.");

// Validate the session
if (!isset($json_input["session"])) error(400, "No session specified. You must specify a session ID or 'new' to create a new session.");
if ($json_input["session"] == "new") {
    // Create a new session
    // Validate the collection
    if (!isset($json_input["collection"])) error(400, "No collection specified. You must specify a collection ID or 'new' to create a new collection.");
    if ($json_input["collection"] == "new") {
        // Create a new collection
        try {
            if (!isset($json_input["collection_config"])) error(400, "No collection config specified. You must specify a collection config.");
            $collection_config = $json_input["collection_config"];
            if (!is_array($collection_config)) error(400, "Invalid collection config. Must be an array.");
            $collection_name = isset($collection_config["name"]) ? $sql->escape($collection_config["name"]) : null;
            $sql->query("INSERT INTO `collections` (`collection_name`,`user_id`) VALUES ('$collection_name','$user_id')");
            $collection_id = $sql->insert_id();
            $response["collection_id"] = $collection_id;
            $sql->query("INSERT INTO `collections_api_tokens` (`collection_id`, `token_id`) VALUES ('$collection_id', '$token_id')");
            // if key_id isset
            if (isset($collection_config["key_id"])) {
                $key_id = $sql->escape($collection_config["key_id"]);
                $sql->query("UPDATE `collections` SET `key_id` = '$key_id' WHERE `collection_id` = '$collection_id'");
            }
            if (isset($collection_config["model"])) {
                $model = $sql->escape($collection_config["model"]);
                $sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '$collection_id'");
            }
            if (isset($collection_config["temperature"])) {
                $temperature = $sql->escape($collection_config["temperature"]);
                $sql->query("UPDATE `collections` SET `temperature` = '$temperature' WHERE `collection_id` = '$collection_id'");
            }
            // if max_tokens
            if (isset($collection_config["max_tokens"])) {
                $max_tokens = $sql->escape($collection_config["max_tokens"]);
                $sql->query("UPDATE `collections` SET `max_tokens` = '$max_tokens' WHERE `collection_id` = '$collection_id'");
            }
            // if top_p
            if (isset($collection_config["top_p"])) {
                $top_p = $sql->escape($collection_config["top_p"]);
                $sql->query("UPDATE `collections` SET `top_p` = '$top_p' WHERE `collection_id` = '$collection_id'");
            }
            // if frequency_penalty
            if (isset($collection_config["frequency_penalty"])) {
                $frequency_penalty = $sql->escape($collection_config["frequency_penalty"]);
                $sql->query("UPDATE `collections` SET `frequency_penalty` = '$frequency_penalty' WHERE `collection_id` = '$collection_id'");
            }
            // if presence_penalty
            if (isset($collection_config["presence_penalty"])) {
                $presence_penalty = $sql->escape($collection_config["presence_penalty"]);
                $sql->query("UPDATE `collections` SET `presence_penalty` = '$presence_penalty' WHERE `collection_id` = '$collection_id'");
            }
            // if stop_sequence
            if (isset($collection_config["stop_sequence"])) {
                $stop_sequence = $sql->escape($collection_config["stop_sequence"]);
                $sql->query("UPDATE `collections` SET `stop_sequence` = '$stop_sequence' WHERE `collection_id` = '$collection_id'");
            }
            $collection_config = $sql->single("SELECT * FROM `collections` WHERE `collection_id` = '$collection_id'");
        } catch (\Exception $e) {
            error(500, $e->getMessage());
        } catch (\Error $e) {
            error(500, $e->getMessage());
        } catch (\Throwable $e) {
            error(500, $e->getMessage());
        }
    } else {
        // Use an existing collection
        $collection_id = $json_input["collection"];
        if (!is_numeric($collection_id)) error(400, "Invalid collection ID. Must be numeric.");
        extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
        if (!$valid) error(401, "Invalid token for this collection. Check your token and collection IDs and try again.");
        $collection_config = $sql->single("SELECT * FROM `collections` WHERE `collection_id` = '$collection_id'");
        if (!$collection_config) error(400, "Invalid collection ID. Check your collection ID and try again.");
    }
    try {
        $sql->query("INSERT INTO `sessions` (`collection_id`) VALUES ('$collection_id')");
        $session_id = $sql->insert_id();
        $response["session_id"] = $session_id;
        if (isset($json_input["session_config"])) {
            $session_config = $json_input["session_config"];
            if (!is_array($session_config)) error(400, "Invalid session config. Must be an array.");
            // if key_id isset
            if (isset($session_config["key_id"])) {
                $key_id = $sql->escape($session_config["key_id"]);
                $sql->query("UPDATE `sessions` SET `key_id` = '$key_id' WHERE `session_id` = '$session_id'");
            }
            // if model
            if (isset($session_config["model"])) {
                $model = $sql->escape($session_config["model"]);
                $sql->query("UPDATE `sessions` SET `model` = '$model' WHERE `session_id` = '$session_id'");
            }
            // if max_tokens
            if (isset($session_config["max_tokens"])) {
                $max_tokens = $sql->escape($session_config["max_tokens"]);
                $sql->query("UPDATE `sessions` SET `max_tokens` = '$max_tokens' WHERE `session_id` = '$session_id'");
            }
            // if top_p
            if (isset($session_config["top_p"])) {
                $top_p = $sql->escape($session_config["top_p"]);
                $sql->query("UPDATE `sessions` SET `top_p` = '$top_p' WHERE `session_id` = '$session_id'");
            }
            // if frequency_penalty
            if (isset($session_config["frequency_penalty"])) {
                $frequency_penalty = $sql->escape($session_config["frequency_penalty"]);
                $sql->query("UPDATE `sessions` SET `frequency_penalty` = '$frequency_penalty' WHERE `session_id` = '$session_id'");
            }
            // if presence_penalty
            if (isset($session_config["presence_penalty"])) {
                $presence_penalty = $sql->escape($session_config["presence_penalty"]);
                $sql->query("UPDATE `sessions` SET `presence_penalty` = '$presence_penalty' WHERE `session_id` = '$session_id'");
            }
            // if stop_sequence
            if (isset($session_config["stop_sequence"])) {
                $stop_sequence = $sql->escape($session_config["stop_sequence"]);
                $sql->query("UPDATE `sessions` SET `stop_sequence` = '$stop_sequence' WHERE `session_id` = '$session_id'");
            }
        }
        $session_config = $sql->single("SELECT * FROM `sessions` WHERE `session_id` = '$session_id'");
    } catch (\Exception $e) {
        error(500, $e->getMessage());
    } catch (\Error $e) {
        error(500, $e->getMessage());
    } catch (\Throwable $e) {
        error(500, $e->getMessage());
    }
} else {
    $session_id = $json_input["session"];
    if (!is_numeric($session_id)) error(400, "Invalid session ID. Must be numeric.");
    $session_config = $sql->single("SELECT count(1) as `valid`, `collection_id` FROM `sessions` WHERE `session_id` = '$session_id'");
    if (!$session_config) error(400, "Invalid session ID. Check your session ID and try again.");
    $collection_id = $session_config["collection_id"];
    extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
    if (!$valid) error(401, "Invalid token for this session. Check your token and try again.");
}
$passthru = isset($json_input["passthru"]) && $json_input["passthru"] === true ? true : false;
if ($passthru) {
    require_once(__DIR__ . "/../../../OpenAIClient.php");
    $stream = isset($json_input["stream"]) && $json_input["stream"] === true ? true : false;
    if ($stream) {
        header('Content-Type: plain/text; charset=utf-8');
        echo ("Starting stream test...\n");
        for ($i = 0; $i < 10; $i++) {
            pad_echo("$i...");
            sleep(1);
        }
    } else {
        header('Content-Type: application/json; charset=utf-8');
        $response["result"] = "ok";
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    $response["result"] = "ok";
    $response["session"] = $session_id;
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
