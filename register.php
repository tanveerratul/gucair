<?php
/**
 * Registration page.
 *
 * Creates a university account in MongoDB after JS and PHP validation.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/config/database.php';

$errors = [];
$values = [
    'name'           => '',
    'country'        => '',
    'email'          => '',
    'website'        => '',
    'contact_person' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token protects this form from cross-site submissions.
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'This form expired. Please reload the page and try again.';
    } else {
        [$values, $errors] = validate_registration($_POST);

        if (empty($errors)) {
            try {
                $universities = get_universities_collection();

                // Keep email unique even when two registrations arrive together.
                $universities->createIndex(['email' => 1], ['unique' => true]);

                // Block duplicate emails before creating the account.
                $emailExists = $universities->countDocuments(
                    ['email' => $values['email']],
                    ['limit' => 1]
                ) > 0;

                if ($emailExists) {
                    $errors['email'] = 'An account with this email already exists. Try logging in.';
                } else {
                    try {
                        $universities->insertOne([
                            'name'           => $values['name'],
                            'country'        => $values['country'],
                            'email'          => $values['email'],
                            'website'        => $values['website'],
                            'contact_person' => $values['contact_person'],
                            // Never store the plain password.
                            'password_hash'  => password_hash($values['password'], PASSWORD_DEFAULT),
                            'created_at'     => new MongoDB\BSON\UTCDateTime(),
                        ]);

                        // Flash message is shown by includes/header.php after redirect.
                        $_SESSION['flash_success'] = 'Registration successful. You can log in now.';
                        header('Location: login.php');
                        exit;
                    } catch (Throwable $error) {
                        // A race can beat the check above; the unique index reports E11000.
                        if (strpos($error->getMessage(), 'E11000') !== false) {
                            $errors['email'] = 'An account with this email already exists. Try logging in.';
                        } else {
                            throw $error;
                        }
                    }
                }
            } catch (Throwable $error) {
                // Log details for debugging, but show a friendly message to the user.
                error_log('GUCAIR registration failed: ' . $error->getMessage());
                $errors['general'] = 'We could not save your registration. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<form class="form-card" method="post" action="register.php" data-form="register" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <h1>Create an account</h1>
    <p class="lead">Register your university to submit AI readiness assessments.</p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <div class="form-group">
        <label for="name">University name</label>
        <input class="form-control" id="name" name="name" type="text"
               autocomplete="organization" maxlength="120"
               value="<?= e($values['name']) ?>"
               aria-describedby="name-error"<?= invalid_attr($errors, 'name') ?>>
        <span class="field-error" id="name-error" aria-live="polite"><?= e(field_error($errors, 'name')) ?></span>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="country">Country</label>
            <input class="form-control" id="country" name="country" type="text"
                   autocomplete="country-name" maxlength="100"
                   value="<?= e($values['country']) ?>"
                   aria-describedby="country-error"<?= invalid_attr($errors, 'country') ?>>
            <span class="field-error" id="country-error" aria-live="polite"><?= e(field_error($errors, 'country')) ?></span>
        </div>

        <div class="form-group">
            <label for="email">University email</label>
            <input class="form-control" id="email" name="email" type="email"
                   autocomplete="email" maxlength="254"
                   value="<?= e($values['email']) ?>"
                   aria-describedby="email-error"<?= invalid_attr($errors, 'email') ?>>
            <span class="field-error" id="email-error" aria-live="polite"><?= e(field_error($errors, 'email')) ?></span>
        </div>
    </div>

    <div class="form-group">
        <label for="website">Website URL</label>
        <input class="form-control" id="website" name="website" type="url"
               autocomplete="url" placeholder="https://example.edu"
               value="<?= e($values['website']) ?>"
               aria-describedby="website-error website-hint"<?= invalid_attr($errors, 'website') ?>>
        <span class="hint" id="website-hint">Include https:// at the beginning.</span>
        <span class="field-error" id="website-error" aria-live="polite"><?= e(field_error($errors, 'website')) ?></span>
    </div>

    <div class="form-group">
        <label for="contact_person">Contact person</label>
        <input class="form-control" id="contact_person" name="contact_person" type="text"
               autocomplete="name" maxlength="120"
               value="<?= e($values['contact_person']) ?>"
               aria-describedby="contact_person-error"<?= invalid_attr($errors, 'contact_person') ?>>
        <span class="field-error" id="contact_person-error" aria-live="polite"><?= e(field_error($errors, 'contact_person')) ?></span>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password"
               autocomplete="new-password"
               aria-describedby="password-error password-hint"<?= invalid_attr($errors, 'password') ?>>
        <span class="hint" id="password-hint">Use at least 8 characters.</span>
        <span class="field-error" id="password-error" aria-live="polite"><?= e(field_error($errors, 'password')) ?></span>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Create account</button>
        <a href="login.php">Already registered? Log in</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
