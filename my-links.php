<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/platform.php';
include_once __DIR__ . '/functions/shortlink.php';
include_once __DIR__ . '/functions/user_auth.php';

ensure_platform_schema($db);
$user = current_user_record($db);
$notice = '';
$notice_type = 'success';
$new_api_key = '';

if (!$user || $user['status'] !== 'approved') {
    http_response_code($user ? 403 : 401);
    $error_msg = $user ? '账号通过审核后才能管理短链。' : '请先使用 OAuth2 登录。';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $notice = '请求已过期，请刷新页面后重试。';
        $notice_type = 'danger';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        $user_id = (int) $user['id'];
        $link_id = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        if ($action === 'create_key') {
            if (!rate_limit_allow($db, 'api-key-create', 5, 3600)) {
                $notice = 'API Key 创建过于频繁，请稍后再试。';
                $notice_type = 'danger';
            } else {
                $new_api_key = create_user_api_key($db, $user_id, isset($_POST['label']) ? $_POST['label'] : '');
                audit_event($db, 'user', $user_id, 'api_key.created', 'api_key', null, array('label' => isset($_POST['label']) ? substr((string) $_POST['label'], 0, 100) : ''));
                $notice = 'API Key 已创建。完整密钥只在本次显示，请立即保存。';
            }
        } elseif ($action === 'revoke_key') {
            $key_id = isset($_POST['key_id']) ? (int) $_POST['key_id'] : 0;
            if ($key_id < 1) {
                $notice = '无效的 API Key。';
                $notice_type = 'danger';
            } else {
                $db->query("UPDATE shortny_api_keys SET revoked_at=NOW() WHERE id=$key_id AND user_id=$user_id AND revoked_at IS NULL");
                audit_event($db, 'user', $user_id, 'api_key.revoked', 'api_key', $key_id);
                $notice = 'API Key 已撤销。';
            }
        } elseif ($link_id < 1) {
            $notice = '无效的短链。';
            $notice_type = 'danger';
        } else {
            $link_result = $db->query("SELECT * FROM links WHERE id=$link_id AND owner_user_id=$user_id LIMIT 1");
            $owned_link = $db->fetch_array($link_result);
            if (!$owned_link) {
                $notice = '找不到这条短链，或你没有修改权限。';
                $notice_type = 'danger';
            } elseif ($action === 'toggle') {
                $next_status = $owned_link['status'] === 'paused' ? 'active' : 'paused';
                $next_status_sql = $db->escape_value($next_status);
                $db->query("UPDATE links SET status='$next_status_sql' WHERE id=$link_id AND owner_user_id=$user_id");
                audit_event($db, 'user', $user_id, 'link.' . ($next_status === 'active' ? 'resumed' : 'paused'), 'link', $link_id);
                $notice = $next_status === 'active' ? '短链已恢复。' : '短链已暂停。';
            } elseif ($action === 'delete') {
                $db->query("DELETE FROM shortny_clicks WHERE link_id=$link_id");
                $db->query("DELETE FROM links WHERE id=$link_id AND owner_user_id=$user_id");
                audit_event($db, 'user', $user_id, 'link.deleted', 'link', $link_id, array('alias' => $owned_link['link']));
                $notice = '短链已删除。';
            } elseif ($action === 'update') {
                $url = isset($_POST['longurl']) ? trim((string) $_POST['longurl']) : '';
                $url_error = validate_destination_url($url);
                $options = normalize_link_options(isset($_POST['expires_at']) ? $_POST['expires_at'] : '', isset($_POST['max_hits']) ? $_POST['max_hits'] : null);
                if ($url_error !== '') {
                    $notice = $url_error;
                    $notice_type = 'danger';
                } elseif (!$options['success']) {
                    $notice = $options['error'];
                    $notice_type = 'danger';
                } elseif (destination_domain_blocked($db, normalized_url_host($url))) {
                    $notice = '这个目标域名已被管理员禁止。';
                    $notice_type = 'danger';
                } elseif ($options['max_hits'] !== null && $options['max_hits'] < (int) $owned_link['hits']) {
                    $notice = '最大点击数不能小于已经产生的点击数。';
                    $notice_type = 'danger';
                } else {
                    $url_sql = $db->escape_value($url);
                    $expires_sql = $options['expires_at'] === '' ? 'NULL' : "'" . $db->escape_value($options['expires_at']) . "'";
                    $max_hits_sql = $options['max_hits'] === null ? 'NULL' : (string) $options['max_hits'];
                    $db->query("UPDATE links SET URL='$url_sql', expires_at=$expires_sql, max_hits=$max_hits_sql WHERE id=$link_id AND owner_user_id=$user_id");
                    audit_event($db, 'user', $user_id, 'link.updated', 'link', $link_id, array('alias' => $owned_link['link']));
                    $notice = '短链设置已更新。';
                }
            } else {
                $notice = '不支持的操作。';
                $notice_type = 'danger';
            }
        }
    }
}

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$page = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
$page_size = 20;
$search_sql = $db->escape_value($search);
$link_where = "owner_user_id=" . (int) $user['id'];
if ($search !== '') {
    $link_where .= " AND (link LIKE '%$search_sql%' OR URL LIKE '%$search_sql%')";
}
$link_count_result = $db->query("SELECT COUNT(*) AS total FROM links WHERE $link_where");
$link_count_row = $db->fetch_array($link_count_result);
$link_count = (int) $link_count_row['total'];
$link_offset = $page * $page_size;
$links = $db->query("SELECT * FROM links WHERE $link_where ORDER BY date DESC LIMIT $link_offset, $page_size");
$keys = $db->query("SELECT id, label, key_prefix, created_at, last_used_at, revoked_at FROM shortny_api_keys WHERE user_id=" . (int) $user['id'] . " ORDER BY created_at DESC");
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>我的短链 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content-wide">
        <div class="ak-admin-toolbar">
            <div><p class="ak-eyebrow">Link workspace</p><h1 class="ak-page-heading">我的短链</h1></div>
            <a class="ak-button ak-button-secondary" href="index.php"><span class="ak-material" aria-hidden="true">add_link</span>创建短链</a>
        </div>
        <?php if ($notice !== ''): ?><div class="ak-notice ak-notice-<?php echo htmlspecialchars($notice_type, ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($new_api_key !== ''): ?><section class="ak-card ak-card-body"><h2 class="ak-modal-title">新的 API Key</h2><p class="ak-page-copy">这串密钥不会再次显示，请现在复制并妥善保存。</p><code class="ak-key-reveal"><?php echo htmlspecialchars($new_api_key, ENT_QUOTES, 'UTF-8'); ?></code></section><?php endif; ?>
        <form class="ak-form-actions" method="get" style="justify-content:flex-start;margin-bottom:18px;"><input class="ak-input" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="搜索短链或目标网址"><button class="ak-button ak-button-secondary" type="submit"><span class="ak-material" aria-hidden="true">search</span>搜索</button><?php if ($search !== ''): ?><a class="ak-button ak-button-secondary" href="my-links.php">清除</a><?php endif; ?></form>
        <section class="ak-card ak-table-card">
            <div class="ak-table-wrap">
                <table class="ak-table" style="min-width:1060px;">
                    <thead><tr><th>短链</th><th>目标网址</th><th>状态 / 限制</th><th>点击</th><th>编辑</th><th>操作</th></tr></thead>
                    <tbody>
                    <?php $has_links = false; while ($link = $db->fetch_array($links)): $has_links = true; $expired = !empty($link['expires_at']) && strtotime($link['expires_at']) <= time(); ?>
                        <tr>
                            <td><a href="<?php echo htmlspecialchars(short_link_url($link['link']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($link['link'], ENT_QUOTES, 'UTF-8'); ?></a><br><a class="ak-admin-muted" href="stats.php?id=<?php echo rawurlencode($link['link']); ?>">查看统计</a></td>
                            <td><span class="ak-url-cell"><?php echo htmlspecialchars($link['URL'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><span class="ak-status ak-status-<?php echo $link['status'] === 'paused' || $expired ? 'suspended' : 'approved'; ?>"><?php echo $expired ? '已过期' : ($link['status'] === 'paused' ? '已暂停' : '正常'); ?></span><br><small><?php echo $link['max_hits'] === null ? '不限点击' : '上限 ' . (int) $link['max_hits']; ?><?php echo !empty($link['expires_at']) ? ' · ' . htmlspecialchars($link['expires_at'], ENT_QUOTES, 'UTF-8') : ''; ?></small></td>
                            <td><?php echo (int) $link['hits']; ?></td>
                            <td>
                                <form class="ak-form-stack" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="link_id" value="<?php echo (int) $link['id']; ?>">
                                    <input class="ak-input" type="url" name="longurl" value="<?php echo htmlspecialchars($link['URL'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <input class="ak-input" type="datetime-local" name="expires_at" value="<?php echo !empty($link['expires_at']) ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($link['expires_at'])), ENT_QUOTES, 'UTF-8') : ''; ?>">
                                    <input class="ak-input" type="number" name="max_hits" min="<?php echo max(1, (int) $link['hits']); ?>" placeholder="不限点击" value="<?php echo $link['max_hits'] === null ? '' : (int) $link['max_hits']; ?>">
                                    <button class="ak-button ak-button-secondary" type="submit"><span class="ak-material" aria-hidden="true">save</span>保存</button>
                                </form>
                            </td>
                            <td><div class="ak-table-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="link_id" value="<?php echo (int) $link['id']; ?>"><button class="ak-button ak-button-secondary" type="submit"><?php echo $link['status'] === 'paused' ? '恢复' : '暂停'; ?></button></form><form method="post" onsubmit="return window.confirm('确定删除这个短链吗？');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="link_id" value="<?php echo (int) $link['id']; ?>"><button class="ak-button ak-button-danger" type="submit"><span class="ak-material" aria-hidden="true">delete</span>删除</button></form></div></td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_links): ?><tr><td colspan="6" class="ak-empty">你还没有创建短链</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php if ($link_count > $page_size): ?><nav class="ak-pagination" aria-label="我的短链分页"><?php if ($page > 0): ?><a class="ak-button ak-button-secondary" href="my-links.php?q=<?php echo rawurlencode($search); ?>&page=<?php echo $page - 1; ?>">上一页</a><?php endif; ?><span class="ak-admin-muted">第 <?php echo $page + 1; ?> / <?php echo (int) ceil($link_count / $page_size); ?> 页</span><?php if ($link_offset + $page_size < $link_count): ?><a class="ak-button ak-button-secondary" href="my-links.php?q=<?php echo rawurlencode($search); ?>&page=<?php echo $page + 1; ?>">下一页</a><?php endif; ?></nav><?php endif; ?>
        <section class="ak-card ak-card-body" style="margin-top:18px;">
            <div class="ak-admin-toolbar"><div><p class="ak-eyebrow">Developer access</p><h2 class="ak-modal-title">API Key</h2></div></div>
            <form class="ak-form-actions" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="create_key"><input class="ak-input" type="text" name="label" maxlength="100" placeholder="名称，例如：个人脚本"><button class="ak-button ak-button-primary" type="submit"><span class="ak-material" aria-hidden="true">key</span>创建 Key</button></form>
            <div class="ak-table-wrap" style="margin-top:18px;"><table class="ak-table"><thead><tr><th>名称</th><th>前缀</th><th>创建时间</th><th>最近使用</th><th>状态</th><th></th></tr></thead><tbody>
            <?php $has_keys = false; while ($key = $db->fetch_array($keys)): $has_keys = true; ?><tr><td><?php echo htmlspecialchars($key['label'] ?: '未命名', ENT_QUOTES, 'UTF-8'); ?></td><td><code><?php echo htmlspecialchars($key['key_prefix'], ENT_QUOTES, 'UTF-8'); ?>…</code></td><td><?php echo htmlspecialchars($key['created_at'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($key['last_used_at'] ?: '尚未使用', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo $key['revoked_at'] ? '已撤销' : '有效'; ?></td><td><?php if (!$key['revoked_at']): ?><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="revoke_key"><input type="hidden" name="key_id" value="<?php echo (int) $key['id']; ?>"><button class="ak-button ak-button-danger" type="submit">撤销</button></form><?php endif; ?></td></tr><?php endwhile; ?>
            <?php if (!$has_keys): ?><tr><td colspan="6" class="ak-empty">暂无 API Key</td></tr><?php endif; ?></tbody></table></div>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
