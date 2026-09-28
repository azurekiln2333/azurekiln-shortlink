<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';
if (!admin_session_active()) {
    header('Location: login.php');
    exit;
}
include_once __DIR__ . '/../functions/database.php';
include_once __DIR__ . '/../functions/platform.php';
ensure_platform_schema($db);
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$page = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
$limit = 50;
$offset = $page * $limit;
$count_result = $db->query("SELECT COUNT(*) AS total FROM shortny_audit_logs");
$count_row = $db->fetch_array($count_result);
$total = (int) $count_row['total'];
$logs = $db->query("SELECT * FROM shortny_audit_logs ORDER BY created_at DESC, id DESC LIMIT $offset, $limit");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>审计日志 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet"><link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include 'functions/menu.php'; ?>
<main class="ak-admin-main"><div class="ak-shell">
    <div class="ak-admin-toolbar"><div><p class="ak-eyebrow">Security history</p><h1 class="ak-admin-title">审计日志</h1></div><span class="ak-admin-muted">共 <?php echo $total; ?> 条</span></div>
    <section class="ak-card ak-table-card"><div class="ak-table-wrap"><table class="ak-table" style="min-width:920px;"><thead><tr><th>时间</th><th>操作者</th><th>动作</th><th>目标</th><th>详细信息</th></tr></thead><tbody>
    <?php $has_logs = false; while ($log = $db->fetch_array($logs)): $has_logs = true; $metadata = json_decode($log['metadata_json'], true); ?><tr><td><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($log['actor_type'] . ($log['actor_user_id'] ? ' #' . $log['actor_user_id'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td><code><?php echo htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars($log['target_type'] . ($log['target_id'] ? ' #' . $log['target_id'] : ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars(is_array($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '', ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endwhile; ?>
    <?php if (!$has_logs): ?><tr><td colspan="5" class="ak-empty">暂无审计记录</td></tr><?php endif; ?></tbody></table></div></section>
    <?php if ($total > $limit): ?><nav class="ak-pagination" aria-label="审计日志分页"><?php if ($page > 0): ?><a class="ak-button ak-button-secondary" href="audit.php?page=<?php echo $page - 1; ?>">上一页</a><?php endif; ?><span class="ak-admin-muted">第 <?php echo $page + 1; ?> / <?php echo (int) ceil($total / $limit); ?> 页</span><?php if ($offset + $limit < $total): ?><a class="ak-button ak-button-secondary" href="audit.php?page=<?php echo $page + 1; ?>">下一页</a><?php endif; ?></nav><?php endif; ?>
</div></main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
