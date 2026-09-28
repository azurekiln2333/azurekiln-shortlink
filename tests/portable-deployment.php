<?php

// Run with php -n tests/portable-deployment.php; no database or provider needed.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$project_root = dirname(__DIR__);
$fixture_root = $project_root . '/.tmp/portable-check-' . bin2hex(random_bytes(8));
$fixture_files = array('config.php', 'database.php', 'url.php', 'oauth2.php', 'platform.php');
mkdir($fixture_root . '/functions', 0700, true);
register_shutdown_function(function () use ($fixture_root, $fixture_files) {
    foreach ($fixture_files as $file) {
        if (is_file($fixture_root . '/functions/' . $file)) {
            unlink($fixture_root . '/functions/' . $file);
        }
    }
    if (is_file($fixture_root . '/config.php')) {
        unlink($fixture_root . '/config.php');
    }
    rmdir($fixture_root . '/functions');
    rmdir($fixture_root);
});

$settings = require $project_root . '/config.php';
foreach (array_keys($settings) as $name) {
    putenv($name);
}
$settings['APP_DEBUG'] = '0';
$settings['ALLOW_LEGACY_ADMIN_LOGIN'] = '0';
$settings['SHORTNY_DB_HOST'] = '127.0.0.1';
$settings['SHORTNY_ANALYTICS_SALT'] = '';
$settings['SHORTNY_ANALYTICS_RETENTION_DAYS'] = '180';
$settings['SHORTNY_DB_USER'] = 'fixture-user';
$settings['SHORTNY_DB_PASS'] = "  password'\\with spaces  ";
$settings['SHORTNY_DB_NAME'] = 'fixture-database';
$settings['OAUTH2_ISSUER'] = '';
$settings['OAUTH2_AUTHORIZATION_ENDPOINT'] = 'https://sso.example.test/authorize';
$settings['OAUTH2_TOKEN_ENDPOINT'] = 'https://sso.example.test/token';
$settings['OAUTH2_USERINFO_ENDPOINT'] = 'https://sso.example.test/userinfo';
$settings['OAUTH2_CLIENT_ID'] = ' fixture-client ';
$settings['OAUTH2_CLIENT_SECRET'] = 'fixture-secret';
$settings['OAUTH2_REDIRECT_URI'] = 'https://login.example.test/app/admin/login.php?op=oauth2_callback';
$settings['OAUTH2_USER_REDIRECT_URI'] = 'https://login.example.test/app/account.php?op=oauth2_callback';
$settings['OAUTH2_ADMIN_EMAILS'] = ' Admin@example.test ';
$settings['OAUTH2_ADMIN_SUBJECTS'] = ' fixture-admin ';
file_put_contents($fixture_root . '/config.php', "<?php\nreturn " . var_export($settings, true) . ";\n");
foreach ($fixture_files as $file) {
    copy($project_root . '/functions/' . $file, $fixture_root . '/functions/' . $file);
}

require $fixture_root . '/functions/config.php';
require $fixture_root . '/functions/url.php';
require $fixture_root . '/functions/oauth2.php';
require $fixture_root . '/functions/platform.php';

$checks = 0;
function expect_same($expected, $actual, $message)
{
    global $checks;
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
    $checks++;
}

expect_same('fixture-user', app_config('SHORTNY_DB_USER'), 'Database user must load from file.');
expect_same("  password'\\with spaces  ", app_config('SHORTNY_DB_PASS'), 'Database passwords must retain whitespace.');
expect_same('fallback', app_config('UNCONFIGURED_TEST_OPTION', 'fallback'), 'Unknown options must use the caller fallback.');
expect_same('0', app_config('APP_DEBUG'), 'Debugging must default to disabled.');
expect_same('0', app_config('ALLOW_LEGACY_ADMIN_LOGIN'), 'Legacy login must default to disabled.');
expect_same('', privacy_hash('127.0.0.1'), 'Blank analytics salt must disable IP hashes.');
$_SESSION = array();
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
expect_same(hash('sha256', 'shortny-rate-limit|ip:127.0.0.1'), rate_limit_subject(), 'Blank analytics salt must preserve the rate-limit default.');
expect_same('180', platform_env('SHORTNY_ANALYTICS_RETENTION_DAYS'), 'Retention must load from file.');
putenv('SHORTNY_DB_USER=environment-user');
expect_same('environment-user', app_config('SHORTNY_DB_USER'), 'Environment must override file configuration.');
putenv('SHORTNY_DB_USER');
expect_same('fixture-user', app_config('SHORTNY_DB_USER'), 'Removing an override must restore file configuration.');
putenv('SHORTNY_DB_PASS=');
expect_same('', app_config('SHORTNY_DB_PASS'), 'An explicitly empty override must remain empty.');
putenv('SHORTNY_DB_PASS');
putenv('SHORTNY_ANALYTICS_ENABLED=0');
expect_same('0', platform_env('SHORTNY_ANALYTICS_ENABLED', '1'), 'Zero must not be replaced by a fallback.');
putenv('SHORTNY_ANALYTICS_ENABLED');

