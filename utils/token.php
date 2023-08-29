<?php
echo (hash("sha256", number_format(microtime(true), 6, ".", ",")) . "\n");
