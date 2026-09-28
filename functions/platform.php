<?php

include_once __DIR__ . '/config.php';

function platform_env($name, $default = '')
{
    return trim((string) app_config($name, $default));
}

function ensure_platform_schema($db)
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $columns = array(
        'owner_user_id' => 'BIGINT UNSIGNED NULL',
        'status' => "ENUM('active','paused') NOT NULL DEFAULT 'active'",
        'expires_at' => 'DATETIME NULL',
        'max_hits' => 'INT UNSIGNED NULL',
    );
    foreach ($columns as $column => $definition) {
        $column_sql = $db->escape_value($column);
        $check = $db->query(
            "SELECT COUNT(*) AS total FROM information_schema.COLUMNS " .
            "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='links' AND COLUMN_NAME='$column_sql'"
        );
        $exists = $db->fetch_array($check);
        if ((int) $exists['total'] === 0) {
            $db->query("ALTER TABLE links ADD COLUMN `$column` $definition");
        }
    }

    $indexes = array(
        'links_owner_user_id' => 'CREATE INDEX links_owner_user_id ON links (owner_user_id)',
        'links_status_expires' => 'CREATE INDEX links_status_expires ON links (status, expires_at)',
    );
    foreach ($indexes as $index_name => $statement) {
        $index_sql = $db->escape_value($index_name);
        $index_result = $db->query(
            "SELECT COUNT(*) AS total FROM information_schema.STATISTICS " .
            "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='links' AND INDEX_NAME='$index_sql'"
        );
        $index_row = $db->fetch_array($index_result);
        if ((int) $index_row['total'] === 0) {
            $db->query($statement);
        }
    }
    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_clicks (" .
        "id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
        "link_id INT NOT NULL," .
        "clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP," .
        "referrer VARCHAR(512) NOT NULL DEFAULT ''," .
        "device_type VARCHAR(32) NOT NULL DEFAULT 'unknown'," .
        "browser VARCHAR(64) NOT NULL DEFAULT 'unknown'," .
        "operating_system VARCHAR(64) NOT NULL DEFAULT 'unknown'," .
        "ip_hash CHAR(64) NOT NULL DEFAULT ''," .
        "PRIMARY KEY (id)," .
        "KEY link_clicked (link_id, clicked_at)," .
        "KEY clicked_at (clicked_at)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_api_keys (" .
        "id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
        "user_id BIGINT UNSIGNED NOT NULL," .
        "label VARCHAR(100) NOT NULL DEFAULT ''," .
        "key_prefix VARCHAR(16) NOT NULL," .
        "key_hash CHAR(64) NOT NULL," .
        "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP," .
        "last_used_at DATETIME NULL," .
        "revoked_at DATETIME NULL," .
        "PRIMARY KEY (id)," .
        "UNIQUE KEY key_hash (key_hash)," .
        "KEY user_status (user_id, revoked_at)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_rate_limits (" .
        "subject_hash CHAR(64) NOT NULL," .
        "action VARCHAR(64) NOT NULL," .
        "bucket_start DATETIME NOT NULL," .
        "hits INT UNSIGNED NOT NULL DEFAULT 0," .
        "PRIMARY KEY (subject_hash, action, bucket_start)," .
        "KEY bucket_start (bucket_start)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_blocked_domains (" .
        "id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
        "hostname VARCHAR(255) NOT NULL," .
        "note VARCHAR(255) NOT NULL DEFAULT ''," .
        "enabled TINYINT(1) NOT NULL DEFAULT 1," .
        "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP," .
        "created_by VARCHAR(255) NOT NULL DEFAULT ''," .
        "PRIMARY KEY (id)," .
        "UNIQUE KEY hostname (hostname)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_audit_logs (" .
        "id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
        "actor_type VARCHAR(32) NOT NULL," .
        "actor_user_id BIGINT UNSIGNED NULL," .
        "action VARCHAR(64) NOT NULL," .
        "target_type VARCHAR(32) NOT NULL DEFAULT ''," .
        "target_id BIGINT UNSIGNED NULL," .
        "metadata_json TEXT NOT NULL," .
        "ip_hash CHAR(64) NOT NULL DEFAULT ''," .
        "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP," .
        "PRIMARY KEY (id)," .
        "KEY audit_created (created_at)," .
        "KEY audit_target (target_type, target_id)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $ready = true;
}

