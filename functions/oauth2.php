<?php

include_once __DIR__ . '/config.php';

function oauth2_env($name, $default = '')
{
    return trim((string) app_config($name, $default));
}

function oauth2_config()
{
    return array(
        'issuer' => rtrim(oauth2_env('OAUTH2_ISSUER'), '/'),
        'authorization_endpoint' => oauth2_env('OAUTH2_AUTHORIZATION_ENDPOINT'),
        'token_endpoint' => oauth2_env('OAUTH2_TOKEN_ENDPOINT'),
        'userinfo_endpoint' => oauth2_env('OAUTH2_USERINFO_ENDPOINT'),
        'client_id' => oauth2_env('OAUTH2_CLIENT_ID'),
        'client_secret' => oauth2_env('OAUTH2_CLIENT_SECRET'),
        'redirect_uri' => oauth2_env('OAUTH2_REDIRECT_URI'),
        'user_redirect_uri' => oauth2_env('OAUTH2_USER_REDIRECT_URI'),
        'scopes' => oauth2_env('OAUTH2_SCOPES', 'openid profile email'),
        'admin_emails' => oauth2_env('OAUTH2_ADMIN_EMAILS'),
        'admin_subjects' => oauth2_env('OAUTH2_ADMIN_SUBJECTS'),
    );
}

function oauth2_enabled($require_admin = true)
{
    $config = oauth2_config();
    $base_enabled = $config['client_id'] !== ''
        && $config['client_secret'] !== ''
        && $config['redirect_uri'] !== ''
        && $config['user_redirect_uri'] !== ''
        && ($config['issuer'] !== '' || ($config['authorization_endpoint'] !== '' && $config['token_endpoint'] !== '' && $config['userinfo_endpoint'] !== ''));
    if (!$base_enabled || !$require_admin) {
        return $base_enabled;
    }
    return $config['admin_emails'] !== '' || $config['admin_subjects'] !== '';
}

function oauth2_redirect_uri($config, $purpose = 'admin')
{
    if ($purpose === 'user' && $config['user_redirect_uri'] !== '') {
        return $config['user_redirect_uri'];
    }
    if ($purpose !== 'user' && $config['redirect_uri'] !== '') {
        return $config['redirect_uri'];
    }
    throw new Exception('OAuth2 redirect URI is not configured.');
}

function oauth2_http_json($url, $method = 'GET', $body = array(), $headers = array())
{
    if (!function_exists('curl_init')) {
        throw new Exception('PHP cURL extension is required for OAuth2 login.');
    }

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(array('Accept: application/json'), $headers));

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body, '', '&'));
    }

    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false || $error !== '') {
        throw new Exception('OAuth2 provider request failed.');
    }

    $decoded = json_decode($response, true);
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        throw new Exception('OAuth2 provider returned an invalid response.');
    }

    return $decoded;
}

function oauth2_provider_config($config)
{
    static $provider = null;
    if ($provider !== null) {
        return $provider;
    }

    $provider = array(
        'authorization_endpoint' => $config['authorization_endpoint'],
        'token_endpoint' => $config['token_endpoint'],
        'userinfo_endpoint' => $config['userinfo_endpoint'],
    );

    if ($config['issuer'] !== '') {
        try {
            $discovery = oauth2_http_json($config['issuer'] . '/.well-known/openid-configuration');
            $provider = array_merge($provider, array(
                'authorization_endpoint' => isset($discovery['authorization_endpoint']) ? $discovery['authorization_endpoint'] : $provider['authorization_endpoint'],
                'token_endpoint' => isset($discovery['token_endpoint']) ? $discovery['token_endpoint'] : $provider['token_endpoint'],
                'userinfo_endpoint' => isset($discovery['userinfo_endpoint']) ? $discovery['userinfo_endpoint'] : $provider['userinfo_endpoint'],
            ));
        } catch (Exception $exception) {
            // Explicit endpoints remain a deployable fallback when discovery is temporarily unavailable.
            if ($provider['authorization_endpoint'] === '' || $provider['token_endpoint'] === '' || $provider['userinfo_endpoint'] === '') {
                throw new Exception('OAuth2 provider discovery failed.');
            }
        }
    }

    if ($provider['authorization_endpoint'] === '' || $provider['token_endpoint'] === '') {
        throw new Exception('OAuth2 authorization and token endpoints are required.');
    }

    return $provider;
}

