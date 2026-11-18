<?php
/**
 * Logout action.
 *
 * Only a POST request with a valid CSRF token can clear the login session.
 */

require_once __DIR__ . '/includes/auth.php';

// This is a private action, so guests never reach the logout code.
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = 'Use the Log out button to end your session.';
    header('Location: index.php');
    exit;
}

// Remove all session data.
$_SESSION = [];

// Also remove the session cookie from the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}

session_destroy();

// Start a fresh session only to carry the success message.
session_start();
session_regenerate_id(true);
$_SESSION['flash_success'] = 'You have been logged out.';

header('Location: index.php');
exit;
