<?php

include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/platform.php';
include_once __DIR__ . '/admin/functions/time.php';

ensure_platform_schema($db);

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$alias = isset($_GET['id']) ? trim($_GET['id']) : '';
$alias_sql = $db->escape_value($alias);
$result = $db->query("SELECT URL, date, hits, pass, last_visit, status, expires_at, max_hits FROM links WHERE BINARY link = '$alias_sql'");
$link = $db->fetch_array($result);

if (!$link || $link['URL'] === '') {
    $error_msg = '找不到您跳转的链接';
    include __DIR__ . '/functions/error.php';
    $db->close_connection();
    exit;
}

$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$created_link = short_link_url($alias);
$stats_link = public_url('stats.php?id=' . rawurlencode($alias));
$click_total = 0;
$daily = array();
$referrers = array();
$devices = array();
$browsers = array();
$operating_systems = array();
$clicks = $db->query("SELECT DATE(clicked_at) AS day, referrer, device_type, browser, operating_system FROM shortny_clicks WHERE link_id=" . (int) $link['id'] . " AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY clicked_at DESC");
while ($click = $db->fetch_array($clicks)) {
    $click_total++;
    $day = (string) $click['day'];
    $daily[$day] = isset($daily[$day]) ? $daily[$day] + 1 : 1;
    $referrer = trim((string) $click['referrer']);
    $referrer = $referrer === '' ? '直接访问' : (parse_url($referrer, PHP_URL_HOST) ?: '其他来源');
    $devices[(string) $click['device_type']] = isset($devices[(string) $click['device_type']]) ? $devices[(string) $click['device_type']] + 1 : 1;
    $browsers[(string) $click['browser']] = isset($browsers[(string) $click['browser']]) ? $browsers[(string) $click['browser']] + 1 : 1;
    $operating_systems[(string) $click['operating_system']] = isset($operating_systems[(string) $click['operating_system']]) ? $operating_systems[(string) $click['operating_system']] + 1 : 1;
    $referrers[$referrer] = isset($referrers[$referrer]) ? $referrers[$referrer] + 1 : 1;
}
ksort($daily);
arsort($referrers);
arsort($devices);
arsort($browsers);
arsort($operating_systems);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>短链统计 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">Link analytics</p>
        <h1 class="ak-page-heading">短链统计</h1>
        <section class="ak-card ak-card-body">
            <div class="ak-result-url">
                <input id="stats-url" class="ak-input" readonly value="<?php echo htmlspecialchars($created_link, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="ak-button ak-button-primary" type="button" id="copy-button">
                    <span class="ak-material" aria-hidden="true">content_copy</span>复制
                </button>
            </div>
            <div class="ak-result-grid" style="margin-top:24px;">
                <article class="ak-result-panel">
                    <h2 class="ak-modal-title">短链二维码</h2>
                    <img class="ak-qr" alt="短链二维码" src="qr/api.php?text=<?php echo rawurlencode($created_link); ?>&size=220">
                </article>
                <article class="ak-result-panel">
                    <h2 class="ak-modal-title">访问数据</h2>
                    <dl class="ak-detail-list">
                        <div><dt>总点击量</dt><dd><?php echo (int) $link['hits']; ?></dd></div>
                        <div><dt>近 30 天可分析点击</dt><dd><?php echo (int) $click_total; ?></dd></div>
                        <div><dt>状态</dt><dd><?php echo $link['status'] === 'paused' ? '已暂停' : (!empty($link['expires_at']) && strtotime($link['expires_at']) <= time() ? '已过期' : '正常'); ?></dd></div>
                        <div><dt>最后访问</dt><dd title="<?php echo htmlspecialchars($link['last_visit'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(time_ago($link['last_visit']), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        <div><dt>创建日期</dt><dd><?php echo htmlspecialchars(date('Y年m月d日', strtotime($link['date'])), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                    </dl>
                    <a class="ak-button ak-button-secondary" href="<?php echo htmlspecialchars($created_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">打开短链</a>
                </article>
            </div>
            <p class="ak-page-copy" style="margin:22px 0 0;">统计链接：<a href="<?php echo htmlspecialchars($stats_link, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($stats_link, ENT_QUOTES, 'UTF-8'); ?></a></p>
        </section>
        <section class="ak-card ak-card-body" style="margin-top:18px;">
            <div class="ak-admin-toolbar" style="margin-bottom:16px;"><div><p class="ak-eyebrow">Last 30 days</p><h2 class="ak-modal-title">访问分析</h2></div><span class="ak-admin-muted">不保存明文 IP</span></div>
            <div class="ak-form-grid">
                <div><h3 class="ak-section-title">每日点击</h3><div class="ak-mini-list"><?php foreach ($daily as $day => $count): ?><div><span><?php echo htmlspecialchars($day, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $count; ?></strong></div><?php endforeach; ?><?php if (empty($daily)): ?><p class="ak-empty">暂无分析数据</p><?php endif; ?></div></div>
                <div><h3 class="ak-section-title">来源</h3><div class="ak-mini-list"><?php foreach ($referrers as $name => $count): ?><div><span><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $count; ?></strong></div><?php endforeach; ?><?php if (empty($referrers)): ?><p class="ak-empty">暂无来源数据</p><?php endif; ?></div></div>
                <div><h3 class="ak-section-title">设备</h3><div class="ak-mini-list"><?php foreach ($devices as $name => $count): ?><div><span><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $count; ?></strong></div><?php endforeach; ?></div></div>
                <div><h3 class="ak-section-title">浏览器 / 系统</h3><div class="ak-mini-list"><?php foreach ($browsers as $name => $count): ?><div><span><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $count; ?></strong></div><?php endforeach; ?><?php foreach ($operating_systems as $name => $count): ?><div><span><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo (int) $count; ?></strong></div><?php endforeach; ?></div></div>
            </div>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
<script>
document.getElementById('copy-button').addEventListener('click', function () {
    var input = document.getElementById('stats-url');
    navigator.clipboard.writeText(input.value).then(function () {
        document.getElementById('copy-button').lastChild.textContent = '已复制';
    });
});
</script>
</body>
</html>
<?php $db->close_connection(); ?>
