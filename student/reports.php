<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$student = get_student_by_user($pdo, current_user_id());

$stmt = $pdo->prepare("
    SELECT pl.*, c.company_name
    FROM placements pl JOIN companies c ON c.id = pl.company_id
    WHERE pl.student_id = ? AND pl.status IN ('accepted', 'completed')
    ORDER BY pl.decided_at DESC LIMIT 1
");
$stmt->execute([$student['id']]);
$placement = $stmt->fetch();

$errors = [];

if ($placement && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['report_type'] ?? 'weekly';
    $period = trim($_POST['period_label'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!in_array($type, ['weekly', 'monthly', 'final'], true)) $errors[] = 'Invalid report type.';
    if ($period === '')  $errors[] = 'Please label this reporting period (e.g. "Week 3").';
    if ($content === '') $errors[] = 'Report content cannot be empty.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO reports (placement_id, report_type, period_label, content) VALUES (?, ?, ?, ?)")
            ->execute([$placement['id'], $type, $period, $content]);
        flash('success', 'Report submitted.');
        header('Location: ' . BASE_URL . '/student/reports.php');
        exit;
    }
}

$reports = [];
if ($placement) {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE placement_id = ? ORDER BY submitted_at DESC");
    $stmt->execute([$placement['id']]);
    $reports = $stmt->fetchAll();
}

$page_title = 'Reports';
$active_nav = 'reports';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$placement): ?>
    <div class="card empty-state">
        You need an accepted placement before you can submit reports.
        <br><a href="<?= BASE_URL ?>/student/companies.php">Find a placement</a>.
    </div>
<?php else: ?>
    <p class="muted" style="margin-top:-6px;">Placement: <strong><?= h($placement['company_name']) ?></strong></p>

    <div class="card">
        <h2>Submit a Report</h2>
        <?php if ($errors): ?>
            <div class="flash flash-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="two-col">
                <div class="field">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report_type">
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="final">Final</option>
                    </select>
                </div>
                <div class="field">
                    <label for="period_label">Period Label</label>
                    <input type="text" id="period_label" name="period_label" placeholder="e.g. Week 3" required>
                </div>
            </div>
            <div class="field">
                <label for="content">Report Content</label>
                <textarea id="content" name="content" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn">Submit Report</button>
        </form>
    </div>

    <div class="card">
        <h2>Your Reports</h2>
        <?php if (empty($reports)): ?>
            <p class="muted">No reports submitted yet.</p>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="card" style="background:var(--bg);">
                    <p style="margin:0 0 6px;">
                        <strong><?= h(ucfirst($r['report_type'])) ?> &middot; <?= h($r['period_label']) ?></strong>
                        &nbsp; <span class="badge <?= status_badge_class($r['status']) ?>"><?= h($r['status']) ?></span>
                        <?php if ($r['score'] !== null): ?> &nbsp; <span class="muted">Score: <?= (int)$r['score'] ?>/100</span><?php endif; ?>
                    </p>
                    <p style="margin:0 0 8px;"><?= nl2br(h($r['content'])) ?></p>
                    <?php if ($r['supervisor_feedback']): ?>
                        <p class="muted" style="margin:0;"><strong>Supervisor feedback:</strong> <?= nl2br(h($r['supervisor_feedback'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