function oauth2_random_string($bytes = 32)
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function oauth2_begin($purpose = 'admin')
{
    $config = oauth2_config();
    $provider = oauth2_provider_config($config);
    $state = oauth2_random_string();
    $verifier = oauth2_random_string(48);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    $state_key = $purpose === 'user' ? 'oauth2_user_state' : 'oauth2_state';
    $redirect_uri = oauth2_redirect_uri($config, $purpose);
    $_SESSION[$state_key] = array(
        'state' => $state,
        'verifier' => $verifier,
        'created_at' => time(),
        'redirect_uri' => $redirect_uri,
        'purpose' => $purpose,
    );

    $query = http_build_query(array(
        'response_type' => 'code',
        'client_id' => $config['client_id'],
        'redirect_uri' => $redirect_uri,
        'scope' => $config['scopes'],
        'state' => $state,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ), '', '&');

    return $provider['authorization_endpoint'] . (strpos($provider['authorization_endpoint'], '?') === false ? '?' : '&') . $query;
}

function oauth2_login($purpose = 'admin')
{
    $state_key = $purpose === 'user' ? 'oauth2_user_state' : 'oauth2_state';
    $saved = isset($_SESSION[$state_key]) && is_array($_SESSION[$state_key])
        ? $_SESSION[$state_key]
        : null;
    unset($_SESSION[$state_key]);

    if ($saved === null || !isset($saved['state'], $saved['verifier'], $saved['created_at'])) {
        throw new Exception('OAuth2 callback state is missing or expired.');
    }
    if (isset($_GET['error'])) {
        throw new Exception('OAuth2 authorization was not completed.');
    }
    if (!isset($_GET['code'], $_GET['state']) || $_GET['code'] === '') {
        throw new Exception('OAuth2 callback is missing required parameters.');
    }
    if (!hash_equals((string) $saved['state'], (string) $_GET['state']) || time() - (int) $saved['created_at'] > 600) {
        throw new Exception('OAuth2 state validation failed.');
    }

    $config = oauth2_config();
    $provider = oauth2_provider_config($config);
    $redirect_uri = isset($saved['redirect_uri']) ? $saved['redirect_uri'] : oauth2_redirect_uri($config, $purpose);
    $token_body = array(
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $redirect_uri,
        'client_id' => $config['client_id'],
        'code_verifier' => $saved['verifier'],
    );
    if ($config['client_secret'] !== '') {
        $token_body['client_secret'] = $config['client_secret'];
    }

    $token = oauth2_http_json($provider['token_endpoint'], 'POST', $token_body, array('Content-Type: application/x-www-form-urlencoded'));
    if (empty($token['access_token'])) {
        throw new Exception('OAuth2 provider did not return an access token.');
    }
    if ($provider['userinfo_endpoint'] === '') {
        throw new Exception('OAuth2 userinfo endpoint is required.');
    }

    return oauth2_http_json(
        $provider['userinfo_endpoint'],
        'GET',
        array(),
        array('Authorization: Bearer ' . $token['access_token'])
    );
}

function oauth2_list($value)
{
    $items = array();
    foreach (explode(',', $value) as $item) {
        $item = trim($item);
        if ($item !== '') {
            $items[] = $item;
        }
    }
    return $items;
}

function oauth2_is_admin($identity)
{
    $config = oauth2_config();
    $email = isset($identity['email']) ? strtolower(trim($identity['email'])) : '';
    $subject = isset($identity['sub']) ? (string) $identity['sub'] : '';

    foreach (oauth2_list($config['admin_emails']) as $allowed) {
        if ($email !== '' && hash_equals(strtolower($allowed), $email)) {
            return true;
        }
    }
    foreach (oauth2_list($config['admin_subjects']) as $allowed) {
        if ($subject !== '' && hash_equals($allowed, $subject)) {
            return true;
        }
    }

    return false;
}

function oauth2_identity_name($identity)
{
    foreach (array('email', 'preferred_username', 'name', 'sub') as $field) {
        if (!empty($identity[$field])) {
            return (string) $identity[$field];
        }
    }
    return 'oauth2-admin';
}
