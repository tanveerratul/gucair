<?php
/**
 * Session and login helpers.
 *
 * Include this file on any page that needs to know whether a user is logged in.
 */

// Start the session only once, before any HTML is sent to the browser.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!function_exists('is_logged_in')) {
    /**
     * A user is considered logged in when the session has a user id.
     */
    function is_logged_in(): bool
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_local_path')) {
    /**
     * Check that a redirect stays inside this application.
     *
     * Accepts a relative page name or an absolute local path. External URLs,
     * protocol-relative URLs, and paths containing '..' are rejected.
     */
    function is_local_path($path): bool
    {
        if (!is_string($path) || trim($path) === '') {
            return false;
        }

        $parts = parse_url(trim($path));
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return false;
        }

        $pathOnly = $parts['path'] ?? '';
        if ($pathOnly === '' || strpos($pathOnly, '..') !== false || strpos($pathOnly, '\\') !== false) {
            return false;
        }

        // An absolute path starts with '/'; a relative one is just a page name.
        return $pathOnly[0] === '/' || preg_match('/^[A-Za-z0-9_.-]+\.php$/', $pathOnly) === 1;
    }
}

if (!function_exists('require_login')) {
    /**
     * Protect a private page.
     *
     * Call this before printing any HTML on dashboard.php,
     * assessment.php, or any other private page.
     */
    function require_login(): void
    {
        if (is_logged_in()) {
            return;
        }

        // Remember a local page so login.php can send the user back after login.
        $requested = $_SERVER['REQUEST_URI'] ?? '';
        if (is_local_path($requested)) {
            $_SESSION['redirect_after_login'] = $requested;
        }

        // Relative so the app works at /gucair/ and at the domain root on Render.
        header('Location: login.php');
        exit;
    }
}

if (!function_exists('current_user_name')) {
    /**
     * Safe display name for the navbar. Never store passwords in the session.
     */
    function current_user_name(): string
    {
        return isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Create (or reuse) a CSRF token for forms that change data.
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Check a submitted CSRF token in PHP. JavaScript alone is not enough.
     */
    function verify_csrf_token($token): bool
    {
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('logout_form')) {
    /**
     * Print a small POST form so logout is protected by a CSRF token.
     *
     * The button class is escaped even though this app controls the value.
     */
    function logout_form(string $buttonClass = 'btn btn-secondary'): string
    {
        if (!is_logged_in()) {
            return '';
        }

        $buttonClass = trim($buttonClass) !== '' ? trim($buttonClass) : 'btn btn-secondary';

        return '<form class="logout-form" method="post" action="logout.php">'
            . '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">'
            . '<button class="' . htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8')
            . '" type="submit">Log out</button>'
            . '</form>';
    }
}
