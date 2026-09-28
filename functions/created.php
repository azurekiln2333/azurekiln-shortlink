<?php

$stats_url = public_url('stats.php?id=' . rawurlencode($rand1));
$qr_url = public_url('qr/api.php?text=' . rawurlencode($created_link) . '&size=220');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>生成成功 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">Link created</p>
        <h1 class="ak-page-heading">短链接已生成</h1>
        <section class="ak-card ak-card-body">
            <p class="ak-page-copy" style="margin-top:0;">保存下面的短链，之后可以通过任意绑定到本站的域名访问。</p>
            <div class="ak-result-url">
                <input id="created-url" class="ak-input" readonly value="<?php echo htmlspecialchars($created_link, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="ak-button ak-button-primary" type="button" id="copy-button"><span class="ak-material" aria-hidden="true">content_copy</span>复制</button>
            </div>
            <div class="ak-result-grid" style="margin-top:28px;">
                <div class="ak-result-panel">
                    <h2 class="ak-modal-title">二维码</h2>
                    <img class="ak-qr" alt="短链接二维码" src="<?php echo htmlspecialchars($qr_url, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="ak-result-panel">
                    <h2 class="ak-modal-title">专属统计链接</h2>
                    <p class="ak-page-copy" style="margin:12px 0 18px;">访问统计页可以查看点击次数和最后访问时间。</p>
                    <a class="ak-button ak-button-secondary" href="<?php echo htmlspecialchars($stats_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">打开统计</a>
                </div>
            </div>
            <p class="ak-alert" style="margin-top:28px;">请勿使用本站生成违法、恶意或垃圾链接。发现违规链接将被删除。</p>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
<script>
document.getElementById('copy-button').addEventListener('click', function () {
    var button = this;
    navigator.clipboard.writeText(document.getElementById('created-url').value).then(function () { button.lastChild.textContent = '已复制'; });
});
</script>
</body>
</html>
