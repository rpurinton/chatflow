<?php

namespace RPurinton\ChatFlow\v1;

use RPurinton\ChatFlow\SqlClient;
use RPurinton\ChatFlow\TikToken;

class Messages
{
    private $sql;
    private $encoder;
    private $response;
    private $token;
    private $token_id;
    private $passthru;
    private $stream;
    private $json_input;
    private $user_id;
    private $collection_id;
    private $session_id;
    private $user_config;
    private $collection_config;
    private $session_config;
    private $key_id;


    public function __construct()
    {
        $this->init();
        $this->collection();
        $this->collection_config();
        $this->session();
        $this->session_config();
        $this->save_configs();
        $this->save_messages();
        if ($this->passthru) $this->passthru();
        $this->respond();
    }

    private function init()
    {
        // Look for a token in the HTTP Authorization header
        // Validate the request method
        if ($_SERVER["REQUEST_METHOD"] != "POST") $this->error(400, "Invalid request method. Must be POST, not " . $_SERVER["REQUEST_METHOD"]);

        // Validate the request body
        $json_string_input = file_get_contents("php://input") or $this->error(400, "No input data. You must POST a valid JSON string in the body.");

        // Validate the JSON string
        if (!$json_string_input) $this->error(400, "No input data. You must POST a valid JSON string in the body.");
        if (!($this->json_input = json_decode($json_string_input, true))) $this->error(400, "The string in body cannot be decoded as JSON. Check your syntax and try again.");

        // Get some important variables
        $this->token = isset($this->json_input["token"]) ? $this->json_input["token"] : null;
        $this->passthru = isset($this->json_input["passthru"]) && $this->json_input["passthru"] === true ? true : false;
        $this->stream = isset($this->json_input["stream"]) && $this->json_input["stream"] === true ? true : false;

        // set response headers
        if ($this->stream) {
            $passthru = true;
            header("Content-Type: text/plain; charset=utf-8");
        } else header("Content-Type: application/json; charset=utf-8");

        if (!$this->token && !isset($_SERVER["HTTP_AUTHORIZATION"])) $this->error(400, "No token specified. You must either specify a token in the HTTP Authorization header or in the JSON body.");

        // Validate the token
        $token ??= trim(str_replace("Bearer", "", $_SERVER["HTTP_AUTHORIZATION"]));

        if (strlen($token) != 64) $this->error(400, "Invalid token length. Must be 64 characters.");
        if (substr($token, 0, 9) != "chatflow-") $this->error(400, "Invalid token prefix. Must be 'chatflow-...'.");

        // Connect to the database
        try {
            require_once(__DIR__ . "/../../../SqlClient.php");
            $this->sql = new SqlClient();

            // Continue to Validate the token
            $token = $this->sql->escape($token);
            extract($this->sql->single("SELECT count(1) as `valid`, `token_id`, `token`, `user_id` FROM `api_tokens` WHERE `token` = '$token'"));
            if (!$valid) $this->error(401, "Invalid token. Check your token and try again.");
            if (!$token_id) $this->error(401, "Invalid token. Check your token and try again.");
            if (!$this->user_id) $this->error(401, "Invalid token. Check your token and try again.");
            $this->token_id = $token_id;
            $this->user_id = $this->user_id;
        } catch (\Exception $e) {
            $this->error(500, $e->getMessage());
        } catch (\Error $e) {
            $this->error(500, $e->getMessage());
        } catch (\Throwable $e) {
            $this->error(500, $e->getMessage());
        }

        // init the encoder
        require_once(__DIR__ . "/../../../TikToken.php");
        $this->encoder = new TikToken();

        // init the response
        $this->response = [];
    }

