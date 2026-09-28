<?php

include_once __DIR__ . '/random.php';
include_once __DIR__ . '/platform.php';

function validate_destination_url($long_url)
{
    $long_url = trim((string) $long_url);
    $parsed_url = $long_url === '' ? false : parse_url($long_url);
    if ($long_url === '' || strlen($long_url) > 255 || !filter_var($long_url, FILTER_VALIDATE_URL)
        || !is_array($parsed_url)
        || !isset($parsed_url['scheme'], $parsed_url['host'])
        || !in_array(strtolower($parsed_url['scheme']), array('http', 'https'), true)) {
        return 'URL is invalid.';
    }
    return '';
}

function normalize_link_options($expires_at = '', $max_hits = null)
{
    $expires_at = trim((string) $expires_at);
    $normalized_expiry = '';
    if ($expires_at !== '') {
        $timestamp = strtotime($expires_at);
        if ($timestamp === false || $timestamp <= time()) {
            return array('success' => false, 'error' => 'Expiration must be a future date and time.');
        }
        $normalized_expiry = date('Y-m-d H:i:s', $timestamp);
    }
    if ($max_hits === null || $max_hits === '') {
        $normalized_hits = null;
    } elseif (filter_var($max_hits, FILTER_VALIDATE_INT) === false || (int) $max_hits < 1 || (int) $max_hits > 2147483647) {
        return array('success' => false, 'error' => 'Maximum clicks must be a positive integer.');
    } else {
        $normalized_hits = (int) $max_hits;
    }
    return array('success' => true, 'expires_at' => $normalized_expiry, 'max_hits' => $normalized_hits);
}

function create_short_link_record($db, $long_url, $custom_alias = '', $password = '', $owner_user_id = 0, $options = array())
{
    ensure_platform_schema($db);
    $long_url = trim((string) $long_url);
    $custom_alias = trim((string) $custom_alias);
    $password = (string) $password;
    $reserved = array('admin', 'contact', 'tos', 'about', 'api-about', '404', 'api', 'stats', 'statics', 'account', 'my-links');
    $url_error = validate_destination_url($long_url);
    if ($url_error !== '') {
        return array('success' => false, 'error' => $url_error);
    }

    if (strlen($password) > 255) {
        return array('success' => false, 'error' => 'Link password is too long.');
    }

    $link_options = normalize_link_options(
        isset($options['expires_at']) ? $options['expires_at'] : '',
        isset($options['max_hits']) ? $options['max_hits'] : null
    );
    if (!$link_options['success']) {
        return $link_options;
    }

    if (destination_domain_blocked($db, normalized_url_host($long_url))) {
        return array('success' => false, 'error' => 'This destination domain is blocked.');
    }

    if ($custom_alias !== '') {
        if (!preg_match('/^[A-Za-z0-9_-]{1,16}$/', $custom_alias)) {
            return array('success' => false, 'error' => 'Invalid custom alias.');
        }

        if (in_array(strtolower($custom_alias), $reserved, true)) {
            return array('success' => false, 'error' => 'Invalid custom alias.');
        }
    }

    $alias = $custom_alias;
    if ($alias === '') {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = generateRandomString();
            $candidate_sql = $db->escape_value($candidate);
            $existing = $db->query("SELECT link FROM links WHERE BINARY link = '$candidate_sql'");
            if ($db->num_rows($existing) === 0) {
                $alias = $candidate;
                break;
            }
        }

        if ($alias === '') {
            return array('success' => false, 'error' => 'Unable to allocate a short alias.');
        }
    } else {
        $alias_sql = $db->escape_value($alias);
        $existing = $db->query("SELECT link FROM links WHERE BINARY link = '$alias_sql'");
        if ($db->num_rows($existing) !== 0) {
            return array('success' => false, 'error' => 'Custom alias already exists.');
        }
    }

    $long_sql = $db->escape_value($long_url);
    $alias_sql = $db->escape_value($alias);
    $stored_password = $password === '' ? '' : password_hash($password, PASSWORD_DEFAULT);
    $password_sql = $db->escape_value($stored_password);
    $is_custom = $custom_alias === '' ? 0 : 1;
    $owner_sql = (int) $owner_user_id > 0 ? (string) (int) $owner_user_id : 'NULL';
    $expiry_sql = $link_options['expires_at'] === '' ? 'NULL' : "'" . $db->escape_value($link_options['expires_at']) . "'";
    $max_hits_sql = $link_options['max_hits'] === null ? 'NULL' : (string) $link_options['max_hits'];

    $action = $db->query(
        "INSERT INTO links (URL, link, pass, custom, last_visit, owner_user_id, status, expires_at, max_hits) " .
        "VALUES ('$long_sql', '$alias_sql', '$password_sql', '$is_custom', NOW(), $owner_sql, 'active', $expiry_sql, $max_hits_sql)"
    );

    if (!$action) {
        return array('success' => false, 'error' => 'Internal Server Error.');
    }
    $link_id = $db->insert_id();

    return array(
        'success' => true,
        'id' => (int) $link_id,
        'alias' => $alias,
        'longurl' => $long_url,
        'shorturl' => short_link_url($alias),
        'stats' => public_url('stats.php?id=' . rawurlencode($alias)),
        'expires_at' => $link_options['expires_at'],
        'max_hits' => $link_options['max_hits'],
    );
}
