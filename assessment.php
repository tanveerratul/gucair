<?php
/**
 * AI readiness assessment page (private).
 *
 * Logged-in users rate five categories from 1 to 5. PHP validates the
 * ratings, computes the average and level, then saves the assessment.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// Protect this page before any HTML is printed.
require_login();

$categories = assessment_categories();

$errors = [];
$result = null;

// A successful submission redirects here with its result in the session.
if (!empty($_SESSION['flash_assessment_result']) && is_array($_SESSION['flash_assessment_result'])) {
    $result = $_SESSION['flash_assessment_result'];
    unset($_SESSION['flash_assessment_result']);
}

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'This form expired. Please reload the page and try again.';
    } else {
        $scores = [];

        // Validate every rating before doing any database work.
        foreach ($categories as $name => $label) {
            $raw = $_POST[$name] ?? '';
            $raw = is_scalar($raw) ? trim((string) $raw) : '';

            if ($raw === '') {
                $errors[$name] = $label . ' rating is required.';
                continue;
            }

            if (!ctype_digit($raw)) {
                $errors[$name] = $label . ' rating must be a whole number from 1 to 5.';
                continue;
            }

            $value = (int) $raw;
            if ($value < 1 || $value > 5) {
                $errors[$name] = $label . ' rating must be between 1 and 5.';
                continue;
            }

            $scores[$name] = $value;
        }

        // The session user id must be a valid MongoDB ObjectId string.
        if (!preg_match('/^[a-f0-9]{24}$/i', $sessionUserId)) {
            $errors['general'] = 'Your session is invalid. Please log out and log in again.';
        }

        if (empty($errors)) {
            try {
                $universities = get_universities_collection();
                $assessments = get_assessments_collection();
                $objectId = new MongoDB\BSON\ObjectId($sessionUserId);

                // Make sure the account still exists before saving a record.
                $university = $universities->findOne(
                    ['_id' => $objectId],
                    ['projection' => ['name' => 1]]
                );

                if (!$university) {
                    $errors['general'] = 'We could not find your university record. Please log in again.';
                } else {
                    $average = round(array_sum($scores) / count($scores), 2);
                    $level = get_assessment_level($average);
                    $year = (int) date('Y');

                    $assessments->insertOne([
                        'university_id'   => $objectId,
                        'assessment_year' => $year,
                        'score'           => $average,
                        'level'           => $level,
                        'categories'      => $scores,
                        'created_at'      => new MongoDB\BSON\UTCDateTime(),
                    ]);

                    // Show the result after a redirect so refresh cannot resubmit.
                    $_SESSION['flash_success'] = 'Assessment saved successfully.';
                    $_SESSION['flash_assessment_result'] = [
                        'average' => $average,
                        'level'   => $level,
                        'scores'  => $scores,
                        'year'    => $year,
                    ];

                    header('Location: assessment.php');
                    exit;
                }
            } catch (Throwable $error) {
                error_log('GUCAIR assessment save failed: ' . $error->getMessage());
                $errors['general'] = 'We could not save the assessment. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Assessment';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($result !== null): ?>
    <?php
    $resultScores = isset($result['scores']) && is_array($result['scores']) ? $result['scores'] : [];
    ?>
    <section class="result-card" aria-labelledby="result-title">
        <span class="badge">Saved</span>
        <h2 id="result-title">Assessment result</h2>
        <p class="lead">Your AI readiness assessment has been stored in the database.</p>

        <div class="result-metrics">
            <div class="result-metric">
                <span>Average score</span>
                <strong><?= e(number_format((float) ($result['average'] ?? 0), 2)) ?> / 5.00</strong>
            </div>
            <div class="result-metric">
                <span>Readiness level</span>
                <strong><?= e((string) ($result['level'] ?? 'Unrated')) ?></strong>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <caption>Category ratings for <?= e((string) ($result['year'] ?? date('Y'))) ?></caption>
                <thead>
                    <tr>
                        <th scope="col">Category</th>
                        <th scope="col">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $name => $label): ?>
                        <tr>
                            <td><?= e($label) ?></td>
                            <td><span class="badge"><?= e((string) ($resultScores[$name] ?? '')) ?></span> / 5</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<form class="form-card assessment-form" method="post" action="assessment.php"
      data-form="assessment" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <h1>AI readiness assessment</h1>
    <p class="lead">
        Submitting for <strong><?= e(current_user_name()) ?></strong>.
        Rate each category from 1 (low) to 5 (high).
    </p>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <p class="hint">Choose one rating for every category.</p>

    <?php foreach ($categories as $name => $label): ?>
        <fieldset class="rating-fieldset" data-field="<?= e($name) ?>"
                  data-label="<?= e($label) ?>"
                  aria-describedby="<?= e($name) ?>-error"<?= invalid_attr($errors, $name) ?>>
            <legend><?= e($label) ?></legend>

            <div class="rating-scale">
                <?php for ($score = 1; $score <= 5; $score++): ?>
                    <label class="rating-option">
                        <input type="radio" name="<?= e($name) ?>" value="<?= $score ?>"
                               aria-label="<?= e($label) ?> rating <?= $score ?>"<?= rating_checked($name, $score) ?>>
                        <span><?= $score ?></span>
                    </label>
                <?php endfor; ?>
            </div>

            <span class="field-error" id="<?= e($name) ?>-error" aria-live="polite">
                <?= e(field_error($errors, $name)) ?>
            </span>
        </fieldset>
    <?php endforeach; ?>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save assessment</button>
        <a href="index.php">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
