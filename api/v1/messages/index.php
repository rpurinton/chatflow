<?php
// initialization
if (true) {
    // Look for a token in the HTTP Authorization header
    // Validate the request method
    if ($_SERVER["REQUEST_METHOD"] != "POST") error(400, "Invalid request method. Must be POST, not " . $_SERVER["REQUEST_METHOD"]);

    // Validate the request body
    $json_string_input = file_get_contents("php://input") or error(400, "No input data. You must POST a valid JSON string in the body.");

    // Validate the JSON string
    if (!$json_string_input) error(400, "No input data. You must POST a valid JSON string in the body.");
    if (!($json_input = json_decode($json_string_input, true))) error(400, "The string in body cannot be decoded as JSON. Check your syntax and try again.");

    // Get some important variables
    $token = isset($json_input["token"]) ? $json_input["token"] : null;
    $passthru = isset($json_input["passthru"]) && $json_input["passthru"] === true ? true : false;
    $stream = isset($json_input["stream"]) && $json_input["stream"] === true ? true : false;
    if ($stream) {
        $passthru = true;
        header("Content-Type: text/plain; charset=utf-8");
    } else header("Content-Type: application/json; charset=utf-8");

    if (!$token && !isset($_SERVER["HTTP_AUTHORIZATION"])) error(400, "No token specified. You must either specify a token in the HTTP Authorization header or in the JSON body.", $stream);

    // Validate the token
    $token ??= trim(str_replace("Bearer", "", $_SERVER["HTTP_AUTHORIZATION"]));

    if (strlen($token) != 64) error(400, "Invalid token length. Must be 64 characters.", $stream);
    if (substr($token, 0, 9) != "chatflow-") error(400, "Invalid token prefix. Must be 'chatflow-...'.", $stream);

    // Connect to the database
    try {
        require_once(__DIR__ . "/../../../SqlClient.php");
        $sql = new RPurinton\ChatFlow\SqlClient();

        // Continue to Validate the token
        $token = $sql->escape($token);
        extract($sql->single("SELECT count(1) as `valid`, `token_id`, `token`, `user_id` FROM `api_tokens` WHERE `token` = '$token'"));
        if (!$valid) error(401, "Invalid token. Check your token and try again.", $stream);
        if (!$token_id) error(401, "Invalid token. Check your token and try again.", $stream);
    } catch (\Exception $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Error $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Throwable $e) {
        error(500, $e->getMessage(), $stream);
    }

    // init the encoder
    require_once(__DIR__ . "/../../../TikToken.php");
    $encoder = new RPurinton\ChatFlow\TikToken();

    // init the response
    $response = [];
}

// collection related
if (isset($json_input["collection"])) {
    if ($json_input["collection"] == "new") {
        // Create a new collection
        try {
            if (!isset($json_input["collection_config"])) error(400, "No collection config specified. You must specify a collection config.", $stream);
            $collection_config = $json_input["collection_config"];
            if (!is_array($collection_config)) error(400, "Invalid collection config. Must be an array.", $stream);
            $collection_name = isset($collection_config["name"]) ? $sql->escape($collection_config["name"]) : null;
            $sql->query("INSERT INTO `collections` (`collection_name`,`user_id`) VALUES ('$collection_name','$user_id')");
            $collection_id = $sql->insert_id();
            $response["collection_id"] = strval($collection_id);
            $sql->query("INSERT INTO `collections_api_tokens` (`collection_id`, `token_id`) VALUES ('$collection_id', '$token_id')");
        } catch (\Exception $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Error $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Throwable $e) {
            error(500, $e->getMessage(), $stream);
        }
    } else {
        // Use an existing collection
        $collection_id = $json_input["collection"];
        if (!is_numeric($collection_id)) error(400, "Invalid collection ID. Must be numeric.", $stream);
        try {
            extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
            if (!$valid) error(401, "Invalid token for this collection. Check your token and collection ID and try again.", $stream);
        } catch (\Exception $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Error $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Throwable $e) {
            error(500, $e->getMessage(), $stream);
        }
    }
}

