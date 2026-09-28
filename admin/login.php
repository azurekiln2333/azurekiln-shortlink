<?php
ob_start();
include_once __DIR__ . '/../functions/session.php';
start_app_session();

include_once __DIR__ . '/../functions/database.php';
include_once __DIR__ . '/../functions/oauth2.php';
include_once __DIR__ . '/../functions/platform.php';
include_once __DIR__ . '/../functions/user_auth.php';

if (admin_session_active()) {
    header('Location: index.php');
    exit;
}

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['op']) && $_GET['op'] === 'oauth2') {
    try {
        if (!oauth2_enabled()) {
            throw new Exception('OAuth2 尚未配置管理员白名单或客户端信息。');
        }
        ensure_platform_schema($db);
        if (!rate_limit_allow($db, 'oauth2-admin-start', 10, 600)) {
            throw new Exception('OAuth2 login rate limit exceeded.');
        }
        header('Location: ' . oauth2_begin());
        exit;
    } catch (Exception $exception) {
        $error_message = 'OAuth2 登录暂时不可用，请检查服务端配置后重试。';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['op']) && $_GET['op'] === 'oauth2_callback') {
    try {
        $identity = oauth2_login();
        if (!oauth2_is_admin($identity)) {
            throw new Exception('This OAuth2 account is not an administrator.');
        }

        $admin_user = user_sync_identity($db, $identity, true);
        session_regenerate_id(true);
        $_SESSION['admin_user'] = true;
        $_SESSION['valid_id'] = isset($identity['sub']) ? $identity['sub'] : null;
        $_SESSION['valid_user'] = oauth2_identity_name($identity);
        $_SESSION['valid_time'] = time();
        $_SESSION['oauth2_identity'] = array(
            'sub' => isset($identity['sub']) ? $identity['sub'] : '',
            'email' => isset($identity['email']) ? $identity['email'] : '',
        );
        header('Location: index.php');
        exit;
    } catch (Exception $exception) {
        $error_message = 'OAuth2 登录失败，请重新发起登录或联系管理员。';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['op']) && $_GET['op'] === 'login') {
    if (oauth2_env('ALLOW_LEGACY_ADMIN_LOGIN', '0') !== '1') {
        $error_message = '密码登录已禁用，请使用 OAuth2 登录。';
    } elseif (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error_message = '登录请求已过期，请刷新页面后重试。';
    } else {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if ($user === '' || $pass === '') {
        $error_message = '请输入用户名和密码。';
    } else {
        $user_sql = $db->escape_value($user);
        $sql = $db->query("SELECT admin_user, admin_pass FROM settings WHERE admin_user = '$user_sql'");
        $admin = $db->fetch_array($sql);

        if ($admin && password_verify($pass, $admin['admin_pass'])) {
            session_regenerate_id(true);
            $_SESSION['admin_user'] = true;
            $_SESSION['valid_id'] = $admin['admin_user'];
            $_SESSION['valid_user'] = $admin['admin_user'];
            $_SESSION['valid_time'] = time();
            header('Location: index.php');
            exit;
        }

        $error_message = '用户名或密码错误。';
    }
    }
}

$oauth2_available = false;
try {
    $oauth2_available = oauth2_enabled();
} catch (Exception $exception) {
    $oauth2_available = false;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<header class="ak-header">
    <div class="ak-shell ak-header-inner">
        <a class="ak-brand" href="../index.php">
            <span class="ak-brand-mark" aria-hidden="true">A</span>
            <span class="ak-brand-text"><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <div class="ak-header-actions">
            <button class="ak-icon-button" type="button" data-theme-toggle aria-label="切换深色模式">
                <span class="ak-material" data-theme-icon aria-hidden="true">dark_mode</span>
            </button>
        </div>
    </div>
</header>
<main class="ak-login-wrap">
    <section class="ak-card ak-login-card" aria-labelledby="login-title">
        <p class="ak-eyebrow">AzureKiln Admin</p>
        <h1 id="login-title" class="ak-login-heading">登录管理面板</h1>
        <p class="ak-login-copy">使用管理员账号管理短链接、统计和站点设置。</p>
        <?php if ($error_message !== ''): ?>
            <div class="ak-alert" role="alert"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (oauth2_env('ALLOW_LEGACY_ADMIN_LOGIN', '0') === '1'): ?>
        <div class="ak-divider">管理员密码回退</div>
        <form class="ak-login-form" role="form" method="post" action="?op=login">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="ak-field">
                <label class="ak-label" for="username">用户名</label>
                <input class="ak-input" type="text" name="username" id="username" autocomplete="username" placeholder="请输入用户名" required>
            </div>
            <div class="ak-field">
                <label class="ak-label" for="password">密码</label>
                <input class="ak-input" type="password" name="password" id="password" autocomplete="current-password" placeholder="请输入密码" required>
            </div>
            <div class="ak-login-actions">
                <button type="submit" class="ak-button ak-button-primary">
                    <span class="ak-material" aria-hidden="true">login</span>
                    使用密码登录
                </button>
            </div>
        </form>
        <?php endif; ?>
        <?php if ($oauth2_available): ?>
            <div class="ak-divider">或使用统一身份认证</div>
            <a class="ak-button ak-button-secondary" href="?op=oauth2">
                <span class="ak-material" aria-hidden="true">account_circle</span>
                使用 OAuth2 登录
            </a>
        <?php endif; ?>
    </section>
</main>
<script src="../js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
