<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>跳转中 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
    <style><?php echo $info['cstm-style']; ?></style>
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-main">
    <div class="ak-content-wide">
        <section class="ak-card ak-card-body" style="text-align:center;">
            <p class="ak-eyebrow">Preparing redirect</p>
            <h1 class="ak-page-heading" style="font-size:30px;">即将跳转</h1>
            <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;">将在 <strong id="counter">3</strong> 秒后自动跳转到目标网址。</p>
            <div class="ak-result-grid" style="margin-top:28px;text-align:left;">
                <article class="ak-result-panel">
                    <h2 class="ak-modal-title">广告</h2>
                    <div class="ak-ad"><?php echo $ads_info['ad3']; ?></div>
                </article>
                <article class="ak-result-panel">
                    <h2 class="ak-modal-title">广告</h2>
                    <div class="ak-ad"><?php echo $ads_info['ad4']; ?></div>
                </article>
            </div>
            <p id="redirect-status" class="ak-hint">请稍候</p>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
<script>
    (function () {
        var counter = document.getElementById('counter');
        var status = document.getElementById('redirect-status');
        var target = <?php echo json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        function countdown() {
            var remaining = parseInt(counter.textContent, 10);
            if (remaining <= 0) {
                status.textContent = '正在打开目标网址';
                window.location.href = target;
                return;
            }
            counter.textContent = String(remaining - 1);
        }
        window.setInterval(countdown, 1000);
    }());
</script>
</body>
</html>