// collection_config related
if (isset($json_input["collection_config"])) {
    if (!isset($collection_id)) error(400, "You must specify a collection ID to update the collection config.", $stream);
    $collection_config = $json_input["collection_config"];
    try {
        // if name isset
        if (isset($collection_config["collection_name"])) {
            if (!is_string($collection_config["name"])) error(400, "Invalid collection config name. Must be a string.", $stream);
            $collection_name = $sql->escape($collection_config["name"]);
            $sql->query("UPDATE `collections` SET `collection_name` = '$collection_name' WHERE `collection_id` = '$collection_id'");
        }
        if (isset($collection_config["key_id"])) {
            if (!is_numeric($collection_config["key_id"])) error(400, "Invalid collection config key_id. Must be numeric.", $stream);
            $key_id = $sql->escape($collection_config["key_id"]);
            $sql->query("UPDATE `collections` SET `key_id` = '$key_id' WHERE `collection_id` = '$collection_id'");
        }
        if (isset($collection_config["model"])) {
            if (!is_string($collection_config["model"])) error(400, "Invalid collection config model. Must be a string.", $stream);
            $model = $sql->escape($collection_config["model"]);
            $sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '$collection_id'");
        }
        if (isset($collection_config["temperature"])) {
            if (!is_numeric($collection_config["temperature"])) error(400, "Invalid collection config temperature. Must be a float.", $stream);
            $temperature = $sql->escape($collection_config["temperature"]);
            $sql->query("UPDATE `collections` SET `temperature` = '$temperature' WHERE `collection_id` = '$collection_id'");
        }
        // if max_tokens
        if (isset($collection_config["max_tokens"])) {
            if (!is_int($collection_config["max_tokens"])) error(400, "Invalid collection config max_tokens. Must be an integer.", $stream);
            $max_tokens = $sql->escape($collection_config["max_tokens"]);
            $sql->query("UPDATE `collections` SET `max_tokens` = '$max_tokens' WHERE `collection_id` = '$collection_id'");
        }
        // if top_p
        if (isset($collection_config["top_p"])) {
            if (!is_numeric($collection_config["top_p"])) error(400, "Invalid collection config top_p. Must be a float.", $stream);
            $top_p = $sql->escape($collection_config["top_p"]);
            $sql->query("UPDATE `collections` SET `top_p` = '$top_p' WHERE `collection_id` = '$collection_id'");
        }
        // if frequency_penalty
        if (isset($collection_config["frequency_penalty"])) {
            if (!is_numeric($collection_config["frequency_penalty"])) error(400, "Invalid collection config frequency_penalty. Must be a float.", $stream);
            $frequency_penalty = $sql->escape($collection_config["frequency_penalty"]);
            $sql->query("UPDATE `collections` SET `frequency_penalty` = '$frequency_penalty' WHERE `collection_id` = '$collection_id'");
        }

        // if presence_penalty
        if (isset($collection_config["presence_penalty"])) {
            if (!is_numeric($collection_config["presence_penalty"])) error(400, "Invalid collection config presence_penalty. Must be a float.", $stream);
            $presence_penalty = $sql->escape($collection_config["presence_penalty"]);
            $sql->query("UPDATE `collections` SET `presence_penalty` = '$presence_penalty' WHERE `collection_id` = '$collection_id'");
        }

        // if stop_sequence
        if (isset($collection_config["stop_sequence"])) {
            if (!is_string($collection_config["stop_sequence"])) error(400, "Invalid collection config stop_sequence. Must be a string.", $stream);
            $stop_sequence = $sql->escape($collection_config["stop_sequence"]);
            $sql->query("UPDATE `collections` SET `stop_sequence` = '$stop_sequence' WHERE `collection_id` = '$collection_id'");
        }
        if (isset($collection_config["messages"])) {
            if (!is_array($collection_config["messages"])) error(400, "Invalid collection config messages. Must be an array.", $stream);
            foreach ($collection_config["messages"] as $message) {
                $role = $message["role"];
                if (!in_array($role, ["system", "user", "assistant", "function"])) error(400, "Invalid collection config message role. Must be 'system', 'user', 'assistant', or 'function'.");
                $content = $message["content"];
                $token_count = $encoder->token_count($content);
                $sql_content = $sql->escape($content);
                $sql->query("INSERT INTO `collection_messages` (`collection_id`, `role`, `content`, `token_count`) VALUES ('$collection_id', '$role', '$sql_content', '$token_count')");
                if (!isset($response["tokens_inserted"])) $response["tokens_inserted"] = $token_count;
                else $response["tokens_inserted"] += $token_count;
                if (isset($json_input["return_meta"]) && $json_input["return_meta"] === true) {
                    $message_id = $sql->insert_id();
                    extract($sql->single("SELECT `created_at` FROM `collection_messages` WHERE `message_id` = '$message_id'"));
                    $response["meta"][] = [
                        "message_id" => $message_id,
                        "collection_id" => $collection_id,
                        "role" => $role,
                        "content" => $content,
                        "token_count" => $token_count,
                        "created_at" => $created_at
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Error $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Throwable $e) {
        error(500, $e->getMessage(), $stream);
    }
}

// session related
if (isset($json_input["session"])) {
    if ($json_input["session"] == "new") {
        try {
            if (!isset($collection_id)) error(400, "You must specify a collection ID to create a new session.", $stream);
            $sql->query("INSERT INTO `sessions` (`collection_id`) VALUES ('$collection_id')");
            $session_id = $sql->insert_id();
            $response["session_id"] = strval($session_id);
        } catch (\Exception $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Error $e) {
            error(500, $e->getMessage(), $stream);
        } catch (\Throwable $e) {
            error(500, $e->getMessage(), $stream);
        }
    } else {
        $session_id = $json_input["session"];
        if (!is_numeric($session_id)) error(400, "Invalid session ID. Must be numeric.", $stream);
        try {
            $session_config = $sql->single("SELECT count(1) as `valid`, `collection_id` FROM `sessions` WHERE `session_id` = '$session_id'");
            if (!$session_config["valid"]) error(400, "Invalid session ID. Check your session ID and try again.", $stream);
            $collection_id = $session_config["collection_id"];
            extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
            if (!$valid) error(401, "Invalid token for this session. Check your token and try again.", $stream);
        } catch (\Exception $e) {
            error(500, $e->getMessage());
        } catch (\Error $e) {
            error(500, $e->getMessage());
        } catch (\Throwable $e) {
            error(500, $e->getMessage());
        }
    }
}

// session_config related
if (isset($json_input["session_config"])) {
    try {
        if (!isset($session_id)) error(400, "You must specify a session ID to update the session config.", $stream);
        $session_config = $json_input["session_config"];
        if (!is_array($session_config)) error(400, "Invalid session config. Must be an array.", $stream);
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
    } catch (\Exception $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Error $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Throwable $e) {
        error(500, $e->getMessage(), $stream);
    }
}

// save the configs
$user_config = $sql->single("SELECT * FROM `users` WHERE `user_id` = '$user_id'");
if (!$user_config) error(401, "Invalid User Error Check your token and try again.", $stream);
$collection_config = $sql->single("SELECT * FROM `collections` WHERE `collection_id` = '$collection_id'");
if (!$collection_config) error(400, "Invalid collection ID. Check your collection ID and try again.", $stream);
$session_config = $sql->single("SELECT * FROM `sessions` WHERE `session_id` = '$session_id'");
if (!$session_config) error(400, "Invalid session ID. Check your session ID and try again.", $stream);

// save any new messages
if (isset($json_input["messages"])) {
    try {
        if (!is_array($json_input["messages"])) error(400, "Invalid messages object. Must be an array.", $stream);
        if (isset($json_input["session_config"]["messages"])) {
            if (!is_array($json_input["session_config"]["messages"])) error(400, "Invalid session config messages. Must be an array.", $stream);
            $json_input["messages"] = array_merge($json_input["session_config"]["messages"], $json_input["messages"]);
        }
        foreach ($json_input["messages"] as $message) {
            $role = $message["role"];
            if (!in_array($role, ["system", "user", "assistant", "function"])) error(400, "Invalid message role. Must be 'system', 'user', 'assistant', or 'function' but you had '$role'.", $stream);
            $content = $message["content"];
            $token_count = $encoder->token_count($content);
            $sql_content = $sql->escape($content);
            $sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('$session_id', '$role', '$sql_content', '$token_count')");
            if (!isset($response["tokens_inserted"])) $response["tokens_inserted"] = $token_count;
            else $response["tokens_inserted"] += $token_count;
            if (isset($json_input["return_meta"]) && $json_input["return_meta"] === true) {
                $message_id = $sql->insert_id();
                extract($sql->single("SELECT `created_at` FROM `chat_messages` WHERE `message_id` = '$message_id'"));
                $response["meta"][] = [
                    "message_id" => $message_id,
                    "session_id" => $session_id,
                    "role" => $role,
                    "content" => $content,
                    "token_count" => $token_count,
                    "created_at" => $created_at
                ];
            }
        }
    } catch (\Exception $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Error $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Throwable $e) {
        error(500, $e->getMessage(), $stream);
    }
}

// passthru to OpenAI option
if ($passthru) {
    try {
        if (!isset($json_input["prompt_tokens"])) error(400, "When using passthru, you must specify prompt_tokens to send.", $stream);
        if (!is_int($json_input["prompt_tokens"]) || $json_input["prompt_tokens"] < 1) error(400, "prompt_tokens must be a positive integer.", $stream);
        $prompt_tokens = $json_input["prompt_tokens"];
        require_once(__DIR__ . "/../../../OpenAIClient.php");

        // figuring out the settings
        if (true) {
            // decide what key to use
            switch (true) {
                case isset($json_input["key"]):
                    $key = $json_input["key"];
                    if (!is_String($key) || strlen($key) != 51) error(400, "Invalid key length. Must be a string 51 characters.", $stream);
                    //save the new key in the db
                    $sql_key = $sql->escape($key);
                    $sql->query("INSERT INTO `chatgpt_api_keys` (`user_id`,`key`) VALUES ('$user_id','$sql_key') ON DUPLICATE KEY UPDATE `key_id` = LAST_INSERT_ID(`key_id`)");
                    $key_id = $sql->insert_id();
                    break;
                case isset($session_config["key_id"]):
                    $key_id = $session_config["key_id"];
                    break;
                case isset($collection_config["key_id"]):
                    $key_id = $collection_config["key_id"];
                    break;
                case isset($user_config["key_id"]):
                    $key_id = $user_config["key_id"];
                    break;
            }

            if (!isset($key_id)) error(400, "No key specified. You must specify a key.");
            $key_result = $sql->single("SELECT `key` FROM `chatgpt_api_keys` WHERE `key_id` = '$key_id' AND `user_id` = '$user_id'");

            if (!$key_result) error(400, "Invalid key. Check your key and try again.");
            $openai = \OpenAI::client($key_result["key"]);

            // decide what model to use
            switch (true) {
                case isset($json_input["model"]):
                    $model = $json_input["model"];
                    if (!is_string($model)) error(400, "Invalid model. Must be a string.");
                    break;
                case isset($session_config["model"]):
                    $model = $session_config["model"];
                    break;
                case isset($collection_config["model"]):
                    $model = $collection_config["model"];
                    break;
                case isset($user_config["model"]):
                    $model = $user_config["model"];
                    break;
            }
            if (!isset($model)) error(400, "No model specified. You must specify a model.");

            // decide what max_tokens to use
            switch (true) {
                case isset($json_input["max_tokens"]):
                    $max_tokens = $json_input["max_tokens"];
                    if (!is_numeric($max_tokens)) error(400, "Invalid max_tokens. Must be numeric.");
                    break;
                case isset($session_config["max_tokens"]):
                    $max_tokens = $session_config["max_tokens"];
                    break;
                case isset($collection_config["max_tokens"]):
                    $max_tokens = $collection_config["max_tokens"];
                    break;
                case isset($user_config["max_tokens"]):
                    $max_tokens = $user_config["max_tokens"];
                    break;
            }

            // decide what top_p to use
            switch (true) {
                case isset($json_input["top_p"]):
                    $top_p = $json_input["top_p"];
                    if (!is_numeric($top_p)) error(400, "Invalid top_p. Must be numeric.");
                    break;
                case isset($session_config["top_p"]):
                    $top_p = $session_config["top_p"];
                    break;
                case isset($collection_config["top_p"]):
                    $top_p = $collection_config["top_p"];
                    break;
                case isset($user_config["top_p"]):
                    $top_p = $user_config["top_p"];
                    break;
            }

            // decide what frequency_penalty to use
            switch (true) {
                case isset($json_input["frequency_penalty"]):
                    $frequency_penalty = $json_input["frequency_penalty"];
                    if (!is_numeric($frequency_penalty)) error(400, "Invalid frequency_penalty. Must be numeric.");
                    break;
                case isset($session_config["frequency_penalty"]):
                    $frequency_penalty = $session_config["frequency_penalty"];
                    break;
                case isset($collection_config["frequency_penalty"]):
                    $frequency_penalty = $collection_config["frequency_penalty"];
                    break;
                case isset($user_config["frequency_penalty"]):
                    $frequency_penalty = $user_config["frequency_penalty"];
                    break;
            }

            // decide what presence_penalty to use
            switch (true) {
                case isset($json_input["presence_penalty"]):
                    $presence_penalty = $json_input["presence_penalty"];
                    if (!is_numeric($presence_penalty)) error(400, "Invalid presence_penalty. Must be numeric.");
                    break;
                case isset($session_config["presence_penalty"]):
                    $presence_penalty = $session_config["presence_penalty"];
                    break;
                case isset($collection_config["presence_penalty"]):
                    $presence_penalty = $collection_config["presence_penalty"];
                    break;
                case isset($user_config["presence_penalty"]):
                    $presence_penalty = $user_config["presence_penalty"];
                    break;
            }

            // decide what stop_sequence to use
            switch (true) {
                case isset($json_input["stop_sequence"]):
                    $stop_sequence = $json_input["stop_sequence"];
                    if (!is_string($stop_sequence)) error(400, "Invalid stop_sequence. Must be a string.");
                    break;
                case isset($session_config["stop_sequence"]):
                    $stop_sequence = $session_config["stop_sequence"];
                    break;
                case isset($collection_config["stop_sequence"]):
                    $stop_sequence = $collection_config["stop_sequence"];
                    break;
                case isset($user_config["stop_sequence"]):
                    $stop_sequence = $user_config["stop_sequence"];
                    break;
            }

            // decide what temperature
            switch (true) {
                case isset($json_input["temperature"]):
                    $temperature = $json_input["temperature"];
                    if (!is_numeric($temperature)) error(400, "Invalid temperature. Must be numeric.");
                    break;
                case isset($session_config["temperature"]):
                    $temperature = $session_config["temperature"];
                    break;
                case isset($collection_config["temperature"]):
                    $temperature = $collection_config["temperature"];
                    break;
                case isset($user_config["temperature"]):
                    $temperature = $user_config["temperature"];
                    break;
            }
        }

        // setup the prompt
        if (true) {
            $prompt_messages = [];
            // get any collection messages
            $collection_messages = $sql->query("SELECT `role`, `content`,`token_count` FROM `collection_messages` WHERE `collection_id` = '$collection_id' ORDER BY `message_id` ASC");
            while ($collection_message = $sql->assoc($collection_messages)) {
                if ($prompt_tokens - $collection_message["token_count"] < 0) continue;
                $prompt_tokens -= $collection_message["token_count"];
                $prompt_messages[] = ["role" => $collection_message["role"], "content" => $collection_message["content"]];
            }
            // get the chat history 
            $chat_messages = $sql->query("SELECT `role`,`content`
            FROM (
                SELECT `message_id`,`role`, `content`,
                SUM(`token_count`) OVER (ORDER BY `created_at` DESC) AS `cumulative_token_count`
                FROM `chat_messages`
                WHERE `session_id` = '$session_id'
                ORDER BY `message_id` DESC
            ) AS temp
            WHERE cumulative_token_count <= '$prompt_tokens'
            ORDER BY `message_id` ASC;");
            while ($chat_message = $chat_messages->fetch_assoc()) $prompt_messages[] = ["role" => $chat_message["role"], "content" => $chat_message["content"]];
            $prompt = [
                'model' => $model,
                'messages' => $prompt_messages
            ];
            if (isset($temperature)) $prompt["temperature"] = floatval($temperature);
            if (isset($top_p)) $prompt["top_p"] = floatval($top_p);
            if (isset($frequency_penalty)) $prompt["frequency_penalty"] = floatval($frequency_penalty);
            if (isset($presence_penalty)) $prompt["presence_penalty"] = floatval($presence_penalty);
            if (isset($stop_sequence)) $prompt["stop"] = $stop_sequence;
            if (isset($max_tokens)) $prompt["max_tokens"] = intval($max_tokens);
        }

        if ($stream) {
            $full_response = "";
            $stream = $openai->chat()->createStreamed($prompt);
            foreach ($stream as $response) {
                $reply = $response->choices[0]->toArray();
                if (isset($reply["delta"]["content"])) {
                    $delta_content = $reply["delta"]["content"];
                    $full_response .= $delta_content;
                    pad_echo($delta_content);
                }
            }
            $token_count = $encoder->token_count($full_response);
            $sql_text = $sql->escape($full_response);
            $sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('$session_id', 'assistant', '$sql_text', '$token_count')");
            exit();
        } else {
            $ai_response = $openai->chat()->create($prompt);
            $response = array_merge($response, $ai_response->toArray());
            $full_response = $response["choices"][0]["message"]["content"];
            $token_count = $encoder->token_count($full_response);
            $sql_text = $sql->escape($full_response);
            $sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('$session_id', 'assistant', '$sql_text', '$token_count')");
            if (!isset($response["tokens_inserted"])) $response["tokens_inserted"] = $token_count;
            else $response["tokens_inserted"] += $token_count;
            if (isset($json_input["return_meta"]) && $json_input["return_meta"] === true) {
                $message_id = $sql->insert_id();
                extract($sql->single("SELECT `created_at` FROM `collection_messages` WHERE `message_id` = '$message_id'"));
                $response["meta"][] = [
                    "message_id" => $message_id,
                    "session_id" => $session_id,
                    "role" => "assistant",
                    "content" => $text,
                    "token_count" => $token_count,
                    "created_at" => $created_at
                ];
            }
            $response["result"] = "ok";
            echo json_encode($response, JSON_PRETTY_PRINT);
            exit();
        }
    } catch (\Exception $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Error $e) {
        error(500, $e->getMessage(), $stream);
    } catch (\Throwable $e) {
        error(500, $e->getMessage(), $stream);
    }
}

// non passthru responses
$response["result"] = "ok";
echo json_encode($response, JSON_PRETTY_PRINT);

function error($code, $message = null, $stream = false)
{
    if ($stream) {
        pad_echo("Error[$code]: " . $message);
        exit();
    }
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