// Exercise the actual database reader without opening a network connection.
if (extension_loaded('mysqli')) {
    throw new RuntimeException('Run this test with php -n to disable loaded MySQL extensions.');
}
if (!function_exists('mysqli_connect')) {
    function mysqli_connect($host, $user, $password, $database)
    {
        $GLOBALS['fixture_connection'] = array($host, $user, $password, $database);
        return new stdClass();
    }
    function mysqli_connect_errno() { return 0; }
    function mysqli_set_charset($connection, $charset) { return true; }
    function mysqli_close($connection) { return true; }
}
require $fixture_root . '/functions/database.php';
expect_same(array('127.0.0.1', 'fixture-user', "  password'\\with spaces  ", 'fixture-database'), $GLOBALS['fixture_connection'], 'Database reader must receive the file credentials unchanged.');

$oauth = oauth2_config();
expect_same('fixture-client', $oauth['client_id'], 'OAuth2 values must retain their existing normalization.');
expect_same(true, oauth2_enabled(false), 'File configuration must enable user login.');
expect_same(true, oauth2_enabled(), 'File configuration must enable admin login with an allowlist.');
expect_same(true, oauth2_is_admin(array('email' => 'ADMIN@example.test')), 'Email allowlist must load from file.');
expect_same(true, oauth2_is_admin(array('sub' => 'fixture-admin')), 'Subject allowlist must load from file.');
expect_same(false, oauth2_is_admin(array('sub' => 'other-user')), 'An unlisted user must remain unprivileged.');
$_SESSION = array();
foreach (array('admin' => $settings['OAUTH2_REDIRECT_URI'], 'user' => $settings['OAUTH2_USER_REDIRECT_URI']) as $purpose => $callback) {
    parse_str(parse_url(oauth2_begin($purpose), PHP_URL_QUERY), $query);
    expect_same($callback, $query['redirect_uri'], 'Each login purpose must use its explicit configured callback.');
    expect_same('fixture-client', $query['client_id'], 'Authorization URL must use the configured client.');
    expect_same('S256', $query['code_challenge_method'], 'PKCE must stay enabled.');
}
putenv('OAUTH2_ADMIN_EMAILS=');
putenv('OAUTH2_ADMIN_SUBJECTS=');
expect_same(false, oauth2_enabled(), 'Admin login must require an allowlist.');
expect_same(true, oauth2_enabled(false), 'User login must remain available without an admin allowlist.');
putenv('OAUTH2_ADMIN_EMAILS');
putenv('OAUTH2_ADMIN_SUBJECTS');
putenv('OAUTH2_USER_REDIRECT_URI=');
expect_same(false, oauth2_enabled(false), 'Missing user callback must keep login disabled.');
putenv('OAUTH2_USER_REDIRECT_URI');

$_SERVER['HTTP_HOST'] = 'alias.example.test:8443';
$_SERVER['HTTPS'] = 'on';
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
foreach (array('/about.php' => '', '/admin/links.php' => '', '/nested/app/index.php' => '/nested/app', '/nested/app/admin/links.php' => '/nested/app') as $script => $base_path) {
    $_SERVER['SCRIPT_NAME'] = $script;
    expect_same('https://alias.example.test:8443' . $base_path . '/link.php?id=old_Ab-1', short_link_url('old_Ab-1'), 'Alias URL must use a direct PHP endpoint under the current host and base path.');
    expect_same('https://alias.example.test:8443' . $base_path . '/stats.php?id=old_Ab-1', public_url('stats.php?id=old_Ab-1'), 'Statistics URL must keep its PHP endpoint.');
}
$_SERVER['SCRIPT_NAME'] = '/nested/app/link.php';
expect_same('https://alias.example.test:8443/nested/app/link.php?id=a%26b', short_link_url('a&b'), 'Alias values must be URL encoded.');

$info = array('name' => 'Test site');
$alias = 'old_Ab-1';
ob_start();
include $project_root . '/functions/password.php';
$password_html = ob_get_clean();
expect_same(true, strpos($password_html, 'action="https://alias.example.test:8443/nested/app/link.php?id=old_Ab-1"') !== false, 'Password form must post to the direct short-link URL.');
foreach (array('about.php', 'api-about.php', 'tos.php') as $page) {
    expect_same(true, strpos($password_html, 'href="' . $page . '"') !== false, 'Shared navigation must use actual PHP filenames.');
}

echo 'PASS: ' . $checks . " configuration, OAuth2 and direct URL checks.\n";
