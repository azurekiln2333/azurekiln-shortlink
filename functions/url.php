<?php

function request_scheme()
{
    $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        ? strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]))
        : '';

    if ($forwarded === 'https' || $forwarded === 'http') {
        return $forwarded;
    }

    return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        ? 'https'
        : 'http';
}

function request_host()
{
    $host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';

    if ($host !== '' && preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host)) {
        return $host;
    }

    return 'localhost';
}

function application_base_path()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $directory = dirname($script);

    if (basename($directory) === 'admin') {
        $directory = dirname($directory);
    }

    if ($directory === '.' || $directory === '/' || $directory === '\\') {
        return '';
    }

    return '/' . trim($directory, '/');
}

function public_base_url()
{
    return request_scheme() . '://' . request_host() . application_base_path();
}

function public_url($path = '')
{
    return rtrim(public_base_url(), '/') . '/' . ltrim($path, '/');
}

function short_link_url($alias)
{
    return public_url('link.php?id=' . rawurlencode($alias));
}
