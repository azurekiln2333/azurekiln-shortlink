<?php

include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/platform.php';

ensure_platform_schema($db);

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$ads = $db->query("SELECT * FROM ads");
$ads_info = $db->fetch_array($ads);

$alias = isset($_GET['id']) ? trim($_GET['id']) : '';
$shr = $alias;
$alias_sql = $db->escape_value($alias);
$link_result = $db->query(
    "SELECT URL, date, hits, id, pass, status, expires_at, max_hits FROM links WHERE BINARY link = '$alias_sql'"
);
$link = $db->fetch_array($link_result);

if (!$link || $link['URL'] === '') {
    $error_msg = '找不到您跳转的链接';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

$url = $link['URL'];
$id = (int) $link['id'];
$pass = (string) $link['pass'];

$status = isset($link['status']) && $link['status'] !== '' ? $link['status'] : 'active';
if ($status !== 'active') {
    http_response_code(410);
    $error_msg = '这个短链当前已暂停使用。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}
if (!empty($link['expires_at']) && strtotime($link['expires_at']) <= time()) {
    http_response_code(410);
    $error_msg = '这个短链已经过期。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}
if ($link['max_hits'] !== null && $link['max_hits'] !== '' && (int) $link['hits'] >= (int) $link['max_hits']) {
    http_response_code(410);
    $error_msg = '这个短链的访问次数已用完。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}
if (!rate_limit_allow($db, 'redirect', 300, 600, 'link-' . $id)) {
    http_response_code(429);
    $error_msg = '访问过于频繁，请稍后再试。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

$submitted_password = isset($_POST['txtpass']) ? (string) $_POST['txtpass'] : '';
$password_matches = $pass === '' || (
    (strpos($pass, '$2y$') === 0 || strpos($pass, '$argon2') === 0)
        ? password_verify($submitted_password, $pass)
        : hash_equals($pass, $submitted_password)
);
if (!$password_matches) {
    include __DIR__ . '/functions/password.php';
} else {
    $db->query(
        "UPDATE links SET hits = hits + 1, last_visit = NOW() WHERE id = $id " .
        "AND (status = 'active' OR status IS NULL) " .
        "AND (expires_at IS NULL OR expires_at > NOW()) " .
        "AND (max_hits IS NULL OR hits < max_hits)"
    );
    if ($db->affected_rows() !== 1) {
        http_response_code(410);
        $error_msg = '这个短链已达到访问限制。';
        include __DIR__ . '/functions/error.php';
    } else {
        record_click_event($db, $id);
        if ($info['redirect'] == 1) {
            include __DIR__ . '/functions/splash.php';
        } else {
            include __DIR__ . '/functions/basic_redirect.php';
        }
    }
}

$db->close_connection();
