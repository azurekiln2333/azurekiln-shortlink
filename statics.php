<?php

include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/platform.php';
include_once __DIR__ . '/functions/count.php';
include_once __DIR__ . '/admin/functions/time.php';

ensure_platform_schema($db);

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$public_filter = "pass = '' AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())";
$latest = $db->query("SELECT * FROM links WHERE $public_filter ORDER BY date DESC LIMIT 10");
$popular = $db->query("SELECT * FROM links WHERE $public_filter ORDER BY hits DESC, date DESC LIMIT 10");
$recent = $db->query("SELECT * FROM links WHERE $public_filter ORDER BY last_visit DESC LIMIT 10");
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';

function render_statistics_rows($db, $result)
{
    $has_rows = false;
    while ($row = $db->fetch_array($result)) {
        $has_rows = true;
        $target = (string) $row['URL'];
        $target_display = mb_strlen($target) > 64 ? mb_substr($target, 0, 64) . '...' : $target;
        $short_url = short_link_url($row['link']);
        $stats_url = public_url('stats.php?id=' . rawurlencode($row['link']));
        ?>
        <tr>
            <td><a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($target_display, ENT_QUOTES, 'UTF-8'); ?></a></td>
            <td><a href="<?php echo htmlspecialchars($short_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8'); ?></a></td>
            <td><?php echo (int) $row['hits']; ?></td>
            <td><?php echo htmlspecialchars(time_ago($row['last_visit']), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><a class="ak-button ak-button-secondary" href="<?php echo htmlspecialchars($stats_url, ENT_QUOTES, 'UTF-8'); ?>">查看统计</a></td>
        </tr>
        <?php
    }

    if (!$has_rows) {
        echo '<tr><td class="ak-empty" colspan="5">暂无公开短链</td></tr>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>统计 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content-wide">
        <p class="ak-eyebrow">Link activity</p>
        <h1 class="ak-page-heading">短链统计</h1>
        <p class="ak-page-copy">查看公开短链的创建、访问和点击情况。带密码的短链不会出现在公开列表中。</p>

        <nav class="ak-tabs" aria-label="统计视图">
            <a href="#latest" class="active" data-stat-tab="latest">最新地址</a>
            <a href="#popular" data-stat-tab="popular">访问最多</a>
            <a href="#recent" data-stat-tab="recent">最近访问</a>
        </nav>

        <section class="ak-card ak-table-card" data-stat-panel="latest">
            <div class="ak-card-body">
                <h2 class="ak-modal-title">最新地址</h2>
            </div>
            <div class="ak-table-wrap">
                <table class="ak-table">
                    <thead><tr><th>目标网址</th><th>短链</th><th>点击</th><th>最后访问</th><th>操作</th></tr></thead>
                    <tbody><?php render_statistics_rows($db, $latest); ?></tbody>
                </table>
            </div>
        </section>

        <section class="ak-card ak-table-card" data-stat-panel="popular" hidden>
            <div class="ak-card-body"><h2 class="ak-modal-title">访问最多</h2></div>
            <div class="ak-table-wrap">
                <table class="ak-table">
                    <thead><tr><th>目标网址</th><th>短链</th><th>点击</th><th>最后访问</th><th>操作</th></tr></thead>
                    <tbody><?php render_statistics_rows($db, $popular); ?></tbody>
                </table>
            </div>
        </section>

        <section class="ak-card ak-table-card" data-stat-panel="recent" hidden>
            <div class="ak-card-body"><h2 class="ak-modal-title">最近访问</h2></div>
            <div class="ak-table-wrap">
                <table class="ak-table">
                    <thead><tr><th>目标网址</th><th>短链</th><th>点击</th><th>最后访问</th><th>操作</th></tr></thead>
                    <tbody><?php render_statistics_rows($db, $recent); ?></tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
<script>
document.querySelectorAll('[data-stat-tab]').forEach(function (tab) {
    tab.addEventListener('click', function (event) {
        event.preventDefault();
        document.querySelectorAll('[data-stat-tab]').forEach(function (item) { item.classList.remove('active'); });
        document.querySelectorAll('[data-stat-panel]').forEach(function (panel) { panel.hidden = panel.dataset.statPanel !== tab.dataset.statTab; });
        tab.classList.add('active');
        window.history.replaceState(null, '', tab.getAttribute('href'));
    });
});
</script>
</body>
</html>
<?php $db->close_connection(); ?>
