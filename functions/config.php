<?php

function app_config($name, $default = '')
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }

    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    return array_key_exists($name, $config) ? $config[$name] : $default;
}
