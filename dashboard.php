<?php
/**
 * GUCAIR dashboard (private).
 *
 * Shows the logged-in university, a live count of registered universities,
 * and that university's latest assessment result.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// Protect the page before printing any HTML.
require_login();

$university = null;
$totalUniversities = 0;
$latestAssessment = null;
$dbError = '';

$sessionUserId = (string) ($_SESSION['user_id'] ?? '');

try {
    if (!preg_match('/^[a-f0-9]{24}$/i', $sessionUserId)) {
        $dbError = 'Your session is invalid. Please log out and log in again.';
    } else {
        $universities = get_universities_collection();
        $assessments = get_assessments_collection();
        $objectId = new MongoDB\BSON\ObjectId($sessionUserId);

        // Load the current university from MongoDB, not from session data.
        $university = $universities->findOne(
            ['_id' => $objectId],
            ['projection' => ['name' => 1, 'country' => 1]]
        );

        if (!$university) {
            $dbError = 'We could not find your university record. Please log out and log in again.';
        }

        // Live count of registered universities.
        $totalUniversities = $universities->countDocuments([]);

        // The newest assessment for this university.
        $latestAssessment = $assessments->findOne(
            ['university_id' => $objectId],
            ['sort' => ['created_at' => -1, '_id' => -1]]
        );
    }
} catch (Throwable $error) {
    error_log('GUCAIR dashboard query failed: ' . $error->getMessage());
    $dbError = 'We could not load dashboard data right now. Please try again later.';
}

$universityName = $university && isset($university['name'])
    ? (string) $university['name']
    : (string) current_user_name();

$country = $university && isset($university['country'])
    ? (string) $university['country']
    : '';

if ($latestAssessment && isset($latestAssessment['score']) && is_numeric($latestAssessment['score'])) {
    $latestScore = number_format((float) $latestAssessment['score'], 2) . ' / 5.00';
    $latestLevel = isset($latestAssessment['level']) && (string) $latestAssessment['level'] !== ''
        ? (string) $latestAssessment['level']
        : get_assessment_level((float) $latestAssessment['score']);
} else {
    $latestScore = 'No assessment yet';
    $latestLevel = 'No assessment yet';
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1>Welcome back<?= $universityName !== '' ? ', ' . e($universityName) : '' ?>!</h1>
    <p class="lead">Here is a quick snapshot of your university's GUCAIR activity.</p>
</header>

<?php if ($dbError !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<section class="dashboard-stats" aria-label="Dashboard summary">
    <article class="dashboard-stat">
        <span>University</span>
        <strong><?= $universityName !== '' ? e($universityName) : '&mdash;' ?></strong>
    </article>

    <article class="dashboard-stat">
        <span>Country</span>
        <strong><?= $country !== '' ? e($country) : '&mdash;' ?></strong>
    </article>

    <article class="dashboard-stat">
        <span>Registered universities</span>
        <strong><?= e((string) $totalUniversities) ?></strong>
    </article>

    <article class="dashboard-stat">
        <span>Latest assessment</span>
        <strong><?= e($latestScore) ?></strong>
        <?php if ($latestLevel !== 'No assessment yet'): ?>
            <span class="badge"><?= e($latestLevel) ?></span>
        <?php endif; ?>
    </article>
</section>

<section class="section" aria-labelledby="quick-links-title">
    <h2 id="quick-links-title">Quick links</h2>

    <div class="card-grid">
        <article class="card">
            <h3>Submit an assessment</h3>
            <p>Rate your university across the five AI readiness categories.</p>
            <div class="card-actions">
                <a class="btn btn-primary" href="assessment.php">Open assessment</a>
            </div>
        </article>

        <article class="card">
            <h3>University directory</h3>
            <p>Browse the registered universities stored in GUCAIR.</p>
            <div class="card-actions">
                <a class="btn btn-secondary" href="directory.php">Open directory</a>
            </div>
        </article>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
