<?php
if (!isset($argv[1])) die("Usage: php password.php <password>\n");
for ($i = 0; $i < 10; $i++) $argv[1] = hash("sha256", "chatflow:$i:{$argv[1]}:$i:chatflow");
echo "Password Hash: {$argv[1]}\n";
