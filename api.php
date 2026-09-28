<?php
include_once __DIR__ . '/functions/session.php';
start_app_session();
include_once __DIR__ . '/functions/database.php';
include_once __DIR__ . '/functions/shortlink.php';
include_once __DIR__ . '/functions/user_auth.php';
ensure_platform_schema($db);
ensure_user_schema($db);

header('Content-Type: application/json; charset=utf-8');

$origin = isset($_SERVER['HTTP_ORIGIN']) ? trim($_SERVER['HTTP_ORIGIN']) : '';
$allowed_origins = oauth2_list(oauth2_env('SHORTNY_API_ORIGINS', ''));
$same_origin = request_scheme() . '://' . request_host();
if ($origin !== '' && ($origin === $same_origin || in_array($origin, $allowed_origins, true))) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} elseif ($origin !== '') {
    http_response_code(403);
    echo json_encode(array('success' => false, 'error' => 'This API origin is not allowed.'), JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST, OPTIONS');
    echo json_encode(array('success' => false, 'error' => 'POST is required.'), JSON_UNESCAPED_SLASHES);
    $db->close_connection();
    exit;
}

$request = $_POST;
$content_type = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($content_type, 'application/json') !== false) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $request = $decoded;
    }
}

$api_key_context = null;
$bearer_token = api_bearer_token();
if ($bearer_token !== '') {
    $api_key_context = authenticate_api_key($db, $bearer_token);
    if (!$api_key_context) {
        http_response_code(401);
        echo json_encode(array('success' => false, 'error' => 'Invalid or revoked API key.'), JSON_UNESCAPED_SLASHES);
        $db->close_connection();
        exit;
    }
    $creator = $api_key_context['user'];
} else {
    $creator = approved_user_or_admin($db);
}
if (!$creator) {
    http_response_code(empty($_SESSION['user_id']) ? 401 : 403);
    echo json_encode(array('success' => false, 'error' => 'An approved OAuth2 account is required.'), JSON_UNESCAPED_SLASHES);
    $db->close_connection();
    exit;
}

if ($api_key_context === null) {
    $csrf_header = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    $csrf_value = $csrf_header !== '' ? $csrf_header : (isset($request['csrf_token']) ? $request['csrf_token'] : '');
    if (!csrf_valid($csrf_value)) {
        http_response_code(419);
        echo json_encode(array('success' => false, 'error' => 'A valid CSRF token is required.'), JSON_UNESCAPED_SLASHES);
        $db->close_connection();
        exit;
    }
}

$rate_suffix = $api_key_context !== null ? (string) $api_key_context['key']['id'] : '';
if (!rate_limit_allow($db, 'api-create', 60, 3600, $rate_suffix)) {
    http_response_code(429);
    echo json_encode(array('success' => false, 'error' => 'Too many API requests. Try again later.'), JSON_UNESCAPED_SLASHES);
    $db->close_connection();
    exit;
}

$batch_items = isset($request['urls']) && is_array($request['urls']) ? $request['urls'] : array();
if (!empty($batch_items)) {
    if (count($batch_items) > 50) {
        http_response_code(413);
        echo json_encode(array('success' => false, 'error' => 'A batch may contain at most 50 URLs.'), JSON_UNESCAPED_SLASHES);
        $db->close_connection();
        exit;
    }
    $response = array('success' => true, 'results' => array());
    foreach ($batch_items as $item) {
        if (is_string($item)) {
            $item = array('url' => $item);
        }
        if (!is_array($item)) {
            $item = array();
        }

        $result = create_short_link_record(
            $db,
            isset($item['url']) ? $item['url'] : '',
            isset($item['cust']) ? $item['cust'] : '',
            isset($item['pass']) ? $item['pass'] : '',
            isset($creator['id']) ? (int) $creator['id'] : 0,
            array(
                'expires_at' => isset($item['expires_at']) ? $item['expires_at'] : '',
                'max_hits' => isset($item['max_hits']) ? $item['max_hits'] : null,
            )
        );
        $response['results'][] = $result;
        if (!$result['success']) {
            $response['success'] = false;
        } else {
            audit_event($db, $api_key_context !== null ? 'api_key' : 'user', isset($creator['id']) ? (int) $creator['id'] : null, 'link.created', 'link', (int) $result['id'], array('alias' => $result['alias']));
        }
    }

    if (!$response['success']) {
        http_response_code(422);
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $db->close_connection();
    exit;
}

$result = create_short_link_record(
    $db,
    isset($request['url']) ? $request['url'] : '',
    isset($request['cust']) ? $request['cust'] : '',
    isset($request['pass']) ? $request['pass'] : '',
    isset($creator['id']) ? (int) $creator['id'] : 0,
    array(
        'expires_at' => isset($request['expires_at']) ? $request['expires_at'] : '',
        'max_hits' => isset($request['max_hits']) ? $request['max_hits'] : null,
    )
);

if ($result['success']) {
    audit_event($db, $api_key_context !== null ? 'api_key' : 'user', isset($creator['id']) ? (int) $creator['id'] : null, 'link.created', 'link', (int) $result['id'], array('alias' => $result['alias']));
    unset($result['success'], $result['alias']);
} else {
    http_response_code(422);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$db->close_connection();
