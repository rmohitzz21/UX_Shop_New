<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = apiUser();
sendResponse('success', $user ? 'Signed in.' : 'Guest session.', ['user' => $user]);
