<?php
/**
 * api/auth/csrf.php
 *
 * Returns the current session's CSRF token as JSON.
 * Used as a fallback by the frontend when the meta tag is unavailable.
 *
 * GET — no auth required (token is per-session, not per-user)
 */
header('Content-Type: application/json');
require_once '../../includes/config.php';

// config.php already starts the session and generates $_SESSION['csrf_token']
echo json_encode([
    'status' => 'success',
    'token'  => $_SESSION['csrf_token'],
]);
