<?php

namespace RPurinton\ChatFlow;

require_once(__DIR__ . "/vendor/autoload.php");

class TikToken
{
    private $encoder;
    public function __construct()
    {
        $this->encoder = new \TikToken\Encoder();
    }
    public function token_count($text)
    {
        return count($this->encoder->encode($text));
    }
}
