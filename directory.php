<?php
/**
 * Public university directory.
 *
 * University records and the country dropdown both come from MongoDB.
 * Search text is escaped before being used in a MongoDB regular expression.
 */

require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$search = isset($_GET['search']) && is_string($_GET['search'])
    ? substr(trim($_GET['search']), 0, 100)
    : '';

$selectedCountry = isset($_GET['country']) && is_string($_GET['country'])
    ? substr(trim($_GET['country']), 0, 100)
    : '';

$universities = [];
$countries = [];
$latestByUniversity = [];
$dbError = '';

try {
    $universityCollection = get_universities_collection();
    $assessmentCollection = get_assessments_collection();

    // Build the country dropdown from real stored values.
    $distinctCountries = $universityCollection->distinct('country');
    foreach ($distinctCountries as $countryValue) {
        $countryValue = (string) $countryValue;
        if ($countryValue !== '') {
            $countries[] = $countryValue;
        }
    }
    sort($countries, SORT_NATURAL | SORT_FLAG_CASE);

    // Ignore a country value that is not in the database.
    if ($selectedCountry !== '' && !in_array($selectedCountry, $countries, true)) {
        $selectedCountry = '';
    }

    $filter = [];

    if ($search !== '') {
        // preg_quote() stops user input from changing the regex pattern.
        $safeSearch = preg_quote($search, '/');
        $filter['name'] = new MongoDB\BSON\Regex($safeSearch, 'i');
    }

    if ($selectedCountry !== '') {
        $filter['country'] = $selectedCountry;
    }

    $cursor = $universityCollection->find(
        $filter,
        ['sort' => ['name' => 1]]
    );

    foreach ($cursor as $university) {
        $universities[] = $university;
    }

    // One query for all relevant assessments avoids querying inside the loop.
    $ids = [];
    foreach ($universities as $university) {
        $ids[] = $university['_id'];
    }

    if (!empty($ids)) {
        $assessmentCursor = $assessmentCollection->find(
            ['university_id' => ['$in' => $ids]],
            [
                'sort' => ['created_at' => -1, '_id' => -1],
                'projection' => [
                    'university_id' => 1,
                    'score'         => 1,
                    'level'         => 1,
                ],
            ]
        );

        // Newest first, so the first match for each university is its latest.
        foreach ($assessmentCursor as $assessment) {
            $key = (string) $assessment['university_id'];
            if (!isset($latestByUniversity[$key])) {
                $latestByUniversity[$key] = $assessment;
            }
        }
    }
} catch (Throwable $error) {
    error_log('GUCAIR directory query failed: ' . $error->getMessage());
    $dbError = 'We could not load the university directory right now. Please try again later.';
}

$pageTitle = 'University Directory';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1>University directory</h1>
    <p class="lead">Browse registered universities and their latest AI readiness results.</p>
</header>

<?php if ($dbError !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($dbError) ?></div>
<?php endif; ?>

<form class="filter-form" method="get" action="directory.php">
    <div class="form-group">
        <label for="search">Search by name</label>
        <input class="form-control" id="search" name="search" type="search"
               maxlength="100" placeholder="University name"
               value="<?= e($search) ?>">
    </div>

    <div class="form-group">
        <label for="country">Country</label>
        <select class="form-control" id="country" name="country">
            <option value="">All countries</option>
            <?php foreach ($countries as $country): ?>
                <option value="<?= e($country) ?>"<?= $country === $selectedCountry ? ' selected' : '' ?>>
                    <?= e($country) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Apply filters</button>
        <?php if ($search !== '' || $selectedCountry !== ''): ?>
            <a class="btn btn-secondary" href="directory.php">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($universities)): ?>
    <div class="empty-state">
        <?= ($search !== '' || $selectedCountry !== '')
            ? 'No universities match your filters.'
            : 'No universities found yet.' ?>
    </div>
<?php else: ?>
    <p class="hint">Showing <?= e((string) count($universities)) ?> university record(s).</p>

    <div class="table-wrapper">
        <table>
            <caption>Registered universities</caption>
            <thead>
                <tr>
                    <th scope="col">University</th>
                    <th scope="col">Country</th>
                    <th scope="col">Website</th>
                    <th scope="col">Latest score</th>
                    <th scope="col">Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($universities as $university): ?>
                    <?php
                    $id = (string) $university['_id'];
                    $latest = isset($latestByUniversity[$id]) ? $latestByUniversity[$id] : null;
                    $website = (string) ($university['website'] ?? '');

                    if ($latest && isset($latest['score']) && is_numeric($latest['score'])) {
                        $scoreText = number_format((float) $latest['score'], 2) . ' / 5.00';
                        $levelText = isset($latest['level']) && (string) $latest['level'] !== ''
                            ? (string) $latest['level']
                            : get_assessment_level((float) $latest['score']);
                    } else {
                        $scoreText = 'No assessment yet';
                        $levelText = 'No assessment yet';
                    }
                    ?>
                    <tr>
                        <td>
                            <a href="university_view.php?id=<?= e(rawurlencode($id)) ?>">
                                <?= e((string) ($university['name'] ?? '')) ?>
                            </a>
                        </td>
                        <td><?= e((string) ($university['country'] ?? '')) ?></td>
                        <td>
                            <?php if (is_safe_http_url($website)): ?>
                                <a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer">Visit site</a>
                            <?php else: ?>
                                <?= e($website) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($scoreText) ?></td>
                        <td>
                            <?php if ($levelText !== 'No assessment yet'): ?>
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
