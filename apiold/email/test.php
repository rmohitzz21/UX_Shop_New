<?php
require_once '../../includes/config.php';
require_once '../../core/Mailer.php';

$isAdmin = !empty($_SESSION['admin_id']);
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($isPost) {
    if (!$isAdmin) {
        sendResponse('error', 'Unauthorized: Admin access required', null, 401);
    }

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        sendResponse('error', 'Invalid CSRF token', null, 403);
    }

    $adminRecipient = trim((string) ($_POST['admin_email'] ?? (getenv('ADMIN_EMAIL') ?: '')));
    $userRecipient = trim((string) ($_POST['user_email'] ?? ''));

    if ($adminRecipient === '') {
        $adminRecipient = trim((string) (getenv('SMTP_FROM') ?: 'support@uxpacific.com'));
    }
    if ($userRecipient === '') {
        $userRecipient = trim((string) (getenv('SMTP_FROM') ?: 'support@uxpacific.com'));
    }

    if (!filter_var($adminRecipient, FILTER_VALIDATE_EMAIL) || !filter_var($userRecipient, FILTER_VALIDATE_EMAIL)) {
        sendResponse('error', 'Please provide valid admin and user test emails.', null, 400);
    }

    $mailer = new Mailer();
    $results = [
        'admin' => ['recipient' => $adminRecipient, 'sent' => false, 'error' => null],
        'user' => ['recipient' => $userRecipient, 'sent' => false, 'error' => null],
    ];

    $when = date('Y-m-d H:i:s');
    $htmlBase = '<h2>UX Shop SMTP Test</h2><p>Time: ' . htmlspecialchars($when, ENT_QUOTES, 'UTF-8') . '</p>';

    try {
        $results['admin']['sent'] = $mailer->send(
            $adminRecipient,
            'SMTP Test - Admin Recipient',
            $htmlBase . '<p>This is an admin recipient test from <code>api/email/test.php</code>.</p>'
        );
    } catch (Exception $e) {
        $results['admin']['error'] = $e->getMessage();
        error_log('Email test admin send failed: ' . $e->getMessage());
    }

    try {
        $results['user']['sent'] = $mailer->send(
            $userRecipient,
            'SMTP Test - User Recipient',
            $htmlBase . '<p>This is a user recipient test from <code>api/email/test.php</code>.</p>'
        );
    } catch (Exception $e) {
        $results['user']['error'] = $e->getMessage();
        error_log('Email test user send failed: ' . $e->getMessage());
    }

    $overall = ($results['admin']['sent'] || $results['user']['sent']);
    sendResponse($overall ? 'success' : 'error', $overall ? 'At least one test email sent.' : 'Both test emails failed.', $results, $overall ? 200 : 500);
}

$csrfToken = $_SESSION['csrf_token'] ?? '';
$defaultAdmin = getenv('ADMIN_EMAIL') ?: '';
$defaultUser = getenv('SMTP_FROM') ?: '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>Email SMTP Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; max-width: 760px; color: #1f2937; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; background: #fff; }
        h1 { margin-top: 0; }
        label { display: block; margin: 12px 0 6px; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 1px solid #9ca3af; border-radius: 6px; }
        button { margin-top: 16px; padding: 10px 16px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .muted { color: #6b7280; font-size: 14px; }
        pre { margin-top: 16px; background: #111827; color: #f9fafb; padding: 12px; border-radius: 6px; overflow-x: auto; }
        .warn { color: #b91c1c; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Email SMTP Test</h1>
        <p class="muted">Run quick admin + user recipient checks in one click.</p>

        <?php if (!$isAdmin): ?>
            <p class="warn">Admin login required. Please login to admin panel first, then reopen this page.</p>
        <?php endif; ?>

        <form id="smtp-test-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="admin_email">Admin recipient</label>
            <input id="admin_email" name="admin_email" type="email" value="<?php echo htmlspecialchars($defaultAdmin, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="user_email">User recipient</label>
            <input id="user_email" name="user_email" type="email" value="<?php echo htmlspecialchars($defaultUser, ENT_QUOTES, 'UTF-8'); ?>" required>

            <button type="submit" <?php echo $isAdmin ? '' : 'disabled'; ?>>Send Test Emails</button>
        </form>

        <pre id="result">Waiting for test...</pre>
    </div>

    <script>
    (function () {
        const form = document.getElementById('smtp-test-form');
        const result = document.getElementById('result');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            result.textContent = 'Sending...';

            const formData = new FormData(form);
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrf
                    },
                    body: formData
                });

                const json = await response.json();
                result.textContent = JSON.stringify(json, null, 2);
            } catch (err) {
                result.textContent = JSON.stringify({
                    status: 'error',
                    message: err.message || 'Request failed'
                }, null, 2);
            }
        });
    })();
    </script>
</body>
</html>
