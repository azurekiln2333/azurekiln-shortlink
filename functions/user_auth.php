<?php

include_once __DIR__ . '/oauth2.php';

function ensure_user_schema($db)
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $db->query(
        "CREATE TABLE IF NOT EXISTS shortny_users (" .
        "id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT," .
        "provider VARCHAR(32) NOT NULL DEFAULT 'oauth2'," .
        "subject VARCHAR(128) NOT NULL," .
        "email VARCHAR(255) NOT NULL DEFAULT ''," .
        "display_name VARCHAR(255) NOT NULL DEFAULT ''," .
        "status ENUM('pending','approved','suspended','rejected') NOT NULL DEFAULT 'pending'," .
        "created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP," .
        "last_login_at DATETIME NULL," .
        "approved_at DATETIME NULL," .
        "approved_by VARCHAR(255) NOT NULL DEFAULT ''," .
        "PRIMARY KEY (id)," .
        "UNIQUE KEY provider_subject (provider, subject)," .
        "KEY status (status)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $ready = true;
}

function oauth2_identity_subject($identity)
{
    $subject = isset($identity['sub']) ? trim((string) $identity['sub']) : '';
    if ($subject === '' || strlen($subject) > 128) {
        throw new Exception('OAuth2 identity has no valid subject.');
    }
    return $subject;
}

function oauth2_identity_email($identity)
{
    return isset($identity['email']) ? trim((string) $identity['email']) : '';
}

function oauth2_identity_display_name($identity)
{
    foreach (array('name', 'preferred_username', 'email', 'sub') as $field) {
        if (!empty($identity[$field])) {
            return trim((string) $identity[$field]);
        }
    }
    return 'OAuth2 user';
}

function user_sync_identity($db, $identity, $auto_approve = false)
{
    ensure_user_schema($db);
    $subject = oauth2_identity_subject($identity);
    $subject_sql = $db->escape_value($subject);
    $provider_sql = $db->escape_value('oauth2');
    $email_sql = $db->escape_value(oauth2_identity_email($identity));
    $name_sql = $db->escape_value(oauth2_identity_display_name($identity));
    $existing = $db->query("SELECT * FROM shortny_users WHERE provider='$provider_sql' AND subject='$subject_sql' LIMIT 1");
    $user = $db->fetch_array($existing);

    if (!$user) {
        $status = $auto_approve ? 'approved' : 'pending';
        $status_sql = $db->escape_value($status);
        $approved_at = $auto_approve ? 'NOW()' : 'NULL';
        $approved_by = $auto_approve ? $db->escape_value('oauth2-admin') : '';
        $approved_by_sql = $db->escape_value($approved_by);
        $db->query(
            "INSERT INTO shortny_users (provider, subject, email, display_name, status, last_login_at, approved_at, approved_by) " .
            "VALUES ('$provider_sql', '$subject_sql', '$email_sql', '$name_sql', '$status_sql', NOW(), $approved_at, '$approved_by_sql')"
        );
        $user_id = $db->insert_id();
    } else {
        $user_id = (int) $user['id'];
        $approval_sql = $auto_approve && $user['status'] !== 'approved'
            ? ", status='approved', approved_at=NOW(), approved_by='oauth2-admin'"
            : '';
        $db->query(
            "UPDATE shortny_users SET email='$email_sql', display_name='$name_sql', last_login_at=NOW()$approval_sql " .
            "WHERE id=$user_id"
        );
    }

    $result = $db->query("SELECT * FROM shortny_users WHERE id=$user_id LIMIT 1");
    return $db->fetch_array($result);
}

function current_user_record($db)
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    ensure_user_schema($db);
    $user_id = (int) $_SESSION['user_id'];
    $result = $db->query("SELECT * FROM shortny_users WHERE id=$user_id LIMIT 1");
    return $db->fetch_array($result) ?: null;
}

function admin_session_active()
{
    return !empty($_SESSION['admin_user']) && !empty($_SESSION['valid_user']);
}

function approved_user_or_admin($db)
{
    if (admin_session_active()) {
        return array('id' => 0, 'status' => 'approved', 'display_name' => $_SESSION['valid_user']);
    }
    $user = current_user_record($db);
    return $user && $user['status'] === 'approved' ? $user : null;
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid($token)
{
    return is_string($token) && $token !== '' && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
