<?php
if (!isset($info) || !is_array($info)) {
    $error_settings = $db->query("SELECT * FROM settings");
    $info = $db->fetch_array($error_settings);
}
$error_site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>访问错误 - <?php echo htmlspecialchars($error_site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <section class="ak-card ak-card-body" style="text-align:center;">
            <span class="ak-material" style="font-size:48px;color:var(--ak-danger);" aria-hidden="true">error</span>
            <h1 class="ak-page-heading" style="font-size:30px;margin-top:12px;">无法完成访问</h1>
            <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!empty($ads_info['ad2'])): ?><div class="ak-ad"><?php echo $ads_info['ad2']; ?></div><?php endif; ?>
            <a class="ak-button ak-button-primary" href="<?php echo htmlspecialchars(public_base_url(), ENT_QUOTES, 'UTF-8'); ?>">返回首页</a>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
