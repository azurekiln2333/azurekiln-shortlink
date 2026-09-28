<?php
include_once __DIR__ . '/../../functions/session.php';
start_app_session();
include_once __DIR__ . '/../../functions/user_auth.php';
if (!admin_session_active() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    http_response_code(403);
    exit;
}
include_once __DIR__ . '/../../functions/database.php';
include_once __DIR__ . '/../../functions/platform.php';
ensure_platform_schema($db);
$id = isset($_POST['ccz']) ? (int) $_POST['ccz'] : 0;
if ($id > 0) {
    $link_result = $db->query("SELECT link FROM links WHERE id=$id LIMIT 1");
    $link = $db->fetch_array($link_result);
    $db->query("DELETE FROM shortny_clicks WHERE link_id=$id");
    $db->query("DELETE FROM links WHERE id=$id");
    audit_event($db, 'admin', null, 'link.deleted', 'link', $id, array('alias' => $link ? $link['link'] : ''));
}
