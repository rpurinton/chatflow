<?php

namespace RPurinton\ChatFlow;

class SqlClient
{
    private $sql = null;

    function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $db = "chatflow";
        $this->sql = mysqli_connect("127.0.0.1", $db, $db, $db);
    }

    public function query($query)
    {
        if (!mysqli_ping($this->sql)) $this->connect();
        return mysqli_query($this->sql, $query);
    }

    public function count($result)
    {
        return mysqli_num_rows($result);
    }

    public function insert($query)
    {
        $result = $this->query($query);
        if (!$result) {
            throw new \Exception('MySQL insert error: ' . \mysqli_error($this->sql));
        }
        return $this->insert_id();
    }

    public function assoc($result)
    {
        return mysqli_fetch_assoc($result);
    }

    public function escape($text)
    {
        return mysqli_real_escape_string($this->sql, $text);
    }

    public function single($query)
    {
        if (!mysqli_ping($this->sql)) $this->connect();
        return mysqli_fetch_assoc(mysqli_query($this->sql, $query));
    }

    public function multi($query)
    {
        if (!mysqli_ping($this->sql)) $this->connect();
        mysqli_multi_query($this->sql, $query);
        while (mysqli_next_result($this->sql));
        return;
    }
    public function insert_id()
    {
        return \mysqli_insert_id($this->sql);
    }
}
