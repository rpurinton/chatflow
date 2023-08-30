<?php

namespace RPurinton\ChatFlow;

require_once(__DIR__ . "/vendor/autoload.php");

class OpenAIClient
{
    private $openai;
    public function __construct($api_key)
    {
        $this->openai = \OpenAI::client($api_key);
    }
}
