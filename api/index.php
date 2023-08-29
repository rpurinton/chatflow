<?php
$result["result"] = "ok";
$result["message"] = "Hello World";
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT);
