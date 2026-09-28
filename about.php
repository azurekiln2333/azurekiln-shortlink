<?php

include_once __DIR__ . '/functions/database.php';
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>关于我们 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">About AzureKiln Link</p>
        <h1 class="ak-page-heading">关于我们</h1>
        <section class="ak-card ak-card-body ak-prose">
            <h2>短链与网址转发服务</h2>
            <p>本站提供网址缩短、可选密码保护和访问统计服务。短链只负责转发，不代表本站认可目标网址的内容。</p>
            <h2>使用提醒</h2>
            <p>请勿生成违法、恶意、钓鱼或垃圾链接。发现违规内容后，本站会删除相关短链并保留必要的处理权利。</p>
            <p><a class="ak-button ak-button-secondary" href="admin/login.php">进入管理后台</a></p>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
