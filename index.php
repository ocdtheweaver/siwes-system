<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url_for_role(current_role()));
    exit;
}

$page_title = null; // custom hero layout instead of the standard page title
require __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>SIWES Placement, Tracking &amp; Reporting Portal</h1>
    <p>
        A single platform for managing the Student Industrial Work Experience Scheme (SIWES) -
        from finding the right placement, to logging daily activities, to submitting and
        reviewing reports - replacing scattered paperwork with one organised system.
    </p>
    <a class="btn" href="<?= BASE_URL ?>/auth/register.php">Get started</a>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/auth/login.php">Log in</a>
</div>

<div class="feature-grid">
    <div class="card">
        <h3>For Students</h3>
        <p>Build a profile, get matched to companies that fit your skills and department, apply for placement, and keep your logbook and reports in one place.</p>
    </div>
    <div class="card">
        <h3>For Supervisors / Companies</h3>
        <p>Review applicants ranked by fit, accept the right students, and monitor their logbooks and reports without chasing paper files.</p>
    </div>
    <div class="card">
        <h3>For Institution Administrators</h3>
        <p>See every student, every company, and every placement in one dashboard - with full visibility into reporting compliance across the programme.</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
