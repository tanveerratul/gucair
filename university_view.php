<?php
/**
 * Public university detail page.
 *
 * Shows registered information from MongoDB. The password hash is never read
 * or printed here.
 */

require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$university = null;
$errorMessage = '';

if (!preg_match('/^[a-f0-9]{24}$/i', $id)) {
    $errorMessage = 'That university link is not valid.';
} else {
    try {
        $universities = get_universities_collection();
        $university = $universities->findOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['projection' => [
                'name'           => 1,
                'country'        => 1,
                'email'          => 1,
                'website'        => 1,
                'contact_person' => 1,
                'created_at'     => 1,
            ]]
        );

        if (!$university) {
            $errorMessage = 'That university was not found.';
        }
    } catch (Throwable $error) {
        error_log('GUCAIR university detail query failed: ' . $error->getMessage());
        $errorMessage = 'We could not load this university right now. Please try again later.';
    }
}

$pageTitle = $university && isset($university['name'])
    ? (string) $university['name']
    : 'University Not Found';
require_once __DIR__ . '/includes/header.php';
?>

<header class="page-header">
    <h1><?= $university && isset($university['name']) ? e((string) $university['name']) : 'University not found' ?></h1>
    <p class="lead">Registered information stored in GUCAIR.</p>
</header>

<?php if ($errorMessage !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($errorMessage) ?></div>
<?php endif; ?>

<?php if ($university): ?>
    <?php
    $website = (string) ($university['website'] ?? '');
    $registeredDate = isset($university['created_at'])
        ? format_bson_date($university['created_at'])
        : '—';
    ?>

    <section class="detail-card" aria-label="University information">
        <div class="detail-list">
            <article class="detail-item">
                <span>University name</span>
                <strong><?= e((string) ($university['name'] ?? '')) ?></strong>
            </article>

            <article class="detail-item">
                <span>Country</span>
                <strong><?= e((string) ($university['country'] ?? '')) ?></strong>
            </article>

            <article class="detail-item">
                <span>University email</span>
                <strong><?= e((string) ($university['email'] ?? '')) ?></strong>
            </article>

            <article class="detail-item">
                <span>Website</span>
                <strong>
                    <?php if (is_safe_http_url($website)): ?>
                        <a href="<?= e($website) ?>" target="_blank" rel="noopener noreferrer"><?= e($website) ?></a>
                    <?php else: ?>
                        <?= e($website) ?>
                    <?php endif; ?>
                </strong>
            </article>

            <article class="detail-item">
                <span>Contact person</span>
                <strong><?= e((string) ($university['contact_person'] ?? '')) ?></strong>
            </article>

            <article class="detail-item">
                <span>Registered</span>
                <strong><?= e($registeredDate) ?></strong>
            </article>
        </div>
    </section>
<?php endif; ?>

<div class="form-actions">
    <a class="btn btn-secondary" href="directory.php">Back to directory</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
