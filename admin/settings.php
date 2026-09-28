<?php
include_once __DIR__ . '/../functions/session.php';
start_app_session();
include_once __DIR__ . '/../functions/user_auth.php';
if (!admin_session_active()) {
    header("Location: login.php");
    exit;
}

include "../functions/database.php";
include_once __DIR__ . '/../functions/platform.php';
ensure_platform_schema($db);
$settings_updated = 0;
$error_updating = 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $settings_operation = isset($_GET['op']) ? $_GET['op'] : '';
    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error_updating = 1;
    } elseif ($settings_operation == "general") {
        $webname = $db->escape_value($_POST["web-name"]);
        $url = $db->escape_value($_POST["url"]);
        $logo = $db->escape_value($_POST["logo"] . ";" . $_POST["logo-dark"]);
        $webmail = $db->escape_value($_POST["webmail"]);
        $logo_type = isset($_POST['logo_type']);
        $redirect = isset($_POST['redirect']);
        $wait = (int) $_POST['wait'];
        $type = $logo_type ? 1 : 0;
        $splash = $redirect ? 1 : 0;
        if ($_SESSION["valid_user"]) {
            $action = "UPDATE settings SET name='$webname', url='$url', logo_url='$logo', email='$webmail', redirect = '$splash', logo_type = '$type', wait = '$wait'";
            $db->query($action);
            audit_event($db, 'admin', null, 'settings.updated', 'settings', null, array('section' => 'general'));
            $settings_updated = 1;
        } else {
            $error_updating = 1;
        }
    } elseif ($settings_operation == "ads") {
        $ad1 = $db->escape_value($_POST["ad1"]);
        $ad2 = $db->escape_value($_POST["ad2"]);
        $ad3 = $db->escape_value($_POST["ad3"]);
        $ad4 = $db->escape_value($_POST["ad4"]);
        if ($_SESSION["valid_user"]) {
            $action = "UPDATE ads SET ad1='$ad1', ad2='$ad2', ad3='$ad3', ad4='$ad4'";
            $db->query($action);
            audit_event($db, 'admin', null, 'settings.updated', 'settings', null, array('section' => 'ads'));
            $settings_updated = 1;
        } else {
            $error_updating = 1;
        }
    } elseif ($settings_operation == "admin") {
        $user = $db->escape_value($_POST["username"]);
        $pass = $_POST["pass"];
        $pass_confirmation = isset($_POST["pass_confirmation"]) ? (string) $_POST["pass_confirmation"] : '';
        $pass_hash = $db->escape_value(password_hash($pass, PASSWORD_BCRYPT, ["cost" => 10]));
        if ($pass === '' || !hash_equals($pass, $pass_confirmation)) {
            $error_updating = 1;
        } elseif ($_SESSION["valid_user"]) {
            $action = "UPDATE settings SET admin_user='$user', admin_pass='$pass_hash'";
            $db->query($action);
            audit_event($db, 'admin', null, 'settings.updated', 'settings', null, array('section' => 'admin'));
            $settings_updated = 1;
        } else {
            $error_updating = 1;
        }
    } elseif ($settings_operation == "css") {
        $css = $db->escape_value($_POST['css']);
        if ($_SESSION["valid_user"]) {
            $action = "UPDATE settings SET `cstm-style`='$css'";
            $db->query($action);
            audit_event($db, 'admin', null, 'settings.updated', 'settings', null, array('section' => 'css'));
            $settings_updated = 1;
        } else {
            $error_updating = 1;
        }
    }
}

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$logo_input = $info['logo_url'];
if (strpos($logo_input, ";") !== false) {
    $logo_input = explode(";", $logo_input);
} else {
    $logo_input = [$logo_input, ""];
}
$ads = $db->query("SELECT * FROM ads");
$ads_info = $db->fetch_array($ads);
$active_tab = isset($_GET['op']) && in_array($_GET['op'], ['general', 'ads', 'css', 'admin'], true) ? $_GET['op'] : 'general';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>设置 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="../css/azurekiln.css" rel="stylesheet">
    <style><?php echo $info['cstm-style']; ?></style>
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-admin-main">
    <div class="ak-shell">
        <div class="ak-admin-toolbar">
            <div>
                <p class="ak-eyebrow">Site configuration</p>
                <h1 class="ak-admin-title">网站设置</h1>
            </div>
            <a class="ak-button ak-button-secondary" href="index.php"><span class="ak-material" aria-hidden="true">arrow_back</span>返回概览</a>
        </div>

        <?php if ($settings_updated): ?><div class="ak-notice ak-notice-success" role="status">设置已更新</div><?php endif; ?>
        <?php if ($error_updating): ?><div class="ak-notice ak-notice-danger" role="alert">抱歉，发生了一个错误。</div><?php endif; ?>

        <nav class="ak-tabs" aria-label="设置分类">
            <a href="#general" data-settings-tab="general" class="<?php echo $active_tab === 'general' ? 'active' : ''; ?>">常规</a>
            <a href="#ads" data-settings-tab="ads" class="<?php echo $active_tab === 'ads' ? 'active' : ''; ?>">广告</a>
            <a href="#custom-css" data-settings-tab="css" class="<?php echo $active_tab === 'css' ? 'active' : ''; ?>">自定义 CSS</a>
            <a href="#admin" data-settings-tab="admin" class="<?php echo $active_tab === 'admin' ? 'active' : ''; ?>">管理员</a>
        </nav>

        <section class="ak-card ak-card-body" data-settings-panel="general" <?php echo $active_tab === 'general' ? '' : 'hidden'; ?>>
            <h2 class="ak-modal-title">常规设置</h2>
            <form class="ak-form-stack" action="?op=general" method="post" style="margin-top:22px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-form-grid">
                    <div class="ak-field"><label class="ak-label" for="web-name">网站名称</label><input class="ak-input" type="text" value="<?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?>" name="web-name" id="web-name" required></div>
                    <div class="ak-field"><label class="ak-label" for="webmail">联系 Email</label><input class="ak-input" type="email" value="<?php echo htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8'); ?>" id="webmail" name="webmail" required></div>
                </div>
                <div class="ak-field"><label class="ak-label" for="url">URL <span class="ak-admin-muted">（历史配置字段，结尾不要带 /）</span></label><input class="ak-input" type="url" value="<?php echo htmlspecialchars($info['URL'], ENT_QUOTES, 'UTF-8'); ?>" name="url" id="url"></div>
                <div class="ak-field"><label class="ak-label" for="logo-url">Logo URL</label><input class="ak-input" type="url" value="<?php echo htmlspecialchars($logo_input[0], ENT_QUOTES, 'UTF-8'); ?>" name="logo" id="logo-url"></div>
                <div class="ak-field"><label class="ak-label" for="logo-dark-url">夜间模式 Logo URL <span class="ak-admin-muted">（相同则留空）</span></label><input class="ak-input" type="url" value="<?php echo htmlspecialchars($logo_input[1], ENT_QUOTES, 'UTF-8'); ?>" name="logo-dark" id="logo-dark-url"></div>
                <div class="ak-form-grid">
                    <label class="ak-checkbox"><input type="checkbox" name="logo_type" value="1" <?php echo $info['logo_type'] == 1 ? 'checked' : ''; ?>>启用图片 Logo</label>
                    <label class="ak-checkbox"><input type="checkbox" name="redirect" value="1" <?php echo $info['redirect'] == 1 ? 'checked' : ''; ?>>启用跳转广告页</label>
                </div>
                <div class="ak-field"><label class="ak-label" for="wait">跳转延时（秒）</label><input class="ak-input" type="number" min="0" value="<?php echo (int) $info['wait']; ?>" id="wait" name="wait"></div>
                <div class="ak-form-actions"><button type="submit" class="ak-button ak-button-primary"><span class="ak-material" aria-hidden="true">save</span>保存设置</button></div>
            </form>
        </section>

        <section class="ak-card ak-card-body" data-settings-panel="ads" <?php echo $active_tab === 'ads' ? '' : 'hidden'; ?>>
            <h2 class="ak-modal-title">广告内容</h2>
            <p class="ak-page-copy" style="margin-top:10px;">可以填写 HTML 片段，分别对应首页、错误页和跳转页。</p>
            <form class="ak-form-stack" action="?op=ads" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-form-grid">
                    <div class="ak-field"><label class="ak-label" for="ad1">Ad 1 <span class="ak-admin-muted">首页</span></label><textarea class="ak-input" id="ad1" name="ad1" rows="7"><?php echo htmlspecialchars($ads_info['ad1'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="ak-field"><label class="ak-label" for="ad2">Ad 2 <span class="ak-admin-muted">错误页面</span></label><textarea class="ak-input" id="ad2" name="ad2" rows="7"><?php echo htmlspecialchars($ads_info['ad2'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="ak-field"><label class="ak-label" for="ad3">Ad 3 <span class="ak-admin-muted">跳转页左侧</span></label><textarea class="ak-input" id="ad3" name="ad3" rows="7"><?php echo htmlspecialchars($ads_info['ad3'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="ak-field"><label class="ak-label" for="ad4">Ad 4 <span class="ak-admin-muted">跳转页右侧</span></label><textarea class="ak-input" id="ad4" name="ad4" rows="7"><?php echo htmlspecialchars($ads_info['ad4'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                </div>
                <div class="ak-form-actions"><button type="submit" class="ak-button ak-button-primary"><span class="ak-material" aria-hidden="true">save</span>保存广告</button></div>
            </form>
        </section>

        <section class="ak-card ak-card-body" data-settings-panel="css" <?php echo $active_tab === 'css' ? '' : 'hidden'; ?>>
            <h2 class="ak-modal-title">自定义 CSS</h2>
            <p class="ak-page-copy" style="margin-top:10px;">保存后会应用到公开页面，请确认样式不会遮挡主要内容。</p>
            <form class="ak-form-stack" action="?op=css" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <textarea class="ak-input ak-code" id="css" name="css" rows="20"><?php echo htmlspecialchars($info['cstm-style'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="ak-form-actions"><button type="submit" class="ak-button ak-button-primary"><span class="ak-material" aria-hidden="true">save</span>保存 CSS</button></div>
            </form>
        </section>

        <section class="ak-card ak-card-body" data-settings-panel="admin" <?php echo $active_tab === 'admin' ? '' : 'hidden'; ?>>
            <h2 class="ak-modal-title">管理员账号</h2>
            <p class="ak-page-copy" style="margin-top:10px;">修改密码后，旧密码将立即失效；OAuth2 登录仍按管理员白名单配置工作。</p>
            <form class="ak-form-stack" action="?op=admin" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-field"><label class="ak-label" for="username">管理员用户名</label><input class="ak-input" type="text" value="<?php echo htmlspecialchars($info['admin_user'], ENT_QUOTES, 'UTF-8'); ?>" name="username" id="username" required></div>
                <div class="ak-form-grid">
                    <div class="ak-field"><label class="ak-label" for="password">管理员密码</label><input class="ak-input" type="password" name="pass_confirmation" id="password" autocomplete="new-password" required></div>
                    <div class="ak-field"><label class="ak-label" for="password-confirmation">重新输入密码</label><input class="ak-input" type="password" name="pass" id="password-confirmation" autocomplete="new-password" required></div>
                </div>
                <div class="ak-form-actions"><button type="submit" class="ak-button ak-button-primary"><span class="ak-material" aria-hidden="true">key</span>更新账号</button></div>
            </form>
        </section>
    </div>
</main>
<script src="../js/azurekiln-theme.js"></script>
<script>
    document.querySelectorAll('[data-settings-tab]').forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            document.querySelectorAll('[data-settings-tab]').forEach(function (item) { item.classList.remove('active'); });
            document.querySelectorAll('[data-settings-panel]').forEach(function (panel) { panel.hidden = panel.dataset.settingsPanel !== tab.dataset.settingsTab; });
            tab.classList.add('active');
            window.history.replaceState(null, '', tab.getAttribute('href'));
        });
    });
</script>
</body>
</html>
<?php $db->close_connection(); ?>
