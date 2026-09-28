<?php

include_once __DIR__ . '/functions/database.php';
$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$site_url = public_base_url();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API 接口 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">Developer API</p>
        <h1 class="ak-page-heading">API 接口</h1>
        <section class="ak-card ak-card-body ak-prose">
            <p>API 只对已通过管理员审核的 OAuth2 用户开放。浏览器会话请求必须携带 CSRF token；也可以在“我的短链”页面创建 API Key，并使用 Bearer 认证。</p>
            <h2>基础创建</h2>
            <p>接口地址：<code><?php echo htmlspecialchars($site_url . '/api.php', ENT_QUOTES, 'UTF-8'); ?></code></p>
            <p>在同源页面中使用 <code>credentials: 'same-origin'</code>，并将首页表单中的 <code>csrf_token</code> 作为 <code>X-CSRF-Token</code> 请求头发送。</p>
            <p>返回字段包括 <code>shorturl</code>、<code>stats</code> 和 <code>longurl</code>。</p>
            <h2>自定义后缀与密码</h2>
            <p>JSON 请求示例：<code>{"url":"https://example.com","cust":"docs","pass":"secret","csrf_token":"..."}</code></p>
            <p>后缀只能使用字母、数字、下划线和短横线，密码可选。</p>
            <h2>API Key 与生命周期</h2>
            <p>请求头格式：<code>Authorization: Bearer ak_...</code>。API Key 只在创建时显示完整值，撤销后立即失效。创建参数还支持 <code>expires_at</code> 和 <code>max_hits</code>。</p>
            <h2>批量创建</h2>
            <p>向 <code><?php echo htmlspecialchars($site_url . '/api.php', ENT_QUOTES, 'UTF-8'); ?></code> 发送 JSON：</p>
            <pre class="ak-code"><code>{"urls":[{"url":"https://example.com/a","cust":"a"},{"url":"https://example.com/b"}],"csrf_token":"..."}</code></pre>
            <p>单次批量请求最多创建 50 条短链。跨域调用必须把来源加入服务端的 <code>SHORTNY_API_ORIGINS</code> 白名单，不能使用通配符。创建请求超过限额会返回 HTTP 429，未授权返回 HTTP 401。</p>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
