<?php
/**
 * Assessment history page (private).
 *
 * Lists the logged-in university's past assessments, newest first.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

require_login();

$categories = assessment_categories();
$assessments = [];
$dbError = '';

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');

try {
    if (!preg_match('/^[a-f0-9]{24}$/i', $sessionUserId)) {
        $dbError = 'Your session is invalid. Please log out and log in again.';
    } else {
        $assessmentsCollection = get_assessments_collection();
        $objectId = new MongoDB\BSON\ObjectId($sessionUserId);

        $cursor = $assessmentsCollection->find(
            ['university_id' => $objectId],
            ['sort' => ['created_at' => -1, '_id' => -1]]
        );

        foreach ($cursor as $assessment) {
            $assessments[] = $assessment;
        }
    }
} catch (Throwable $error) {
    error_log('GUCAIR history query failed: ' . $error->getMessage());
    $dbError = 'We could not load your assessment history right now. Please try again later.';
}

$pageTitle = 'Assessment History';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1>Assessment history</h1>
    <p class="lead">Past AI readiness assessments for <?= e(current_user_name()) ?>, newest first.</p>
</header>

<?php if ($dbError !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<?php if (empty($assessments)): ?>
    <div class="empty-state">
        <p>No assessments yet.</p>
        <a class="btn btn-primary" href="assessment.php">Submit your first assessment</a>
    </div>
<?php else: ?>
    <p class="hint">Showing <?= e((string) count($assessments)) ?> saved assessment(s).</p>

    <div class="table-wrapper history-wrapper">
        <table>
            <caption>Saved assessments</caption>
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <?php foreach ($categories as $label): ?>
                        <th scope="col"><?= e($label) ?></th>
                    <?php endforeach; ?>
                    <th scope="col">Average</th>
                    <th scope="col">Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $assessment): ?>
                    <?php
                    // MongoDB returns embedded documents as BSONDocument objects.
                    $rawCategories = $assessment['categories'] ?? null;
                    if ($rawCategories instanceof MongoDB\Model\BSONDocument) {
                        $savedCategories = $rawCategories->getArrayCopy();
                    } elseif (is_array($rawCategories)) {
                        $savedCategories = $rawCategories;
                    } else {
                        $savedCategories = [];
                    }

                    $hasScore = isset($assessment['score']) && is_numeric($assessment['score']);
                    $average = $hasScore ? number_format((float) $assessment['score'], 2) : '—';

                    if ($hasScore) {
                        $level = isset($assessment['level']) && (string) $assessment['level'] !== ''
                            ? (string) $assessment['level']
                            : get_assessment_level((float) $assessment['score']);
                    } else {
                        $level = '—';
                    }
                    ?>
                    <tr>
                        <td><?= e(format_bson_date($assessment['created_at'] ?? null)) ?></td>
                        <?php foreach (array_keys($categories) as $key): ?>
                            <td><?= isset($savedCategories[$key]) ? e((string) $savedCategories[$key]) . ' / 5' : '—' ?></td>
                        <?php endforeach; ?>
                        <td><?= e($average) ?><?= $hasScore ? ' / 5.00' : '' ?></td>
                        <td>
                            <?php if ($level !== '—'): ?>
                                <span class="badge"><?= e($level) ?></span>
                            <?php else: ?>
                                <?= e($level) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="form-actions">
    <a class="btn btn-secondary" href="assessment.php">Submit another assessment</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
