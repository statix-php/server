<?php

// get the env variables
$envVars = array_filter(array_merge($_SERVER, $_ENV, getenv()), function($key) {
    return strpos($key, 'APP_') === 0;
}, ARRAY_FILTER_USE_KEY);

require __DIR__ . '\index.php';