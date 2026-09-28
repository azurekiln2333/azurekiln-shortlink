<?php

include_once __DIR__ . '/url.php';

function start_app_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = request_scheme() === 'https';
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        session_set_cookie_params(0, '/', '', $secure, true);
    }
    session_start();
}
