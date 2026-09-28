<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
include_once __DIR__ . '/functions/user_auth.php';
include "functions/database.php";

$data = $db->query("SELECT * FROM settings");
$info = $db->fetch_array($data);
$contact_error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["op"]) && $_GET["op"] === "contact") {
    $name = trim(isset($_POST["name"]) ? (string) $_POST["name"] : '');
    $email = trim(isset($_POST["email"]) ? (string) $_POST["email"] : '');
    $comment = trim(isset($_POST["message"]) ? (string) $_POST["message"] : '');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

    if (!csrf_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')
        || $name === '' || strlen($name) > 100
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
        || $comment === '' || strlen($comment) > 5000) {
        $contact_error = '提交无效或已过期，请检查内容后重试。';
    } else {
        $sub = "New message";

        $to = $info['email'];
        $subject = $sub;
        $message = ' Message:' . $comment . ' Email:' . $email . ' Name:' . $name . ' IP:' . $ip;
        $headers = 'From: ' . $info['email'] . '' . "\r\n" .
                'Reply-To: ' . $info['email'] . '' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
        include "functions/thankyou.php";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <base href="<?php echo htmlspecialchars(public_base_url(), ENT_QUOTES, 'UTF-8'); ?>/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>联系我们 - <?php echo htmlspecialchars($info['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="css/azurekiln.css" rel="stylesheet">
    <style><?php echo $info['cstm-style']; ?></style>
</head>
<body class="ak-page">
<?php include "functions/menu.php"; ?>
<main class="ak-main">
    <div class="ak-content">
        <p class="ak-eyebrow">Contact AzureKiln Link</p>
        <h1 class="ak-page-heading">联系我们</h1>
        <p class="ak-page-copy">如果你发现了问题或需要联系管理员，可以通过下面的表单发送消息。</p>
        <section class="ak-card ak-card-body">
            <?php if ($contact_error !== ''): ?><div class="ak-alert" role="alert"><?php echo htmlspecialchars($contact_error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form class="ak-form-stack" action="contact.php?op=contact" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ak-form-grid">
                    <div class="ak-field">
                        <label class="ak-label" for="name">姓名</label>
                        <input class="ak-input" id="name" name="name" type="text" placeholder="请输入你的名字" required>
                    </div>
                    <div class="ak-field">
                        <label class="ak-label" for="email">你的邮件</label>
                        <input class="ak-input" id="email" name="email" type="email" placeholder="请输入你的邮件" required>
                    </div>
                </div>
                <div class="ak-field">
                    <label class="ak-label" for="message">你的信息</label>
                    <textarea class="ak-input" id="message" name="message" placeholder="请在此处输入您的消息" rows="7" required></textarea>
                </div>
                <div class="ak-form-actions">
                    <button type="submit" class="ak-button ak-button-primary">
                        <span class="ak-material" aria-hidden="true">send</span>提交消息
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>
<script src="js/azurekiln-theme.js"></script>
</body>
</html>
<?php $db->close_connection(); ?>
