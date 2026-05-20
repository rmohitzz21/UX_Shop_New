<?php
require_once __DIR__ . '/../_bootstrap.php';

sendResponse('success', 'CSRF token ready.', ['token' => $_SESSION['csrf_token'] ?? '']);