function request_ip_address()
{
    return isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
}

function privacy_hash($value)
{
    $salt = platform_env('SHORTNY_ANALYTICS_SALT');
    if ($salt === '') {
        return '';
    }
    return hash('sha256', $salt . '|' . (string) $value);
}

function rate_limit_subject($suffix = '')
{
    $identity = !empty($_SESSION['user_id']) ? 'user:' . (int) $_SESSION['user_id'] : 'ip:' . request_ip_address();
    if ($suffix !== '') {
        $identity .= ':' . $suffix;
    }
    $salt = platform_env('SHORTNY_ANALYTICS_SALT');
    if ($salt === '') {
        $salt = 'shortny-rate-limit';
    }
    return hash('sha256', $salt . '|' . $identity);
}

function rate_limit_allow($db, $action, $limit, $window_seconds = 3600, $suffix = '')
{
    $bucket_timestamp = floor(time() / $window_seconds) * $window_seconds;
    $bucket = date('Y-m-d H:i:s', $bucket_timestamp);
    $subject = $db->escape_value(rate_limit_subject($suffix));
    $action_sql = $db->escape_value($action);
    $bucket_sql = $db->escape_value($bucket);
    $db->query(
        "INSERT INTO shortny_rate_limits (subject_hash, action, bucket_start, hits) " .
        "VALUES ('$subject', '$action_sql', '$bucket_sql', 1) " .
        "ON DUPLICATE KEY UPDATE hits=hits+1"
    );
    $result = $db->query(
        "SELECT hits FROM shortny_rate_limits WHERE subject_hash='$subject' AND action='$action_sql' AND bucket_start='$bucket_sql' LIMIT 1"
    );
    $row = $db->fetch_array($result);
    return $row && (int) $row['hits'] <= (int) $limit;
}

function normalized_url_host($url)
{
    $parsed = parse_url((string) $url);
    if (!is_array($parsed) || empty($parsed['host'])) {
        return '';
    }
    return strtolower(rtrim((string) $parsed['host'], '.'));
}

function destination_domain_blocked($db, $hostname)
{
    $hostname = strtolower(rtrim(trim((string) $hostname), '.'));
    if ($hostname === '') {
        return false;
    }
    $result = $db->query("SELECT hostname FROM shortny_blocked_domains WHERE enabled=1");
    while ($row = $db->fetch_array($result)) {
        $blocked = strtolower(rtrim($row['hostname'], '.'));
        if ($hostname === $blocked || (strlen($hostname) > strlen($blocked) && substr($hostname, -strlen($blocked) - 1) === '.' . $blocked)) {
            return true;
        }
    }
    return false;
}

