<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';

if (!admin_session_active()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    session_unset();
    session_destroy();
}

header('Location: ../index.php');
exit;
?>
