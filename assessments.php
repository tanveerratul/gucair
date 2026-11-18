<?php
/**
 * Public assessment results page.
 *
 * Every row comes from MongoDB. University names are loaded in one extra
 * query so the page does not query the database inside the table loop.
 */

require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$assessments = [];
$universitiesById = [];
$dbError = '';

try {
    $assessmentCollection = get_assessments_collection();

    $cursor = $assessmentCollection->find(
        [],
        [
            'sort' => ['created_at' => -1, '_id' => -1],
            'projection' => [
                'university_id'   => 1,
                'assessment_year' => 1,
                'score'           => 1,
                'level'           => 1,
                'created_at'      => 1,
            ],
        ]
    );

    foreach ($cursor as $assessment) {
        $assessments[] = $assessment;
    }

    // Collect unique ObjectIds, then load only the universities we need.
    $objectIds = [];
    foreach ($assessments as $assessment) {
        if (!isset($assessment['university_id'])) {
            continue;
        }

        $id = strtolower((string) $assessment['university_id']);
        if (preg_match('/^[a-f0-9]{24}$/i', $id) && !isset($objectIds[$id])) {
            $objectIds[$id] = new MongoDB\BSON\ObjectId($id);
        }
    }

    if (!empty($objectIds)) {
        $universityCursor = get_universities_collection()->find(
            ['_id' => ['$in' => array_values($objectIds)]],
            ['projection' => ['name' => 1, 'country' => 1]]
        );

        foreach ($universityCursor as $university) {
            $universitiesById[strtolower((string) $university['_id'])] = $university;
        }
    }
} catch (Throwable $error) {
    error_log('GUCAIR assessments query failed: ' . $error->getMessage());
    $dbError = 'We could not load assessment results right now. Please try again later.';
}

$pageTitle = 'Assessments';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1>Assessment results</h1>
    <p class="lead">AI readiness results submitted by registered universities.</p>
</header>

<?php if ($dbError !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<?php if (empty($assessments)): ?>
    <div class="empty-state">No assessments found yet.</div>
<?php else: ?>
    <p class="hint">Showing <?= e((string) count($assessments)) ?> saved assessment(s).</p>

    <div class="table-wrapper">
        <table>
            <caption>Saved AI readiness assessments</caption>
            <thead>
                <tr>
                    <th scope="col">University</th>
                    <th scope="col">Country</th>
                    <th scope="col">Date</th>
                    <th scope="col">Average score</th>
                    <th scope="col">Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assessments as $assessment): ?>
                    <?php
                    $id = isset($assessment['university_id'])
                        ? strtolower((string) $assessment['university_id'])
                        : '';
                    $university = $universitiesById[$id] ?? null;

                    $universityName = $university && isset($university['name'])
                        ? (string) $university['name']
                        : 'University record unavailable';
                    $country = $university && isset($university['country'])
                        ? (string) $university['country']
                        : '—';

                    $dateText = isset($assessment['created_at'])
                        ? format_bson_date($assessment['created_at'])
                        : '—';
                    if ($dateText === '—' && isset($assessment['assessment_year'])) {
                        $dateText = (string) $assessment['assessment_year'];
                    }

                    if (isset($assessment['score']) && is_numeric($assessment['score'])) {
                        $scoreText = number_format((float) $assessment['score'], 2) . ' / 5.00';
                        $levelText = isset($assessment['level']) && (string) $assessment['level'] !== ''
                            ? (string) $assessment['level']
                            : get_assessment_level((float) $assessment['score']);
                    } else {
                        $scoreText = '—';
                        $levelText = '—';
                    }
                    ?>
                    <tr>
                        <td>
                            <?php if (preg_match('/^[a-f0-9]{24}$/i', $id)): ?>
                                <a href="university_view.php?id=<?= e($id) ?>"><?= e($universityName) ?></a>
                            <?php else: ?>
                                <?= e($universityName) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($country) ?></td>
                        <td><?= e($dateText) ?></td>
                        <td><?= e($scoreText) ?></td>
                        <td>
                            <?php if ($levelText !== '—'): ?>
                                <span class="badge"><?= e($levelText) ?></span>
                            <?php else: ?>
                                <?= e($levelText) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
