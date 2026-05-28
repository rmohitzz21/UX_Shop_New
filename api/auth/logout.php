<?php
require_once __DIR__ . '/../_bootstrap.php';

apiRequirePost();

$input = apiInput();
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) && !empty($input['csrf_token'])) {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) $input['csrf_token'];
}
validateCsrf();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}
session_destroy();

sendResponse('success', 'Signed out successfully.');
