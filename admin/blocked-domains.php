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
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $message = '请求已过期，请刷新页面后重试。';
        $message_type = 'danger';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        $domain_id = isset($_POST['domain_id']) ? (int) $_POST['domain_id'] : 0;
        if ($action === 'save') {
            $hostname = strtolower(trim((string) (isset($_POST['hostname']) ? $_POST['hostname'] : '')));
            $hostname = preg_replace('/^https?:\/\//i', '', $hostname);
            $hostname = trim(explode('/', $hostname, 2)[0]);
            $note = trim((string) (isset($_POST['note']) ? $_POST['note'] : ''));
            if (strlen($hostname) > 255 || !preg_match('/^(?=.{1,255}$)[A-Za-z0-9](?:[A-Za-z0-9.-]*[A-Za-z0-9])?$/', $hostname)) {
                $message = '请输入有效的域名或主机名。';
                $message_type = 'danger';
            } else {
                $hostname_sql = $db->escape_value(rtrim($hostname, '.'));
                $note_sql = $db->escape_value(substr($note, 0, 255));
                $created_by_sql = $db->escape_value((string) $_SESSION['valid_user']);
                $db->query("INSERT INTO shortny_blocked_domains (hostname, note, enabled, created_by) VALUES ('$hostname_sql', '$note_sql', 1, '$created_by_sql') ON DUPLICATE KEY UPDATE note='$note_sql', enabled=1, created_by='$created_by_sql'");
                audit_event($db, 'admin', null, 'blocked_domain.created', 'blocked_domain', null, array('hostname' => $hostname));
                $message = '域名已加入黑名单。';
            }
        } elseif ($domain_id > 0 && in_array($action, array('disable', 'delete'), true)) {
            $domain_result = $db->query("SELECT hostname FROM shortny_blocked_domains WHERE id=$domain_id LIMIT 1");
            $domain_row = $db->fetch_array($domain_result);
            if ($action === 'disable') {
                $db->query("UPDATE shortny_blocked_domains SET enabled=0 WHERE id=$domain_id");
                $message = '域名规则已停用。';
            } else {
                $db->query("DELETE FROM shortny_blocked_domains WHERE id=$domain_id");
                $message = '域名规则已删除。';
            }
            audit_event($db, 'admin', null, 'blocked_domain.' . $action, 'blocked_domain', $domain_id, array('hostname' => $domain_row ? $domain_row['hostname'] : ''));
        } else {
            $message = '无效的操作。';
            $message_type = 'danger';
        }
    }
}
$domains = $db->query("SELECT * FROM shortny_blocked_domains ORDER BY enabled DESC, created_at DESC");
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>域名黑名单 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet"><link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include 'functions/menu.php'; ?>
<main class="ak-admin-main"><div class="ak-shell">
    <div class="ak-admin-toolbar"><div><p class="ak-eyebrow">Destination safety</p><h1 class="ak-admin-title">域名黑名单</h1></div><a class="ak-button ak-button-secondary" href="index.php"><span class="ak-material" aria-hidden="true">arrow_back</span>返回概览</a></div>
    <?php if ($message !== ''): ?><div class="ak-notice ak-notice-<?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <section class="ak-card ak-card-body"><form class="ak-form-actions" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="save"><input class="ak-input" type="text" name="hostname" maxlength="255" placeholder="example.com 或 evil.example.com" required><input class="ak-input" type="text" name="note" maxlength="255" placeholder="备注（可选）"><button class="ak-button ak-button-primary" type="submit"><span class="ak-material" aria-hidden="true">block</span>加入黑名单</button></form><p class="ak-hint">规则会同时匹配填写域名及其子域名，新建或编辑短链时生效。</p></section>
    <section class="ak-card ak-table-card" style="margin-top:18px;"><div class="ak-table-wrap"><table class="ak-table"><thead><tr><th>域名</th><th>备注</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead><tbody>
    <?php $has_domains = false; while ($domain = $db->fetch_array($domains)): $has_domains = true; ?><tr><td><code><?php echo htmlspecialchars($domain['hostname'], ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars($domain['note'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo $domain['enabled'] ? '启用' : '已停用'; ?></td><td><?php echo htmlspecialchars($domain['created_at'], ENT_QUOTES, 'UTF-8'); ?></td><td><div class="ak-table-actions"><?php if ($domain['enabled']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="disable"><input type="hidden" name="domain_id" value="<?php echo (int) $domain['id']; ?>"><button class="ak-button ak-button-secondary" type="submit">停用</button></form><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="domain_id" value="<?php echo (int) $domain['id']; ?>"><button class="ak-button ak-button-danger" type="submit">删除</button></form></div></td></tr><?php endwhile; ?>
    <?php if (!$has_domains): ?><tr><td colspan="5" class="ak-empty">暂无黑名单规则</td></tr><?php endif; ?></tbody></table></div></section>
</div></main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
