<?php
/**
 * Shared document header.
 *
 * Pages can set $pageTitle before including this file.
 */

require_once __DIR__ . '/auth.php';

$pageTitle = isset($pageTitle) && trim((string) $pageTitle) !== ''
    ? trim((string) $pageTitle)
    : 'Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="GUCAIR - Global University Consortium of AI Readiness">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | GUCAIR</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <!-- defer keeps scripts non-blocking; they run after HTML is parsed. -->
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/validation.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <?php require_once __DIR__ . '/navbar.php'; ?>

    <main class="container" id="main-content">
        <?php
        // One-time messages after redirects (for example, login or logout success).
        $flashMessages = [
            'success' => 'alert-success',
            'error'   => 'alert-error',
        ];

        foreach ($flashMessages as $type => $cssClass):
            $key = 'flash_' . $type;
            if (!empty($_SESSION[$key])):
                $message = (string) $_SESSION[$key];
                unset($_SESSION[$key]);
        ?>
                <div class="alert <?= $cssClass ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
        <?php
            endif;
        endforeach;
        ?>

