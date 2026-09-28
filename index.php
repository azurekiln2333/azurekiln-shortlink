<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
if (!(include "functions/database.php")) {
    echo "<a href='install/index.html'>Please Install the script first or make sure it is installed correctly.</a>";
    exit;
}
include_once __DIR__ . '/functions/user_auth.php';
include "functions/count.php";

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$ads = $db->query("SELECT * FROM ads");
$ads_info = $db->fetch_array($ads);
$current_user = current_user_record($db);
$creator = approved_user_or_admin($db);
$can_create = $creator !== null;
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$base_url = public_base_url();
$logo_url = isset($info['logo_url']) ? $info['logo_url'] : '';
if (strpos($logo_url, ';') !== false) {
    $logo_url = explode(';', $logo_url);
}
if (!is_array($logo_url)) {
    $logo_url = array($logo_url, $logo_url);
}
if (empty($logo_url[1])) {
    $logo_url[1] = $logo_url[0];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="AzureKiln 短链接服务，快速生成可统计、可加密的短网址。">
    <meta property="og:title" content="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
    <style><?php echo isset($info['cstm-style']) ? $info['cstm-style'] : ''; ?></style>
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-main">
    <div class="ak-shell">
        <section class="ak-hero" aria-labelledby="page-title">
            <p class="ak-eyebrow">AzureKiln Link</p>
            <h1 id="page-title" class="ak-title">让每个链接更短、更清晰</h1>
            <p class="ak-subtitle">生成轻量短网址，支持自定义后缀、密码保护和访问统计。</p>
        </section>

        <section class="ak-card ak-form-card" aria-label="创建短链接">
            <?php if ($can_create): ?>
            <form action="create.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-form-row">
                    <div class="ak-field">
                        <label class="ak-label" for="urlbox">目标网址</label>
                        <input id="urlbox" class="ak-input ak-input-large" name="longurl" value="" type="url" required placeholder="https://example.com/your-long-url" autocomplete="url">
                    </div>
                    <div class="ak-field">
                        <span class="ak-label" aria-hidden="true">&nbsp;</span>
                        <button class="ak-button ak-button-primary" type="submit">
                            <span class="ak-material" aria-hidden="true">link</span>
                            立即生成
                        </button>
                    </div>
                </div>
                <div class="ak-field-grid">
                    <div class="ak-field">
                        <label class="ak-label" for="cust">自定义后缀（可选）</label>
                        <div class="ak-input-with-prefix">
                            <span class="ak-prefix"><?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>/</span>
                            <input type="text" id="cust" name="cust" class="ak-input" data-validation="alphanumeric" data-validation-allowing="-_" data-validation-optional="true" placeholder="例如：docs" autocomplete="off">
                        </div>
                    </div>
                    <div class="ak-field">
                        <label class="ak-label" for="pass">链接密码（可选）</label>
                        <input type="password" id="pass" name="pass" class="ak-input" data-validation="alphanumeric" data-validation-allowing="-_" data-validation-optional="true" placeholder="留空表示无需密码" autocomplete="new-password">
                    </div>
                </div>
                <div class="ak-field-grid">
                    <div class="ak-field">
                        <label class="ak-label" for="expires-at">过期时间（可选）</label>
                        <input type="datetime-local" id="expires-at" name="expires_at" class="ak-input">
                    </div>
                    <div class="ak-field">
                        <label class="ak-label" for="max-hits">最大点击数（可选）</label>
                        <input type="number" id="max-hits" name="max_hits" class="ak-input" min="1" max="2147483647" placeholder="不限次数">
                    </div>
                </div>
            </form>
            <?php elseif ($current_user): ?>
                <p class="ak-eyebrow">Account review</p>
                <h2 class="ak-modal-title">账号正在审核</h2>
                <p class="ak-page-copy" style="margin:12px auto 18px;text-align:center;">当前账号状态为“<?php echo htmlspecialchars($current_user['status'], ENT_QUOTES, 'UTF-8'); ?>”，管理员审核通过后才能生成短链。</p>
                <a class="ak-button ak-button-secondary" href="account.php"><span class="ak-material" aria-hidden="true">person</span>查看账户状态</a>
            <?php else: ?>
                <p class="ak-eyebrow">Sign in required</p>
                <h2 class="ak-modal-title">登录后生成短链</h2>
                <p class="ak-page-copy" style="margin:12px auto 18px;text-align:center;">请先使用 OAuth2 登录并等待管理员审核。</p>
                <a class="ak-button ak-button-primary" href="account.php"><span class="ak-material" aria-hidden="true">account_circle</span>登录或注册</a>
            <?php endif; ?>
            <?php if ($can_create): ?><p class="ak-form-actions" style="justify-content:flex-start;margin-top:14px;"><a class="ak-button ak-button-secondary" href="my-links.php"><span class="ak-material" aria-hidden="true">link</span>管理我的短链</a></p><?php endif; ?>
            <p class="ak-hint">目标网址必须包含 http:// 或 https://</p>
            <p class="ak-disclaimer">本站永久免费。短网址由用户生成，所跳转网站内容与本站无关。</p>
        </section>

        <?php if (!empty($ads_info['ad1'])): ?>
            <div class="ak-ad"><?php echo $ads_info['ad1']; ?></div>
        <?php endif; ?>

        <section class="ak-metrics" aria-label="服务统计">
            <article class="ak-card ak-metric">
                <p class="ak-metric-label">总点击量</p>
                <p class="ak-metric-value"><?php echo (int) $num_rows3; ?></p>
            </article>
            <article class="ak-card ak-metric">
                <p class="ak-metric-label">总计转址</p>
                <p class="ak-metric-value"><?php echo (int) $num_rows1; ?></p>
            </article>
            <article class="ak-card ak-metric">
                <p class="ak-metric-label">今日转址</p>
                <p class="ak-metric-value"><?php echo (int) $num_rows2; ?></p>
            </article>
        </section>

        <?php if ((int) ($info['logo_type'] ?? 0) === 1 && !empty($logo_url[0])): ?>
            <div class="ak-ad"><img src="<?php echo htmlspecialchars($logo_url[0], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?> Logo" style="max-width: 180px; max-height: 64px;"></div>
        <?php endif; ?>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
