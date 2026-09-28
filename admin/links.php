<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';
if (!admin_session_active()) {
    header("Location: login.php");
    exit;
}
include "functions/time.php";
include "../functions/database.php";
include_once __DIR__ . '/../functions/platform.php';
ensure_platform_schema($db);

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$rec_limit = 10;
$page = isset($_GET['page']) ? max(0, (int) $_GET['page']) : 0;
$offset = $rec_limit * $page;

$count_result = $db->query("SELECT count(id) AS total FROM links");
$count_row = $db->fetch_array($count_result);
$rec_count = (int) $count_row['total'];
$result = $db->query("SELECT id, URL, hits, link, date, last_visit, status, expires_at, max_hits, owner_user_id FROM links ORDER BY date DESC LIMIT $offset, $rec_limit");
$has_rows = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>链接 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-admin-main">
    <div class="ak-shell">
        <div class="ak-admin-toolbar">
            <div>
                <p class="ak-eyebrow">Link management</p>
                <h1 class="ak-admin-title">链接管理</h1>
            </div>
            <span class="ak-admin-muted">共 <?php echo $rec_count; ?> 条链接</span>
        </div>

        <section class="ak-card ak-table-card">
            <div class="ak-table-wrap">
                <table id="links-table" class="ak-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>目标网址</th>
                            <th>短链</th>
                            <th>点击次数</th>
                            <th>状态 / 限制</th>
                            <th>归属</th>
                            <th>创建日期</th>
                            <th>上次访问</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $db->fetch_array($result)): $has_rows = true;
                        $target = (string) $row['URL'];
                        $target_display = mb_strlen($target) > 64 ? mb_substr($target, 0, 64) . '...' : $target;
                        $short_url = short_link_url($row['link']);
                    ?>
                        <tr id="link-row-<?php echo (int) $row['id']; ?>">
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($target_display, ENT_QUOTES, 'UTF-8'); ?></a></td>
                            <td><a href="<?php echo htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                            <td><?php echo (int) $row['hits']; ?></td>
                            <td><?php $expired = !empty($row['expires_at']) && strtotime($row['expires_at']) <= time(); ?><span class="ak-status ak-status-<?php echo $row['status'] === 'paused' || $expired ? 'suspended' : 'approved'; ?>"><?php echo $expired ? '已过期' : ($row['status'] === 'paused' ? '已暂停' : '正常'); ?></span><br><small><?php echo $row['max_hits'] === null ? '不限点击' : '上限 ' . (int) $row['max_hits']; ?></small></td>
                            <td><?php echo $row['owner_user_id'] ? '用户 #' . (int) $row['owner_user_id'] : '历史链接'; ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(time_ago($row['last_visit']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button type="button" class="ak-icon-button ak-delete-link" data-id="<?php echo (int) $row['id']; ?>" data-csrf="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" aria-label="删除链接" title="删除链接">
                                    <span class="ak-material" aria-hidden="true">delete</span>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has_rows): ?>
                        <tr><td class="ak-empty" colspan="9">暂无短链</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($rec_count > $rec_limit): ?>
        <nav class="ak-pagination" aria-label="链接分页">
            <?php if ($page > 0): ?><a class="ak-button ak-button-secondary" href="links.php?page=<?php echo $page - 1; ?>">上一页</a><?php endif; ?>
            <span class="ak-admin-muted">第 <?php echo $page + 1; ?> / <?php echo (int) ceil($rec_count / $rec_limit); ?> 页</span>
            <?php if (($offset + $rec_limit) < $rec_count): ?><a class="ak-button ak-button-secondary" href="links.php?page=<?php echo $page + 1; ?>">下一页</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</main>
<script src="../js/jquery-1.10.2.js"></script>
<script src="../js/jquery.tablesorter.js"></script>
<script src="../js/azurekiln-theme.js"></script>
<script>
    $(function () {
        $('#links-table').tablesorter({ debug: false });
        $('.ak-delete-link').on('click', function () {
            var button = $(this);
            var id = button.data('id');
            if (!window.confirm('确定删除这个短链吗？此操作无法撤销。')) return;
            $.post('functions/delete.php', { ccz: id, csrf_token: button.data('csrf') }).done(function () {
                $('#link-row-' + id).fadeOut(180, function () { $(this).remove(); });
            });
        });
    });
</script>
</body>
</html>
<?php $db->close_connection(); ?>