    private function collection()
    {
        // collection related
        if (isset($this->json_input["collection"])) {
            if ($this->json_input["collection"] == "new") {
                // Create a new collection
                try {
                    if (!isset($this->json_input["collection_config"])) $this->error(400, "No collection config specified. You must specify a collection config.");
                    $collection_config = $this->json_input["collection_config"];
                    if (!is_array($collection_config)) $this->error(400, "Invalid collection config. Must be an array.");
                    $collection_name = isset($collection_config["name"]) ? $this->sql->escape($collection_config["name"]) : null;
                    $this->sql->query("INSERT INTO `collections` (`collection_name`,`user_id`) VALUES ('$collection_name','{$this->user_id}')");
                    $this->collection_id = $this->sql->insert_id();
                    $this->response["collection_id"] = strval($this->collection_id);
                    $this->sql->query("INSERT INTO `collections_api_tokens` (`collection_id`, `token_id`) VALUES ('{$this->collection_id}', '{$this->token_id}')");
                } catch (\Exception $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Error $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Throwable $e) {
                    $this->error(500, $e->getMessage());
                }
            } else {
                // Use an existing collection
                $this->collection_id = $this->json_input["collection"];
                if (!is_numeric($this->collection_id)) $this->error(400, "Invalid collection ID. Must be numeric.");
                try {
                    extract($this->sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '{$this->collection_id}' AND `token_id` = '$token_id'"));
                    if (!$valid) $this->error(401, "Invalid token for this collection. Check your token and collection ID and try again.");
                } catch (\Exception $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Error $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Throwable $e) {
                    $this->error(500, $e->getMessage());
                }
            }
        }
    }

    private function collection_config()
    {
        // collection_config related
        if (isset($this->json_input["collection_config"])) {
            if (!isset($this->collection_id)) $this->error(400, "You must specify a collection ID to update the collection config.");
            $collection_config = $this->json_input["collection_config"];
            try {
                // if name isset
                if (isset($collection_config["collection_name"])) {
                    if (!is_string($collection_config["name"])) $this->error(400, "Invalid collection config name. Must be a string.");
                    $collection_name = $this->sql->escape($collection_config["name"]);
                    $this->sql->query("UPDATE `collections` SET `collection_name` = '$collection_name' WHERE `collection_id` = '{$this->collection_id}'");
                }
                if (isset($collection_config["key_id"])) {
                    if (!is_numeric($collection_config["key_id"])) $this->error(400, "Invalid collection config key_id. Must be numeric.");
                    $this->key_id = $this->sql->escape($collection_config["key_id"]);
                    $this->sql->query("UPDATE `collections` SET `key_id` = '{$this->key_id}' WHERE `collection_id` = '{$this->collection_id}'");
                }
                if (isset($collection_config["model"])) {
                    if (!is_string($collection_config["model"])) $this->error(400, "Invalid collection config model. Must be a string.");
                    $model = $this->sql->escape($collection_config["model"]);
                    $this->sql->query("UPDATE `collections` SET `model` = '$model' WHERE `collection_id` = '{$this->collection_id}'");
                }
                if (isset($collection_config["temperature"])) {
                    if (!is_numeric($collection_config["temperature"])) $this->error(400, "Invalid collection config temperature. Must be a float.");
                    $temperature = $this->sql->escape($collection_config["temperature"]);
                    $this->sql->query("UPDATE `collections` SET `temperature` = '$temperature' WHERE `collection_id` = '{$this->collection_id}'");
                }
                // if max_tokens
                if (isset($collection_config["max_tokens"])) {
                    if (!is_int($collection_config["max_tokens"])) $this->error(400, "Invalid collection config max_tokens. Must be an integer.");
                    $max_tokens = $this->sql->escape($collection_config["max_tokens"]);
                    $this->sql->query("UPDATE `collections` SET `max_tokens` = '$max_tokens' WHERE `collection_id` = '{$this->collection_id}'");
                }
                // if top_p
                if (isset($collection_config["top_p"])) {
                    if (!is_numeric($collection_config["top_p"])) $this->error(400, "Invalid collection config top_p. Must be a float.");
                    $top_p = $this->sql->escape($collection_config["top_p"]);
                    $this->sql->query("UPDATE `collections` SET `top_p` = '$top_p' WHERE `collection_id` = '{$this->collection_id}'");
                }
                // if frequency_penalty
                if (isset($collection_config["frequency_penalty"])) {
                    if (!is_numeric($collection_config["frequency_penalty"])) $this->error(400, "Invalid collection config frequency_penalty. Must be a float.");
                    $frequency_penalty = $this->sql->escape($collection_config["frequency_penalty"]);
                    $this->sql->query("UPDATE `collections` SET `frequency_penalty` = '$frequency_penalty' WHERE `collection_id` = '{$this->collection_id}'");
                }

                // if presence_penalty
                if (isset($collection_config["presence_penalty"])) {
                    if (!is_numeric($collection_config["presence_penalty"])) $this->error(400, "Invalid collection config presence_penalty. Must be a float.");
                    $presence_penalty = $this->sql->escape($collection_config["presence_penalty"]);
                    $this->sql->query("UPDATE `collections` SET `presence_penalty` = '$presence_penalty' WHERE `collection_id` = '{$this->collection_id}'");
                }

                // if stop_sequence
                if (isset($collection_config["stop_sequence"])) {
                    if (!is_string($collection_config["stop_sequence"])) $this->error(400, "Invalid collection config stop_sequence. Must be a string.");
                    $stop_sequence = $this->sql->escape($collection_config["stop_sequence"]);
                    $this->sql->query("UPDATE `collections` SET `stop_sequence` = '$stop_sequence' WHERE `collection_id` = '{$this->collection_id}'");
                }
                if (isset($collection_config["messages"])) {
                    if (!is_array($collection_config["messages"])) $this->error(400, "Invalid collection config messages. Must be an array.");
                    foreach ($collection_config["messages"] as $message) {
                        $role = $message["role"];
                        if (!in_array($role, ["system", "user", "assistant", "function"])) $this->error(400, "Invalid collection config message role. Must be 'system', 'user', 'assistant', or 'function'.");
                        $content = $message["content"];
                        $token_count = $this->encoder->token_count($content);
                        $sql_content = $this->sql->escape($content);
                        $this->sql->query("INSERT INTO `collection_messages` (`collection_id`, `role`, `content`, `token_count`) VALUES ('{$this->collection_id}', '$role', '$sql_content', '$token_count')");
                        if (!isset($this->response["tokens_inserted"])) $this->response["tokens_inserted"] = $token_count;
                        else $this->response["tokens_inserted"] += $token_count;
                        if (isset($this->json_input["return_meta"]) && $this->json_input["return_meta"] === true) {
                            $message_id = $this->sql->insert_id();
                            extract($this->sql->single("SELECT `created_at` FROM `collection_messages` WHERE `message_id` = '$message_id'"));
                            $this->response["meta"][] = [
                                "message_id" => $message_id,
                                "collection_id" => $this->collection_id,
                                "role" => $role,
                                "content" => $content,
                                "token_count" => $token_count,
                                "created_at" => $created_at
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error(500, $e->getMessage());
            } catch (\Error $e) {
                $this->error(500, $e->getMessage());
            } catch (\Throwable $e) {
                $this->error(500, $e->getMessage());
            }
        }
    }

    private function session()
    {
        // session related
        if (isset($this->json_input["session"])) {
            if ($this->json_input["session"] == "new") {
                try {
                    if (!isset($this->collection_id)) $this->error(400, "You must specify a collection ID to create a new session.");
                    $this->sql->query("INSERT INTO `sessions` (`collection_id`) VALUES ('{$this->collection_id}')");
                    $this->session_id = $this->sql->insert_id();
                    $this->response["session_id"] = strval($this->session_id);
                } catch (\Exception $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Error $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Throwable $e) {
                    $this->error(500, $e->getMessage());
                }
            } else {
                $this->session_id = $this->json_input["session"];
                if (!is_numeric($this->session_id)) $this->error(400, "Invalid session ID. Must be numeric.");
                try {
                    $session_config = $this->sql->single("SELECT count(1) as `valid`, `collection_id` FROM `sessions` WHERE `session_id` = '{$this->session_id}'");
                    if (!$session_config["valid"]) $this->error(400, "Invalid session ID. Check your session ID and try again.");
                    $this->collection_id = $session_config["collection_id"];
                    extract($this->sql->single("SELECT count(1) as `valid` FROM `collections_api_tokens` WHERE `collection_id` = '{$this->collection_id}' AND `token_id` = '$token_id'"));
                    if (!$valid) $this->error(401, "Invalid token for this session. Check your token and try again.");
                } catch (\Exception $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Error $e) {
                    $this->error(500, $e->getMessage());
                } catch (\Throwable $e) {
                    $this->error(500, $e->getMessage());
                }
            }
        }
    }

    private function session_config()
    {
        // session_config related
        if (isset($this->json_input["session_config"])) {
            try {
                if (!isset($this->session_id)) $this->error(400, "You must specify a session ID to update the session config.");
                $session_config = $this->json_input["session_config"];
                if (!is_array($session_config)) $this->error(400, "Invalid session config. Must be an array.");
                // if key_id isset
                if (isset($session_config["key_id"])) {
                    $this->key_id = $this->sql->escape($session_config["key_id"]);
                    $this->sql->query("UPDATE `sessions` SET `key_id` = '{$this->key_id}' WHERE `session_id` = '{$this->session_id}'");
                }
                // if model
                if (isset($session_config["model"])) {
                    $model = $this->sql->escape($session_config["model"]);
                    $this->sql->query("UPDATE `sessions` SET `model` = '$model' WHERE `session_id` = '{$this->session_id}'");
                }
                // if max_tokens
                if (isset($session_config["max_tokens"])) {
                    $max_tokens = $this->sql->escape($session_config["max_tokens"]);
                    $this->sql->query("UPDATE `sessions` SET `max_tokens` = '$max_tokens' WHERE `session_id` = '{$this->session_id}'");
                }
                // if top_p
                if (isset($session_config["top_p"])) {
                    $top_p = $this->sql->escape($session_config["top_p"]);
                    $this->sql->query("UPDATE `sessions` SET `top_p` = '$top_p' WHERE `session_id` = '{$this->session_id}'");
                }
                // if frequency_penalty
                if (isset($session_config["frequency_penalty"])) {
                    $frequency_penalty = $this->sql->escape($session_config["frequency_penalty"]);
                    $this->sql->query("UPDATE `sessions` SET `frequency_penalty` = '$frequency_penalty' WHERE `session_id` = '{$this->session_id}'");
                }
                // if presence_penalty
                if (isset($session_config["presence_penalty"])) {
                    $presence_penalty = $this->sql->escape($session_config["presence_penalty"]);
                    $this->sql->query("UPDATE `sessions` SET `presence_penalty` = '$presence_penalty' WHERE `session_id` = '{$this->session_id}'");
                }
                // if stop_sequence
                if (isset($session_config["stop_sequence"])) {
                    $stop_sequence = $this->sql->escape($session_config["stop_sequence"]);
                    $this->sql->query("UPDATE `sessions` SET `stop_sequence` = '$stop_sequence' WHERE `session_id` = '{$this->session_id}'");
                }
            } catch (\Exception $e) {
                $this->error(500, $e->getMessage());
            } catch (\Error $e) {
                $this->error(500, $e->getMessage());
            } catch (\Throwable $e) {
                $this->error(500, $e->getMessage());
            }
        }
    }

    private function save_configs()
    {
        // save the configs
        $this->user_config = $this->sql->single("SELECT * FROM `users` WHERE `user_id` = '{$this->user_id}'");
        if (!$this->user_config) $this->error(401, "Invalid User Error Check your token and try again.");
        $this->collection_config = $this->sql->single("SELECT * FROM `collections` WHERE `collection_id` = '{$this->collection_id}'");
        if (!$this->collection_config) $this->error(400, "Invalid collection ID. Check your collection ID and try again.");
        $this->session_config = $this->sql->single("SELECT * FROM `sessions` WHERE `session_id` = '{$this->session_id}'");
        if (!$this->session_config) $this->error(400, "Invalid session ID. Check your session ID and try again.");
    }

    private function save_messages()
    {
        // save any new messages
        if (isset($this->json_input["messages"])) {
            try {
                if (!is_array($this->json_input["messages"])) $this->error(400, "Invalid messages object. Must be an array.");
                if (isset($this->json_input["session_config"]["messages"])) {
                    if (!is_array($this->json_input["session_config"]["messages"])) $this->error(400, "Invalid session config messages. Must be an array.");
                    $this->json_input["messages"] = array_merge($this->json_input["session_config"]["messages"], $this->json_input["messages"]);
                }
                foreach ($this->json_input["messages"] as $message) {
                    $role = $message["role"];
                    if (!in_array($role, ["system", "user", "assistant", "function"])) $this->error(400, "Invalid message role. Must be 'system', 'user', 'assistant', or 'function' but you had '$role'.");
                    $content = $message["content"];
                    $token_count = $this->encoder->token_count($content);
                    $sql_content = $this->sql->escape($content);
                    $this->sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('{$this->session_id}', '$role', '$sql_content', '$token_count')");
                    if (!isset($this->response["tokens_inserted"])) $this->response["tokens_inserted"] = $token_count;
                    else $this->response["tokens_inserted"] += $token_count;
                    if (isset($this->json_input["return_meta"]) && $this->json_input["return_meta"] === true) {
                        $message_id = $this->sql->insert_id();
                        extract($this->sql->single("SELECT `created_at` FROM `chat_messages` WHERE `message_id` = '$message_id'"));
                        $this->response["meta"][] = [
                            "message_id" => $message_id,
                            "session_id" => $this->session_id,
                            "role" => $role,
                            "content" => $content,
                            "token_count" => $token_count,
                            "created_at" => $created_at
                        ];
                    }
                }
            } catch (\Exception $e) {
                $this->error(500, $e->getMessage());
            } catch (\Error $e) {
                $this->error(500, $e->getMessage());
            } catch (\Throwable $e) {
                $this->error(500, $e->getMessage());
            }
        }
    }

    private function passthru()
    {
        try {
            if (!isset($this->json_input["prompt_tokens"])) $this->error(400, "When using passthru, you must specify prompt_tokens to send.");
            if (!is_int($this->json_input["prompt_tokens"]) || $this->json_input["prompt_tokens"] < 1) $this->error(400, "prompt_tokens must be a positive integer.");
            $prompt_tokens = $this->json_input["prompt_tokens"];
            require_once(__DIR__ . "/../../../OpenAIClient.php");

            // figuring out the settings
            if (true) {
                // decide what key to use
                switch (true) {
                    case isset($this->json_input["key"]):
                        $key = $this->json_input["key"];
                        if (!is_String($key) || strlen($key) != 51) $this->error(400, "Invalid key length. Must be a string 51 characters.");
                        //save the new key in the db
                        $sql_key = $this->sql->escape($key);
                        $this->sql->query("INSERT INTO `chatgpt_api_keys` (`user_id`,`key`) VALUES ('{$this->user_id}','$sql_key') ON DUPLICATE KEY UPDATE `key_id` = LAST_INSERT_ID(`key_id`)");
                        $this->key_id = $this->sql->insert_id();
                        break;
                    case isset($session_config["key_id"]):
                        $this->key_id = $session_config["key_id"];
                        break;
                    case isset($collection_config["key_id"]):
                        $this->key_id = $collection_config["key_id"];
                        break;
                    case isset($user_config["key_id"]):
                        $this->key_id = $user_config["key_id"];
                        break;
                }

                if (!isset($this->key_id)) $this->error(400, "No key specified. You must specify a key.");
                $key_result = $this->sql->single("SELECT `key` FROM `chatgpt_api_keys` WHERE `key_id` = '{$this->key_id}' AND `user_id` = '{$this->user_id}'");

                if (!$key_result) $this->error(400, "Invalid key. Check your key and try again.");
                $openai = \OpenAI::client($key_result["key"]);

                // decide what model to use
                switch (true) {
                    case isset($this->json_input["model"]):
                        $model = $this->json_input["model"];
                        if (!is_string($model)) $this->error(400, "Invalid model. Must be a string.");
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
                if (!isset($model)) $this->error(400, "No model specified. You must specify a model.");

                // decide what max_tokens to use
                switch (true) {
                    case isset($this->json_input["max_tokens"]):
                        $max_tokens = $this->json_input["max_tokens"];
                        if (!is_numeric($max_tokens)) $this->error(400, "Invalid max_tokens. Must be numeric.");
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
                    case isset($this->json_input["top_p"]):
                        $top_p = $this->json_input["top_p"];
                        if (!is_numeric($top_p)) $this->error(400, "Invalid top_p. Must be numeric.");
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
                    case isset($this->json_input["frequency_penalty"]):
                        $frequency_penalty = $this->json_input["frequency_penalty"];
                        if (!is_numeric($frequency_penalty)) $this->error(400, "Invalid frequency_penalty. Must be numeric.");
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
                    case isset($this->json_input["presence_penalty"]):
                        $presence_penalty = $this->json_input["presence_penalty"];
                        if (!is_numeric($presence_penalty)) $this->error(400, "Invalid presence_penalty. Must be numeric.");
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
                    case isset($this->json_input["stop_sequence"]):
                        $stop_sequence = $this->json_input["stop_sequence"];
                        if (!is_string($stop_sequence)) $this->error(400, "Invalid stop_sequence. Must be a string.");
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
                    case isset($this->json_input["temperature"]):
                        $temperature = $this->json_input["temperature"];
                        if (!is_numeric($temperature)) $this->error(400, "Invalid temperature. Must be numeric.");
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
                $collection_messages = $this->sql->query("SELECT `role`, `content`,`token_count` FROM `collection_messages` WHERE `collection_id` = '{$this->collection_id}' ORDER BY `message_id` ASC");
                while ($collection_message = $this->sql->assoc($collection_messages)) {
                    if ($prompt_tokens - $collection_message["token_count"] < 0) continue;
                    $prompt_tokens -= $collection_message["token_count"];
                    $prompt_messages[] = ["role" => $collection_message["role"], "content" => $collection_message["content"]];
                }
                // get the chat history 
                $chat_messages = $this->sql->query("SELECT `role`,`content`
                FROM (
                    SELECT `message_id`,`role`, `content`,
                    SUM(`token_count`) OVER (ORDER BY `created_at` DESC) AS `cumulative_token_count`
                    FROM `chat_messages`
                    WHERE `session_id` = '{$this->session_id}'
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

            if ($this->stream) {
                $full_response = "";
                $this->stream = $openai->chat()->createStreamed($prompt);
                foreach ($this->stream as $this->response) {
                    $reply = $this->response->choices[0]->toArray();
                    if (isset($reply["delta"]["content"])) {
                        $delta_content = $reply["delta"]["content"];
                        $full_response .= $delta_content;
                        $this->pad_echo($delta_content);
                    }
                }
                $token_count = $this->encoder->token_count($full_response);
                $sql_text = $this->sql->escape($full_response);
                $this->sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('{$this->session_id}', 'assistant', '$sql_text', '$token_count')");
                exit();
            } else {
                $ai_response = $openai->chat()->create($prompt);
                $this->response = array_merge($this->response, $ai_response->toArray());
                $full_response = $this->response["choices"][0]["message"]["content"];
                $token_count = $this->encoder->token_count($full_response);
                $sql_text = $this->sql->escape($full_response);
                $this->sql->query("INSERT INTO `chat_messages` (`session_id`, `role`, `content`, `token_count`) VALUES ('{$this->session_id}', 'assistant', '$sql_text', '$token_count')");
                if (!isset($this->response["tokens_inserted"])) $this->response["tokens_inserted"] = $token_count;
                else $this->response["tokens_inserted"] += $token_count;
                if (isset($this->json_input["return_meta"]) && $this->json_input["return_meta"] === true) {
                    $message_id = $this->sql->insert_id();
                    extract($this->sql->single("SELECT `created_at` FROM `collection_messages` WHERE `message_id` = '$message_id'"));
                    $this->response["meta"][] = [
                        "message_id" => $message_id,
                        "session_id" => $this->session_id,
                        "role" => "assistant",
                        "content" => $text,
                        "token_count" => $token_count,
                        "created_at" => $created_at
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->error(500, $e->getMessage());
        } catch (\Error $e) {
            $this->error(500, $e->getMessage());
        } catch (\Throwable $e) {
            $this->error(500, $e->getMessage());
        }
    }

    private function respond()
    {
        // non passthru responses
        $this->response["result"] = "ok";
        echo json_encode($this->response, JSON_PRETTY_PRINT);
    }

    private function error($code, $message = null)
    {
        if ($this->stream) {
            $this->pad_echo("Error[$code]: " . $message);
            exit();
        }
        $result = [];
        $result["result"] = "error";
        $result["code"] = $code;
        $result["error"] = $message;
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }

    private function pad_echo($str)
    {
        echo (str_pad($str, 4096, "\0"));
        flush();
        @ob_flush();
    }
}
