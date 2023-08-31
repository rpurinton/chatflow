<?php

// here is a really verbose example of how to use the API


// location of the config file
$json_file = __DIR__ . "/../../tester.json";

// load the config file
$json = json_decode(file_get_contents($json_file)) or die("Unable to open ./tester.json file!");

// get the url and token from the config file
$url = $json->url;
$token = $json->token;

// send the request
$response = file_get_contents($url, false, stream_context_create([
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n",
        "content" => json_encode($json)
    ]
])) or die("Unable to get response!");

// decode the response
$response = json_decode($response, true) or die("Unable to decode response!");

// check if the response contains the session ID
if (!isset($response["session_id"])) die("Unable to find session ID!\n");
$session_id = $response["session_id"];

// check if the response contains the text
if (!isset($response["choices"][0]["message"]["content"])) die("Unable to find response text!\n");

// print the response text and input prompt
echo ("Press CTRL+C to exit...\n" . $response["choices"][0]["message"]["content"] . "\n> ");

// loop until the user exits
while (true) {
    // open a stream for the user input
    $fp = fopen("php://stdin", "r");

    // get a line of input from the user
    $user_input = fgets($fp);

    // close the stream
    fclose($fp);

    // trim the input
    $user_input = trim($user_input);

    // create the post data
    $post_data = [
        "session" => $session_id,
        "messages" => [
            [
                "role" => "user",
                "content" => $user_input
            ]
        ],
        "passthru" => true,
        "prompt_tokens" => 512,
        "stream" => true
    ];

    // create the stream context
    $context = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n",
            "content" => json_encode($post_data)
        ]
    ]);

    // open a stream to the url
    $fp = fopen($url, "r", false, $context) or die("Unable to open stream!");

    // read the stream until the end
    while (!feof($fp)) {
        // get a character from the stream
        $char = fgetc($fp);

        // remove null characters
        $char = str_replace("\0", "", $char);

        // print the character
        echo $char;
    }

    // close the stream
    fclose($fp);

    // print the input prompt
    echo ("\n> ");

    // flush the output buffer
    @ob_flush();
    flush();

    // loop!
}
