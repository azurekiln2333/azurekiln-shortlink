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
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
ensure_user_schema($db);
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $message = '请求已过期，请刷新页面后重试。';
        $message_type = 'danger';
    } elseif ($user_id < 1 || !in_array($action, array('approve', 'suspend', 'reject'), true)) {
        $message = '无效的审核请求。';
        $message_type = 'danger';
    } else {
        $admin_name = $db->escape_value((string) $_SESSION['valid_user']);
        $status = $action === 'approve' ? 'approved' : ($action === 'suspend' ? 'suspended' : 'rejected');
        $status_sql = $db->escape_value($status);
        $approved_at = $status === 'approved' ? 'NOW()' : 'NULL';
        $db->query("UPDATE shortny_users SET status='$status_sql', approved_at=$approved_at, approved_by='$admin_name' WHERE id=$user_id");
        audit_event($db, 'admin', null, 'user.' . $action, 'user', $user_id, array('status' => $status));
        $message = $status === 'approved' ? '用户已批准。' : ($status === 'suspended' ? '用户已暂停。' : '用户已拒绝。');
    }
}

$users = $db->query("SELECT * FROM shortny_users ORDER BY FIELD(status, 'pending', 'approved', 'suspended', 'rejected'), created_at DESC");
$status_labels = array(
    'pending' => '待审核',
    'approved' => '已批准',
    'suspended' => '已暂停',
    'rejected' => '已拒绝',
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>用户审核 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-admin-main">
    <div class="ak-shell">
        <div class="ak-admin-toolbar">
            <div>
                <p class="ak-eyebrow">User moderation</p>
                <h1 class="ak-admin-title">用户审核</h1>
            </div>
            <a class="ak-button ak-button-secondary" href="index.php"><span class="ak-material" aria-hidden="true">arrow_back</span>返回概览</a>
        </div>
        <?php if ($message !== ''): ?><div class="ak-notice ak-notice-<?php echo $message_type; ?>" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <section class="ak-card ak-table-card">
            <div class="ak-table-wrap">
                <table class="ak-table" style="min-width:900px;">
                    <thead><tr><th>用户</th><th>OAuth2 ID</th><th>状态</th><th>注册时间</th><th>最近登录</th><th>操作</th></tr></thead>
                    <tbody>
                    <?php $has_users = false; while ($user = $db->fetch_array($users)): $has_users = true; ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br><span class="ak-admin-muted"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><code class="ak-code"><?php echo htmlspecialchars($user['subject'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><span class="ak-status ak-status-<?php echo htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(isset($status_labels[$user['status']]) ? $status_labels[$user['status']] : $user['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['last_login_at'] ?: '未登录', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="ak-table-actions">
                                    <?php if ($user['status'] !== 'approved'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>"><input type="hidden" name="action" value="approve"><button class="ak-button ak-button-secondary" type="submit">批准</button></form><?php endif; ?>
                                    <?php if ($user['status'] === 'approved'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>"><input type="hidden" name="action" value="suspend"><button class="ak-button ak-button-danger" type="submit">暂停</button></form><?php endif; ?>
                                    <?php if ($user['status'] !== 'rejected'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>"><input type="hidden" name="action" value="reject"><button class="ak-button ak-button-secondary" type="submit">拒绝</button></form><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_users): ?><tr><td colspan="6" class="ak-empty">暂无 OAuth2 用户</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
