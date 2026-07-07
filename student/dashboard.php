<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$student = get_student_by_user($pdo, current_user_id());

// Current/most recent placement (accepted or completed takes priority over pending)
$stmt = $pdo->prepare("
    SELECT pl.*, c.company_name, c.industry, c.location
    FROM placements pl
    JOIN companies c ON c.id = pl.company_id
    WHERE pl.student_id = ?
    ORDER BY (pl.status = 'accepted') DESC, (pl.status = 'completed') DESC, pl.applied_at DESC
    LIMIT 1
");
$stmt->execute([$student['id']]);
$activePlacement = $stmt->fetch();

$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM placements WHERE student_id = ? AND status = 'pending'");
$pendingCount->execute([$student['id']]);
$pendingCount = $pendingCount->fetchColumn();

$logbookCount = 0;
$reportCount  = 0;
if ($activePlacement && in_array($activePlacement['status'], ['accepted', 'completed'], true)) {
    $logbookCount = $pdo->prepare("SELECT COUNT(*) FROM logbook_entries WHERE placement_id = ?");
    $logbookCount->execute([$activePlacement['id']]);
    $logbookCount = $logbookCount->fetchColumn();

    $reportCount = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE placement_id = ?");
    $reportCount->execute([$activePlacement['id']]);
    $reportCount = $reportCount->fetchColumn();
}

$page_title  = 'Student Dashboard';
$active_nav  = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="card-grid" style="margin-bottom:22px;">
    <div class="stat-box">
        <div class="stat-number"><?= h(ucfirst($student['status'])) ?></div>
        <div class="stat-label">Placement Status</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$pendingCount ?></div>
        <div class="stat-label">Pending Applications</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$logbookCount ?></div>
        <div class="stat-label">Logbook Entries</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$reportCount ?></div>
        <div class="stat-label">Reports Submitted</div>
    </div>
</div>

<div class="card">
    <h2>Your Placement</h2>
    <?php if ($activePlacement): ?>
        <p><strong><?= h($activePlacement['company_name']) ?></strong> &middot; <?= h($activePlacement['industry']) ?> &middot; <?= h($activePlacement['location']) ?></p>
        <p>Status: <span class="badge <?= status_badge_class($activePlacement['status']) ?>"><?= h($activePlacement['status']) ?></span></p>
        <?php if (in_array($activePlacement['status'], ['accepted', 'completed'], true)): ?>
            <a class="btn btn-small" href="<?= BASE_URL ?>/student/logbook.php">Open Logbook</a>
            <a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/student/reports.php">Open Reports</a>
        <?php elseif ($activePlacement['status'] === 'pending'): ?>
            <p class="muted">Your application is awaiting a decision from the company.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted">You have not applied to any placement yet.</p>
        <a class="btn btn-small" href="<?= BASE_URL ?>/student/companies.php">Find a Placement</a>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
