<?php

// Edit deployment values here. Existing environment variables take precedence.
// Keep real database passwords and OAuth2 secrets out of shared source copies.
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(403);
    exit;
}

return array(
    // Application: use '1' to enable debugging during development.
    'APP_DEBUG' => '0',

    // Database: use the same credentials when installing or upgrading.
    'SHORTNY_DB_HOST' => '127.0.0.1',
    'SHORTNY_DB_USER' => '',
    'SHORTNY_DB_PASS' => '',
    'SHORTNY_DB_NAME' => '',

    // OAuth2: discovery issuer, or all three explicit provider endpoints.
    'OAUTH2_ISSUER' => '',
    'OAUTH2_AUTHORIZATION_ENDPOINT' => '',
    'OAUTH2_TOKEN_ENDPOINT' => '',
    'OAUTH2_USERINFO_ENDPOINT' => '',
    'OAUTH2_CLIENT_ID' => '',
    'OAUTH2_CLIENT_SECRET' => '',
    // Register both complete URLs with the provider, including query strings.
    // https://your-domain/admin/login.php?op=oauth2_callback
    'OAUTH2_REDIRECT_URI' => '',
    // https://your-domain/account.php?op=oauth2_callback
    'OAUTH2_USER_REDIRECT_URI' => '',
    'OAUTH2_SCOPES' => 'openid profile email',
    // Comma-separated administrator emails or stable OAuth2 subjects.
    'OAUTH2_ADMIN_EMAILS' => '',
    'OAUTH2_ADMIN_SUBJECTS' => '',
    'ALLOW_LEGACY_ADMIN_LOGIN' => '0',

    // API: comma-separated allowed browser origins; empty means same-origin only.
    'SHORTNY_API_ORIGINS' => '',

    // Analytics: enable with '1'; a blank salt disables visitor IP hashes.
    'SHORTNY_ANALYTICS_ENABLED' => '1',
    'SHORTNY_ANALYTICS_SALT' => '',
    'SHORTNY_ANALYTICS_RETENTION_DAYS' => '180',
);
