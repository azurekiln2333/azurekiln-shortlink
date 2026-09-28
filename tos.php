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
    <title>使用许可 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">Terms of service</p>
        <h1 class="ak-page-heading">使用许可</h1>
        <section class="ak-card ak-card-body ak-prose">
            <h2>禁止内容</h2>
            <ul>
                <li>不得生成垃圾邮件、钓鱼或欺诈网站链接。</li>
                <li>不得生成病毒、恶意软件或其他危害用户设备的链接。</li>
                <li>不得生成违反法律法规或侵犯他人权益的链接。</li>
            </ul>
            <h2>服务说明</h2>
            <p>短链由用户创建，本站仅提供技术转发服务。对于违规链接，本站可以在不另行通知的情况下删除并限制相关访问。</p>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
