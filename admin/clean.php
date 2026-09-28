<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';
if (!admin_session_active()) {
    header("Location: login.php");
    exit;
}

include "../functions/database.php";
include_once __DIR__ . '/../functions/platform.php';
ensure_platform_schema($db);
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$days = isset($_POST['d']) ? (int) $_POST['d'] : null;
$success = false;
$message = '未提供清理天数';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $days !== null && $days >= 0 && admin_session_active() && csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    $sql = "DELETE FROM links WHERE last_visit<DATE_SUB(curdate(), INTERVAL $days DAY)";
    $db->query($sql);
    $retention_days = (int) platform_env('SHORTNY_ANALYTICS_RETENTION_DAYS', '180');
    if ($retention_days > 0) {
        $db->query("DELETE FROM shortny_clicks WHERE clicked_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
    }
    $db->query("DELETE FROM shortny_rate_limits WHERE bucket_start < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    audit_event($db, 'admin', null, 'links.cleaned', 'link', null, array('days' => $days, 'analytics_retention_days' => $retention_days));
    $success = true;
    $message = '清理成功，已删除上次访问时间早于 ' . $days . ' 天的链接。';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>清理链接 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-admin-main">
    <div class="ak-content">
        <section class="ak-card ak-card-body" style="text-align:center;">
            <span class="ak-material" style="font-size:48px;color:<?php echo $success ? 'var(--ak-success)' : 'var(--ak-danger)'; ?>;" aria-hidden="true"><?php echo $success ? 'check_circle' : 'error'; ?></span>
            <h1 class="ak-page-heading" style="font-size:30px;margin-top:12px;">链接清理</h1>
            <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <a class="ak-button ak-button-secondary" href="index.php"><span class="ak-material" aria-hidden="true">arrow_back</span>返回概览</a>
        </section>
    </div>
</main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
