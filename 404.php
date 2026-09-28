<?php
include_once __DIR__ . '/functions/url.php';
$error_message = isset($_GET['msg']) ? $_GET['msg'] : '您访问的目录或文件可能已被删除';
$request_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '当前站点';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found - AzureKiln Link</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<header class="ak-header">
    <div class="ak-shell ak-header-inner">
        <a class="ak-brand" href="<?php echo htmlspecialchars(public_base_url(), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="ak-brand-mark" aria-hidden="true">A</span>
            <span class="ak-brand-text">AzureKiln Link</span>
        </a>
        <div class="ak-header-actions">
            <button class="ak-icon-button" type="button" data-theme-toggle aria-label="切换深色模式"><span class="ak-material" data-theme-icon aria-hidden="true">dark_mode</span></button>
        </div>
    </div>
</header>
<main class="ak-main">
    <div class="ak-content">
        <section class="ak-card ak-card-body" style="text-align:center;">
            <span class="ak-material" style="font-size:52px;color:var(--ak-danger);" aria-hidden="true">search_off</span>
            <p class="ak-eyebrow" style="margin-top:14px;">Page not found</p>
            <h1 class="ak-page-heading" style="font-size:42px;">404</h1>
            <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;">如果你认为这是一个错误，请联系管理员。当前站点：<?php echo htmlspecialchars($request_host, ENT_QUOTES, 'UTF-8'); ?></p>
            <a class="ak-button ak-button-primary" href="<?php echo htmlspecialchars(public_base_url(), ENT_QUOTES, 'UTF-8'); ?>"><span class="ak-material" aria-hidden="true">home</span>返回首页</a>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
