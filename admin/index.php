<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';
if (!admin_session_active()) {
    header('Location: login.php');
    exit;
}

include "../functions/database.php";
include "../functions/count.php";
include "functions/time.php";

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
ensure_user_schema($db);
$pending_users = $db->query("SELECT COUNT(*) AS total FROM shortny_users WHERE status='pending'");
$pending_user_count = (int) $db->fetch_array($pending_users)['total'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理面板 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-admin-main">
    <div class="ak-shell">
        <div class="ak-admin-toolbar">
            <div>
                <p class="ak-eyebrow">AzureKiln Link</p>
                <h1 class="ak-admin-title">运营概览</h1>
            </div>
            <span class="ak-admin-muted">欢迎，<?php echo htmlspecialchars($_SESSION['valid_user'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <section class="ak-stat-grid" aria-label="短链统计">
            <article class="ak-card ak-stat">
                <div class="ak-stat-label">总点击</div>
                <div class="ak-stat-value"><?php echo (int) $num_rows3; ?></div>
            </article>
            <article class="ak-card ak-stat">
                <div class="ak-stat-label">总计转址</div>
                <div class="ak-stat-value"><?php echo (int) $num_rows1; ?></div>
            </article>
            <article class="ak-card ak-stat">
                <div class="ak-stat-label">今日转址</div>
                <div class="ak-stat-value"><?php echo (int) $num_rows2; ?></div>
            </article>
            <article class="ak-card ak-stat">
                <div class="ak-stat-label">待审核用户</div>
                <div class="ak-stat-value"><a href="users.php"><?php echo $pending_user_count; ?></a></div>
            </article>
        </section>

        <section class="ak-card ak-table-card" aria-labelledby="latest-links-title">
            <div class="ak-admin-toolbar" style="padding: 22px 22px 0; margin-bottom: 0;">
                <h2 id="latest-links-title" class="ak-admin-title" style="font-size: 22px;">最新链接</h2>
                <a class="ak-button ak-button-secondary" href="links.php">
                    <span class="ak-material" aria-hidden="true">list</span>
                    查看全部
                </a>
            </div>
            <div class="ak-table-wrap">
                <table class="ak-table">
                    <thead>
                        <tr>
                            <th>目标网址</th>
                            <th>短链</th>
                            <th>创建日期</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = $db->query("SELECT * FROM links ORDER BY date DESC LIMIT 10");
                    while ($row = $db->fetch_array($result)) {
                        $url = (string) $row['URL'];
                        $short_url = short_link_url($row['link']);
                        $display_url = mb_strlen($url) > 50 ? mb_substr($url, 0, 50) . '...' : $url;
                        ?>
                        <tr>
                            <td><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($display_url, ENT_QUOTES, 'UTF-8'); ?></a></td>
                            <td><a href="<?php echo htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                            <td><?php echo htmlspecialchars(time_ago($row['date']), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

        <form class="ak-clean-form" target="_blank" action="clean.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <span>删除上次访问时间早于</span>
            <input class="ak-input" type="number" min="0" name="d" aria-label="天数">
            <span>天的链接</span>
            <button type="submit" name="submit" class="ak-button ak-button-danger">
                <span class="ak-material" aria-hidden="true">delete_sweep</span>
                删除
            </button>
        </form>
    </div>
</main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
