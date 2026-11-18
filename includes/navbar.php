<?php
/**
 * Shared responsive navigation bar.
 * The session must already be started by header.php or auth.php.
 */

require_once __DIR__ . '/auth.php';

if (!function_exists('gucair_is_active')) {
    /**
     * Return an active-state attribute for the current page.
     *
     * Only the file name is compared, so this works at /gucair/ in XAMPP
     * and at the domain root on Render.
     */
    function gucair_is_active(string $page): string
    {
        $currentPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $currentFile = basename($currentPath);

        // Directory URLs such as /gucair/ and / use index.php.
        if ($currentFile === '' || strpos($currentFile, '.') === false) {
            $currentFile = 'index.php';
        }

        return $currentFile === $page
            ? ' class="active" aria-current="page"'
            : '';
    }
}
?>
<header class="site-header">
    <nav class="navbar container" aria-label="Main navigation">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true">G</span>
            <span>GUCAIR</span>
        </a>

        <!-- CSS-only menu toggle: no framework and no inline JavaScript. -->
        <input class="nav-toggle-input" type="checkbox" id="nav-toggle">
        <label class="nav-toggle-button" for="nav-toggle">
            <span class="visually-hidden">Toggle navigation</span>
            <span></span>
            <span></span>
            <span></span>
        </label>

        <ul class="nav-list">
            <li><a<?= gucair_is_active('index.php') ?> href="index.php">Home</a></li>
            <li><a<?= gucair_is_active('directory.php') ?> href="directory.php">Universities</a></li>
            <li><a<?= gucair_is_active('assessments.php') ?> href="assessments.php">Assessments</a></li>

            <?php if (is_logged_in()): ?>
                <li><a<?= gucair_is_active('dashboard.php') ?> href="dashboard.php">Dashboard</a></li>
                <li><a<?= gucair_is_active('profile.php') ?> href="profile.php">Profile</a></li>
                <li><a<?= gucair_is_active('history.php') ?> href="history.php">History</a></li>
                <li>
                    <a<?= gucair_is_active('assessment.php') ?> class="btn btn-small"
                       href="assessment.php">Add Assessment</a>
                </li>
                <li><span class="nav-user"><?= htmlspecialchars(current_user_name(), ENT_QUOTES, 'UTF-8') ?></span></li>
                <li><?= logout_form('btn btn-secondary btn-small') ?></li>
            <?php else: ?>
                <li><a<?= gucair_is_active('login.php') ?> href="login.php">Log in</a></li>
                <li>
                    <a<?= gucair_is_active('register.php') ?> class="btn btn-small"
                       href="register.php">Register</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
