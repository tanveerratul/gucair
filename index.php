<?php
/**
 * GUCAIR home page.
 *
 * Shared header/navbar/footer come from includes/.
 * The session state is already available through includes/auth.php.
 */

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-brand">
        <span class="hero-logo" aria-hidden="true">G</span>
        <div>
            <p class="eyebrow">Global University Consortium of AI Readiness</p>
            <h1>GUCAIR</h1>
        </div>
    </div>

    <p class="lead">
        GUCAIR is a university Web Engineering Lab project that brings institutions together
        to understand, compare, and improve how ready they are for artificial intelligence
        in teaching, research, and administration.
    </p>

    <?php if (is_logged_in()): ?>
        <p class="hero-note">
            Welcome back<?php if (current_user_name() !== ''): ?>, <?= htmlspecialchars(current_user_name(), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>.
        </p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="dashboard.php">Dashboard</a>
            <?= logout_form('btn btn-secondary') ?>
        </div>
    <?php else: ?>
        <div class="hero-actions">
            <a class="btn btn-primary" href="register.php">Register</a>
            <a class="btn btn-secondary" href="login.php">Log in</a>
        </div>
    <?php endif; ?>

    <p class="hero-links">
        <a href="directory.php">Browse universities</a>
        <span aria-hidden="true">&middot;</span>
        <a href="assessments.php">View assessments</a>
    </p>
</section>

<section class="section" id="about">
    <h2>About the consortium</h2>
    <p class="lead">
        GUCAIR gives universities a shared place to describe their AI readiness.
        Member institutions can add their profiles and record assessments, while visitors
        can explore the results in one clear view.
    </p>
    <p>
        The site is built for a Web Engineering Lab assignment, so the code stays simple and
        readable. University and assessment records are stored in MongoDB and displayed from
        the database rather than being written into the pages.
    </p>
</section>

<section class="section" id="objectives">
    <h2>Our objectives</h2>

    <div class="card-grid">
        <article class="card">
            <span class="objective-number" aria-hidden="true">1</span>
            <h3>Share a common language</h3>
            <p>Use one clear set of fields for university profiles and AI readiness records.</p>
        </article>

        <article class="card">
            <span class="objective-number" aria-hidden="true">2</span>
            <h3>Support planning</h3>
            <p>Help institutions see where they are and what they can improve next.</p>
        </article>

        <article class="card">
            <span class="objective-number" aria-hidden="true">3</span>
            <h3>Enable comparison</h3>
            <p>Make assessment information easy to review across participating universities.</p>
        </article>

        <article class="card">
            <span class="objective-number" aria-hidden="true">4</span>
            <h3>Keep records current</h3>
            <p>Allow logged-in users to submit assessments through validated forms.</p>
        </article>
    </div>
</section>

<section class="section" id="readiness">
    <h2>AI readiness in higher education</h2>
    <p class="lead">
        AI readiness is more than having access to new tools. It is the extent to which a
        university can adopt AI responsibly and effectively across its work.
    </p>

    <div class="readiness-panel">
        <h3>What readiness covers</h3>
        <ul class="readiness-list">
            <li>People: skills, training, and confidence.</li>
            <li>Processes: policies, governance, and support.</li>
            <li>Data: quality, access, and responsible use.</li>
            <li>Infrastructure: systems, tools, and security.</li>
        </ul>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
