<?php
/**
 * University profile page (private).
 *
 * Shows the registered university and lets it update country, website,
 * and contact person. Validation runs in JavaScript and PHP.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/config/database.php';

require_login();

$university = null;
$values = [
    'country'        => '',
    'website'        => '',
    'contact_person' => '',
];
$errors = [];
$dbError = '';
$objectId = null;
$universities = null;

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');

try {
    if (!preg_match('/^[a-f0-9]{24}$/i', $sessionUserId)) {
        $dbError = 'Your session is invalid. Please log out and log in again.';
    } else {
        $universities = get_universities_collection();
        $objectId = new MongoDB\BSON\ObjectId($sessionUserId);

        $university = $universities->findOne(
            ['_id' => $objectId],
            ['projection' => [
                'name'           => 1,
                'email'          => 1,
                'country'        => 1,
                'website'        => 1,
                'contact_person' => 1,
            ]]
        );

        if (!$university) {
            $dbError = 'We could not find your university record. Please log out and log in again.';
        } else {
            // Fill the form with saved values on the first page load.
            $values['country']        = (string) ($university['country'] ?? '');
            $values['website']        = (string) ($university['website'] ?? '');
            $values['contact_person'] = (string) ($university['contact_person'] ?? '');
        }
    }
} catch (Throwable $error) {
    error_log('GUCAIR profile load failed: ' . $error->getMessage());
    $dbError = 'We could not load your profile right now. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'This form expired. Please reload the page and try again.';
    } else {
        // A failed submit keeps the submitted values instead of the saved ones.
        [$values, $errors] = validate_profile($_POST);

        if (empty($errors) && $objectId !== null && $dbError === '') {
            try {
                $universities->updateOne(
                    ['_id' => $objectId],
                    ['$set' => [
                        'country'        => $values['country'],
                        'website'        => $values['website'],
                        'contact_person' => $values['contact_person'],
                        'updated_at'     => new MongoDB\BSON\UTCDateTime(),
                    ]]
                );

                $_SESSION['flash_success'] = 'Profile updated successfully.';
                header('Location: profile.php');
                exit;
            } catch (Throwable $error) {
                error_log('GUCAIR profile update failed: ' . $error->getMessage());
                $errors['general'] = 'We could not save your changes. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1>University profile</h1>
    <p class="lead">View your registered details and update the contact information below.</p>
</header>

<?php if ($dbError !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
<?php endif; ?>

<section class="detail-card" aria-label="Registered university details">
    <div class="detail-list">
        <article class="detail-item">
            <span>University</span>
            <strong><?= $university && isset($university['name']) ? e((string) $university['name']) : '&mdash;' ?></strong>
        </article>

        <article class="detail-item">
            <span>University email</span>
            <strong><?= $university && isset($university['email']) ? e((string) $university['email']) : '&mdash;' ?></strong>
        </article>
    </div>
</section>

<form class="form-card assessment-form" method="post" action="profile.php"
      data-form="profile" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <h2>Edit profile</h2>
    <p class="hint">Only country, website, and contact person can be changed here.</p>

    <div class="form-group">
        <label for="country">Country</label>
        <input class="form-control" id="country" name="country" type="text"
               autocomplete="country-name" maxlength="100"
               value="<?= e($values['country']) ?>"
               aria-describedby="country-error"<?= invalid_attr($errors, 'country') ?>>
        <span class="field-error" id="country-error" aria-live="polite"><?= e(field_error($errors, 'country')) ?></span>
    </div>

    <div class="form-group">
        <label for="website">Website URL</label>
        <input class="form-control" id="website" name="website" type="url"
               autocomplete="url" maxlength="255" placeholder="https://example.edu"
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

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a href="dashboard.php">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