function user_agent_facts($user_agent)
{
    $ua = strtolower((string) $user_agent);
    $device = preg_match('/mobile|iphone|android(?!.*tablet)/', $ua) ? 'mobile' : (preg_match('/tablet|ipad/', $ua) ? 'tablet' : 'desktop');
    if (strpos($ua, 'edg/') !== false) {
        $browser = 'Edge';
    } elseif (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) {
        $browser = 'Opera';
    } elseif (strpos($ua, 'chrome/') !== false || strpos($ua, 'crios/') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($ua, 'firefox/') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($ua, 'safari/') !== false) {
        $browser = 'Safari';
    } else {
        $browser = 'Other';
    }
    if (strpos($ua, 'windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($ua, 'android') !== false) {
        $os = 'Android';
    } elseif (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
        $os = 'iOS';
    } elseif (strpos($ua, 'mac os') !== false) {
        $os = 'macOS';
    } elseif (strpos($ua, 'linux') !== false) {
        $os = 'Linux';
    } else {
        $os = 'Other';
    }
    return array('device' => $device, 'browser' => $browser, 'os' => $os);
}

function record_click_event($db, $link_id)
{
    if (platform_env('SHORTNY_ANALYTICS_ENABLED', '1') === '0') {
        return;
    }
    $facts = user_agent_facts(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    $referrer = '';
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referrer_url = parse_url((string) $_SERVER['HTTP_REFERER']);
        if (is_array($referrer_url) && !empty($referrer_url['host'])) {
            $referrer = strtolower((isset($referrer_url['scheme']) ? $referrer_url['scheme'] . '://' : '') . $referrer_url['host']);
        }
    }
    $referrer_sql = $db->escape_value($referrer);
    $ip_hash_sql = $db->escape_value(privacy_hash(request_ip_address()));
    $device_sql = $db->escape_value($facts['device']);
    $browser_sql = $db->escape_value($facts['browser']);
    $os_sql = $db->escape_value($facts['os']);
    $db->query(
        "INSERT INTO shortny_clicks (link_id, referrer, device_type, browser, operating_system, ip_hash) " .
        "VALUES (" . (int) $link_id . ", '$referrer_sql', '$device_sql', '$browser_sql', '$os_sql', '$ip_hash_sql')"
    );
}

function audit_event($db, $actor_type, $actor_user_id, $action, $target_type = '', $target_id = null, $metadata = array())
{
    $actor_sql = $db->escape_value($actor_type);
    $action_sql = $db->escape_value($action);
    $target_type_sql = $db->escape_value($target_type);
    $metadata_sql = $db->escape_value(json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $ip_hash_sql = $db->escape_value(privacy_hash(request_ip_address()));
    $actor_id_sql = $actor_user_id === null ? 'NULL' : (string) ((int) $actor_user_id);
    $target_id_sql = $target_id === null ? 'NULL' : (string) ((int) $target_id);
    $db->query(
        "INSERT INTO shortny_audit_logs (actor_type, actor_user_id, action, target_type, target_id, metadata_json, ip_hash) " .
        "VALUES ('$actor_sql', $actor_id_sql, '$action_sql', '$target_type_sql', $target_id_sql, '$metadata_sql', '$ip_hash_sql')"
    );
}

function api_bearer_token()
{
    $header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '');
    if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
        return trim($matches[1]);
    }
    return '';
}

function authenticate_api_key($db, $token)
{
    ensure_platform_schema($db);
    if ($token === '' || strlen($token) > 200) {
        return null;
    }
    $hash = $db->escape_value(hash('sha256', $token));
    $result = $db->query("SELECT * FROM shortny_api_keys WHERE key_hash='$hash' AND revoked_at IS NULL LIMIT 1");
    $key = $db->fetch_array($result);
    if (!$key) {
        return null;
    }
    $user_id = (int) $key['user_id'];
    $db->query("UPDATE shortny_api_keys SET last_used_at=NOW() WHERE id=" . (int) $key['id']);
    $user_result = $db->query("SELECT * FROM shortny_users WHERE id=$user_id LIMIT 1");
    $user = $db->fetch_array($user_result);
    if (!$user || $user['status'] !== 'approved') {
        return null;
    }
    return array('user' => $user, 'key' => $key);
}

function create_user_api_key($db, $user_id, $label)
{
    $label = trim((string) $label);
    if (strlen($label) > 100) {
        $label = substr($label, 0, 100);
    }
    $token = 'ak_' . bin2hex(random_bytes(24));
    $prefix = substr($token, 0, 12);
    $label_sql = $db->escape_value($label);
    $prefix_sql = $db->escape_value($prefix);
    $hash_sql = $db->escape_value(hash('sha256', $token));
    $db->query(
        "INSERT INTO shortny_api_keys (user_id, label, key_prefix, key_hash) VALUES (" . (int) $user_id . ", '$label_sql', '$prefix_sql', '$hash_sql')"
    );
    return $token;
}
