<?php
/**
 * Login page.
 *
 * Checks the submitted password with password_verify() and creates a session.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/config/database.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'This form expired. Please reload the page and try again.';
    } else {
        [$values, $errors] = validate_login($_POST);
        $email = $values['email'];

        if (empty($errors)) {
            try {
                $universities = get_universities_collection();

                // Only load fields that login actually needs.
                $user = $universities->findOne(
                    ['email' => $values['email']],
                    ['projection' => [
                        'name'           => 1,
                        'email'          => 1,
                        'contact_person' => 1,
                        'password_hash'  => 1,
                    ]]
                );

                $hash = ($user && isset($user['password_hash'])) ? (string) $user['password_hash'] : '';

                // The same message is used for unknown email and wrong password.
                if (!$user || $hash === '' || !password_verify($values['password'], $hash)) {
                    $errors['general'] = 'The email or password is incorrect.';
                } else {
                    // Prevent session fixation after a successful login.
                    session_regenerate_id(true);

                    // Store only safe session data. Never store the password.
                    $_SESSION['user_id']       = (string) $user['_id'];
                    $_SESSION['name']          = (string) $user['name'];
                    $_SESSION['email']         = (string) $user['email'];
                    $_SESSION['contact_person'] = (string) ($user['contact_person'] ?? '');

                    // Send the user back to a local page they requested earlier.
                    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                    unset($_SESSION['redirect_after_login']);

                    if (!is_local_path($redirect)) {
                        $redirect = 'index.php';
                    }

                    $_SESSION['flash_success'] = 'You are now logged in.';
                    header('Location: ' . $redirect);
                    exit;
                }
            } catch (Throwable $error) {
                error_log('GUCAIR login failed: ' . $error->getMessage());
                $errors['general'] = 'We could not check your login right now. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Log in';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (is_logged_in()): ?>
    <div class="alert alert-success" role="status">
        You are already logged in as <?= e(current_user_name()) ?>.
        <a href="dashboard.php">Go to Dashboard</a>
        <?= logout_form('btn btn-secondary btn-small') ?>
    </div>
<?php endif; ?>

<form class="form-card" method="post" action="login.php" data-form="login" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <h1>Log in</h1>
    <p class="lead">Use the university email you registered with.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <div class="form-group">
        <label for="email">University email</label>
        <input class="form-control" id="email" name="email" type="email"
               autocomplete="email" maxlength="254"
               value="<?= e($email) ?>"
               aria-describedby="email-error"<?= invalid_attr($errors, 'email') ?>>
        <span class="field-error" id="email-error" aria-live="polite"><?= e(field_error($errors, 'email')) ?></span>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password"
               autocomplete="current-password"
               aria-describedby="password-error"<?= invalid_attr($errors, 'password') ?>>
        <span class="field-error" id="password-error" aria-live="polite"><?= e(field_error($errors, 'password')) ?></span>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Log in</button>
        <a href="register.php">Need an account? Register</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
