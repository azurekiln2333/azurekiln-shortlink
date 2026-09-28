<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>受保护的链接 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <section class="ak-card ak-card-body" style="max-width:520px;margin:0 auto;">
            <p class="ak-eyebrow">Protected link</p>
            <h1 class="ak-page-heading" style="font-size:30px;">请输入访问密码</h1>
            <form class="ak-form-stack" method="post" action="<?php echo htmlspecialchars(short_link_url($alias), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-field">
                    <label class="ak-label" for="txtpass">链接密码</label>
                    <input class="ak-input" type="password" id="txtpass" name="txtpass" required autofocus autocomplete="current-password">
                </div>
                <button class="ak-button ak-button-primary" type="submit"><span class="ak-material" aria-hidden="true">lock_open</span>继续访问</button>
            </form>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
