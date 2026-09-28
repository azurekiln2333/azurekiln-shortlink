<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/shortlink.php';
include_once __DIR__ . '/functions/user_auth.php';

$creator = approved_user_or_admin($db);
if (!$creator) {
    http_response_code(403);
    $error_msg = '请先使用 OAuth2 登录，并等待管理员审核通过后再生成短链。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}
ensure_platform_schema($db);
if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    http_response_code(419);
    $error_msg = '请求已过期，请返回首页后重新提交。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}
if (!rate_limit_allow($db, 'create', 30, 3600)) {
    http_response_code(429);
    $error_msg = '创建请求过于频繁，请稍后再试。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

$result = create_short_link_record(
    $db,
    isset($_POST['longurl']) ? $_POST['longurl'] : '',
    isset($_POST['cust']) ? $_POST['cust'] : '',
    isset($_POST['pass']) ? $_POST['pass'] : '',
    isset($creator['id']) ? (int) $creator['id'] : 0,
    array(
        'expires_at' => isset($_POST['expires_at']) ? $_POST['expires_at'] : '',
        'max_hits' => isset($_POST['max_hits']) ? $_POST['max_hits'] : null,
    )
);

if (!$result['success']) {
    $error_msg = $result['error'];
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

$info_result = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($info_result);
$rand1 = $result['alias'];
$created_link = $result['shorturl'];

audit_event(
    $db,
    isset($creator['id']) && (int) $creator['id'] > 0 ? 'user' : 'admin',
    isset($creator['id']) && (int) $creator['id'] > 0 ? (int) $creator['id'] : null,
    'link.created',
    'link',
    isset($result['id']) ? (int) $result['id'] : null,
    array('alias' => $result['alias'])
);

include __DIR__ . '/functions/created.php';
$db->close_connection();
