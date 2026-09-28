<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(405);
    exit;
}

$raw_body = file_get_contents('php://input');
$json_body = json_decode($raw_body, true);
$request = is_array($json_body) ? $json_body : $_POST;

if (!isset($request['req'], $request['dbhost'], $request['dbuser'], $request['dbname'])
    || $request['req'] !== 'poop'
    || trim((string) $request['dbhost']) === ''
    || trim((string) $request['dbuser']) === ''
    || trim((string) $request['dbname']) === '') {
    echo json_encode(400);
    exit;
}

$dbhost = trim((string) $request['dbhost']);
$dbuser = (string) $request['dbuser'];
$dbpass = isset($request['dbpass']) ? (string) $request['dbpass'] : '';
$dbname = trim((string) $request['dbname']);
$upgrade = !empty($request['option']);

$connection = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if (!$connection) {
    echo json_encode((int) mysqli_connect_errno());
    exit;
}

mysqli_set_charset($connection, 'utf8mb4');
$filename = $upgrade ? 'shortny-upgrade.sql' : 'shortny.sql';
$lines = @file(__DIR__ . '/' . $filename);
if ($lines === false) {
    error_log('Installer SQL file is missing: ' . $filename);
    mysqli_close($connection);
    echo json_encode(5000);
    exit;
}

$statement = '';
foreach ($lines as $line) {
    if (substr(ltrim($line), 0, 2) === '--' || trim($line) === '') {
        continue;
    }
    $statement .= $line;
    if (substr(trim($line), -1, 1) === ';') {
        if (!mysqli_query($connection, $statement)) {
            error_log('Installer SQL statement failed: ' . mysqli_error($connection));
            mysqli_close($connection);
            echo json_encode(5000);
            exit;
        }
        $statement = '';
    }
}

if (trim($statement) !== '' && !mysqli_query($connection, $statement)) {
    error_log('Installer SQL statement failed: ' . mysqli_error($connection));
    mysqli_close($connection);
    echo json_encode(5000);
    exit;
}

mysqli_close($connection);
// Credentials are intentionally not written into the application source.
echo json_encode(0);
