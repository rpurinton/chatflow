<?php
// Look for a token in the HTTP Authorization header

// initialization
if (true) {
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

    // save the user config
    $user_config = $sql->single("SELECT * FROM `users` WHERE `user_id` = '$user_id'");
}

// session and collection creation and configuration
if (true) {
    // Validate the request method
    if ($_SERVER["REQUEST_METHOD"] != "POST") error(400, "Invalid request method. Must be POST, not " . $_SERVER["REQUEST_METHOD"]);

    // Validate the request body
    $json_string_input = file_get_contents("php://input");

    // Validate the JSON string
    if (!$json_string_input) error(400, "No input data. You must POST a valid JSON string in the body.");
    if (!($json_input = json_decode($json_string_input, true))) error(400, "The string in body cannot be decoded as JSON. Check your syntax and try again.");

    // we may need to count tokens from this point on...
    require_once(__DIR__ . "/../../../TikToken.php");
    $encoder = new RPurinton\ChatFlow\TikToken();

    // Validate the session
    if (!isset($json_input["session"])) error(400, "No session specified. You must specify a session ID or 'new' to create a new session.");

    // create a variable to hold the responses
    $response = [];

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
                    if (!is_numeric($collection_config["key_id"])) error(400, "Invalid collection config key_id. Must be numeric.");
                    $key_id = $sql->escape($collection_config["key_id"]);
                    $sql->query("UPDATE `collections` SET `key_id` = '$key_id' WHERE `collection_id` = '$collection_id'");
                }
                if (isset($collection_config["model"])) {
                    if (!is_string($collection_config["model"])) error(400, "Invalid collection config model. Must be a string.");
                    $model = $sql->escape($collection_config["model"]);
                    $sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '$collection_id'");
                }
                if (isset($collection_config["temperature"])) {
                    if (!is_numeric($collection_config["temperature"])) error(400, "Invalid collection config temperature. Must be a float.");
                    $temperature = $sql->escape($collection_config["temperature"]);
                    $sql->query("UPDATE `collections` SET `temperature` = '$temperature' WHERE `collection_id` = '$collection_id'");
                }
                // if max_tokens
                if (isset($collection_config["max_tokens"])) {
                    if (!is_int($collection_config["max_tokens"])) error(400, "Invalid collection config max_tokens. Must be an integer.");
                    $max_tokens = $sql->escape($collection_config["max_tokens"]);
                    $sql->query("UPDATE `collections` SET `max_tokens` = '$max_tokens' WHERE `collection_id` = '$collection_id'");
                }
                // if top_p
                if (isset($collection_config["top_p"])) {
                    if (!is_numeric($collection_config["top_p"])) error(400, "Invalid collection config top_p. Must be a float.");
                    $top_p = $sql->escape($collection_config["top_p"]);
                    $sql->query("UPDATE `collections` SET `top_p` = '$top_p' WHERE `collection_id` = '$collection_id'");
                }
                // if frequency_penalty
                if (isset($collection_config["frequency_penalty"])) {
                    if (!is_numeric($collection_config["frequency_penalty"])) error(400, "Invalid collection config frequency_penalty. Must be a float.");
                    $frequency_penalty = $sql->escape($collection_config["frequency_penalty"]);
                    $sql->query("UPDATE `collections` SET `frequency_penalty` = '$frequency_penalty' WHERE `collection_id` = '$collection_id'");
                }
                // if presence_penalty
                if (isset($collection_config["presence_penalty"])) {
                    if (!is_numeric($collection_config["presence_penalty"])) error(400, "Invalid collection config presence_penalty. Must be a float.");
                    $presence_penalty = $sql->escape($collection_config["presence_penalty"]);
                    $sql->query("UPDATE `collections` SET `presence_penalty` = '$presence_penalty' WHERE `collection_id` = '$collection_id'");
                }
                // if stop_sequence
                if (isset($collection_config["stop_sequence"])) {
                    if (!is_string($collection_config["stop_sequence"])) error(400, "Invalid collection config stop_sequence. Must be a string.");
                    $stop_sequence = $sql->escape($collection_config["stop_sequence"]);
                    $sql->query("UPDATE `collections` SET `stop_sequence` = '$stop_sequence' WHERE `collection_id` = '$collection_id'");
                }
                if (isset($collection_config["messages"])) {
                    if (!is_array($collection_config["messages"])) error(400, "Invalid collection config messages. Must be an array.");
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
            try {
                extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
                if (!$valid) error(401, "Invalid token for this collection. Check your token and collection IDs and try again.");
                if (isset($json_input["collection_config"])) {
                    $collection_config = $json_input["collection_config"];
                    if (!is_array($collection_config)) error(400, "Invalid collection config. Must be an array.");
                    // if key_id isset
                    if (isset($collection_config["key_id"])) {
                        $key_id = $sql->escape($collection_config["key_id"]);
                        $sql->query("UPDATE `collections` SET `key_id` = '$key_id' WHERE `collection_id` = '$collection_id'");
                    }
                    // if model
                    if (isset($collection_config["model"])) {
                        $model = $sql->escape($collection_config["model"]);
                        $sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '$collection_id'");
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
                    if (isset($collection_config["messages"])) {
                        if (!is_array($collection_config["messages"])) error(400, "Invalid collection config messages. Must be an array.");
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
                }
                $collection_config = $sql->single("SELECT * FROM `collections` WHERE `collection_id` = '$collection_id'");
                if (!$collection_config) error(400, "Invalid collection ID. Check your collection ID and try again.");
            } catch (\Exception $e) {
                error(500, $e->getMessage());
            } catch (\Error $e) {
                error(500, $e->getMessage());
            } catch (\Throwable $e) {
                error(500, $e->getMessage());
            }
        }
        // Create a new session
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
        try {
            $session_config = $sql->single("SELECT count(1) as `valid`, `collection_id` FROM `sessions` WHERE `session_id` = '$session_id'");
            if (!$session_config) error(400, "Invalid session ID. Check your session ID and try again.");
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
                $session_config = $sql->single("SELECT * FROM `sessions` WHERE `session_id` = '$session_id'");
            }
            $collection_id = $session_config["collection_id"];
            extract($sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '$collection_id' AND `token_id` = '$token_id'"));
            if (!$valid) error(401, "Invalid token for this session. Check your token and try again.");
            // update the collection_config if set
            if (isset($json_input["collection_config"])) {
                $collection_config = $json_input["collection_config"];
                if (!is_array($collection_config)) error(400, "Invalid collection config. Must be an array.");
                // if key_id isset
                if (isset($collection_config["key_id"])) {
                    $key_id = $sql->escape($collection_config["key_id"]);
                    $sql->query("UPDATE `collections` SET `key_id` = '$key_id' WHERE `collection_id` = '$collection_id'");
                }
                // if model
                if (isset($collection_config["model"])) {
                    $model = $sql->escape($collection_config["model"]);
                    $sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '$collection_id'");
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
                if (isset($collection_config["messages"])) {
                    if (!is_array($collection_config["messages"])) error(400, "Invalid collection config messages. Must be an array.");
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
            }
            $collection_config = $sql->single("SELECT * FROM `collections` WHERE `collection_id` = '$collection_id'");
        } catch (\Exception $e) {
            error(500, $e->getMessage());
        } catch (\Error $e) {
            error(500, $e->getMessage());
        } catch (\Throwable $e) {
            error(500, $e->getMessage());
        }
    }
}

// save any new messages
if (isset($json_input["messages"])) {
    if (!is_array($json_input["messages"])) error(400, "Invalid messages object. Must be an array.");
    if (isset($json_input["session_config"]["messages"])) {
        if (!is_array($json_input["session_config"]["messages"])) error(400, "Invalid session config messages. Must be an array.");
        $json_input["messages"] = array_merge($json_input["session_config"]["messages"], $json_input["messages"]);
    }
    foreach ($json_input["messages"] as $message) {
        $role = $message["role"];
        if (!in_array($role, ["system", "user", "assistant", "function"])) error(400, "Invalid message role. Must be 'system', 'user', 'assistant', or 'function' but you had '$role'.");
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
}

// passthru to OpenAI option
if (true) {
    $passthru = isset($json_input["passthru"]) && $json_input["passthru"] === true ? true : false;
    if (!isset($json_input["prompt_tokens"])) die("When using passthru, you must specify prompt_tokens to send.");
    if (!is_int($json_input["prompt_tokens"]) || $json_input["prompt_tokens"] < 1) die("prompt_tokens must be a positive integer.");
    $prompt_tokens = $json_input["prompt_tokens"];
    if ($passthru) {
        require_once(__DIR__ . "/../../../OpenAIClient.php");
        // decisions
        if (true) {
            // decide what key to use
            switch (true) {
                case isset($json_input["key"]):
                    $key = $json_input["key"];
                    if (!is_String($key) || strlen($key) != 51) error(400, "Invalid key length. Must be a string 51 characters.");
                    //save the new key in the db
                    $sql_key = $sql->escape($key);
                    $key_id = $sql->insert("INSERT INTO `chatgpt_api_keys` (`user_id`,`key`) VALUES ('$user_id','$sql_key')");
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
            print_r($prompt_messages);
            // get the chat history 
            $chat_messages = $sql->query("SELECT *
                FROM (
                    SELECT `role`, `content`,
                    SUM(`token_count`) OVER (ORDER BY `created_at` DESC) AS `cumulative_token_count`
                    FROM `chat_messages`
                    WHERE `session_id` = '$session_id'
                ) AS temp
                WHERE cumulative_token_count <= 512");
            while ($chat_message = $chat_messages->fetch_assoc()) $prompt_messages[] = ["role" => $chat_message["role"], "content" => $chat_message["content"]];
            print_r($prompt_messages);
            $prompt = [
                'model' => $model,
                'messages' => $prompt_messages,
                'temperature' => $temperature,
                'top_p' => $top_p,
                'frequency_penalty' => $frequency_penalty,
                'presence_penalty' => $presence_penalty
            ];
        }


        $stream = isset($json_input["stream"]) && $json_input["stream"] === true ? true : false;
        if ($stream) {
            try {
                header('Content-Type: plain/text; charset=utf-8');
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
            } catch (\Exception $e) {
                die($e->getMessage());
            } catch (\Error $e) {
                die($e->getMessage());
            } catch (\Throwable $e) {
                die($e->getMessage());
            }
        } else {
            try {
                $ai_response = $openai->chat()->create($prompt);
                $response = array_merge($response, $ai_response->toArray());
                $full_response = $response["choices"][0]["text"];
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
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response, JSON_PRETTY_PRINT);
                exit();
            } catch (\Exception $e) {
                error(500, $e->getMessage());
            } catch (\Error $e) {
                error(500, $e->getMessage());
            } catch (\Throwable $e) {
                error(500, $e->getMessage());
            }
        }
    }
}

// non passthru responses
header('Content-Type: application/json; charset=utf-8');
$response["result"] = "ok";
echo json_encode($response, JSON_PRETTY_PRINT);

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
