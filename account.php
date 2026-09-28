<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/platform.php';
include_once __DIR__ . '/functions/user_auth.php';

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$site_name = isset($info['name']) && $info['name'] !== '' ? $info['name'] : 'AzureKiln Link';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['op']) && $_GET['op'] === 'logout') {
    if (csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        unset($_SESSION['user_id'], $_SESSION['user_subject'], $_SESSION['user_name']);
        session_regenerate_id(true);
    }
    header('Location: account.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['op']) && $_GET['op'] === 'oauth2') {
    try {
        if (!oauth2_enabled(false)) {
            throw new Exception('OAuth2 is not configured.');
        }
        ensure_platform_schema($db);
        if (!rate_limit_allow($db, 'oauth2-user-start', 10, 600)) {
            throw new Exception('OAuth2 login rate limit exceeded.');
        }
        header('Location: ' . oauth2_begin('user'));
        exit;
    } catch (Exception $exception) {
        $error_message = '统一身份认证暂时不可用，请联系管理员。';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['op']) && $_GET['op'] === 'oauth2_callback') {
    try {
        $identity = oauth2_login('user');
        $user = user_sync_identity($db, $identity, oauth2_is_admin($identity));
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_subject'] = $user['subject'];
        $_SESSION['user_name'] = $user['display_name'];
        header('Location: account.php');
        exit;
    } catch (Exception $exception) {
        $error_message = 'OAuth2 登录失败，请重新发起登录或联系管理员。';
    }
}

$user = current_user_record($db);
$oauth2_available = false;
try {
    $oauth2_available = oauth2_enabled(false);
} catch (Exception $exception) {
    $oauth2_available = false;
}
$status_labels = array(
    'pending' => '等待管理员审核',
    'approved' => '已审核，可以生成短链',
    'suspended' => '账号已被暂停',
    'rejected' => '账号审核未通过',
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>用户登录 - <?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
</head>
<body class="ak-page">
<?php include __DIR__ . '/functions/menu.php'; ?>
<main class="ak-main">
    <div class="ak-content">
        <section class="ak-card ak-card-body" style="max-width:560px;margin:0 auto;text-align:center;">
            <p class="ak-eyebrow">OAuth2 Account</p>
            <h1 class="ak-page-heading" style="font-size:30px;">用户账户</h1>
            <?php if ($error_message !== ''): ?><div class="ak-alert" role="alert"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($user): ?>
                <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;">当前身份：<?php echo htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="ak-notice <?php echo $user['status'] === 'approved' ? 'ak-notice-success' : 'ak-notice-danger'; ?>" role="status"><?php echo htmlspecialchars(isset($status_labels[$user['status']]) ? $status_labels[$user['status']] : $user['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($user['status'] === 'approved'): ?><a class="ak-button ak-button-primary" href="index.php"><span class="ak-material" aria-hidden="true">link</span>去生成短链</a><a class="ak-button ak-button-secondary" href="my-links.php" style="margin-top:10px;"><span class="ak-material" aria-hidden="true">manage_search</span>管理我的短链</a><?php endif; ?>
                <form method="post" action="?op=logout" style="margin-top:12px;"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><button class="ak-button ak-button-secondary" type="submit"><span class="ak-material" aria-hidden="true">logout</span>退出登录</button></form>
            <?php elseif ($oauth2_available): ?>
                <p class="ak-page-copy" style="margin-left:auto;margin-right:auto;">使用 OAuth2 登录后，管理员审核通过即可生成短链。</p>
                <a class="ak-button ak-button-primary" href="account.php?op=oauth2"><span class="ak-material" aria-hidden="true">account_circle</span>使用 OAuth2 登录</a>
            <?php else: ?>
                <div class="ak-alert" role="alert">OAuth2 尚未配置，暂时无法注册或登录。</div>
            <?php endif; ?>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
