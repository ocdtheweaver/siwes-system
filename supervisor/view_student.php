<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('supervisor');

$company = get_company_by_user($pdo, current_user_id());
$placementId = (int) ($_GET['placement_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT pl.*, s.matric_no, s.department, s.level, s.skills, s.bio, u.full_name, u.email
    FROM placements pl
    JOIN students s ON s.id = pl.student_id
    JOIN users u ON u.id = s.user_id
    WHERE pl.id = ? AND pl.company_id = ?
");
$stmt->execute([$placementId, $company['id']]);
$placement = $stmt->fetch();

if (!$placement) {
    flash('error', 'Placement not found.');
    header('Location: ' . BASE_URL . '/supervisor/applicants.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment_entry_id'])) {
        $entryId = (int) $_POST['comment_entry_id'];
        $comment = trim($_POST['supervisor_comment'] ?? '');
        $pdo->prepare("UPDATE logbook_entries SET supervisor_comment=?, status='reviewed' WHERE id=? AND placement_id=?")
            ->execute([$comment, $entryId, $placement['id']]);
        flash('success', 'Comment saved.');
    } elseif (isset($_POST['feedback_report_id'])) {
        $reportId = (int) $_POST['feedback_report_id'];
        $feedback = trim($_POST['supervisor_feedback'] ?? '');
        $score    = $_POST['score'] === '' ? null : max(0, min(100, (int)$_POST['score']));
        $pdo->prepare("UPDATE reports SET supervisor_feedback=?, score=?, status='reviewed' WHERE id=? AND placement_id=?")
            ->execute([$feedback, $score, $reportId, $placement['id']]);
        flash('success', 'Feedback saved.');
    } elseif (isset($_POST['mark_completed'])) {
        $pdo->prepare("UPDATE placements SET status='completed' WHERE id=?")->execute([$placement['id']]);
        $pdo->prepare("UPDATE students SET status='completed' WHERE id=?")->execute([$placement['student_id']]);
        flash('success', 'Placement marked as completed.');
    }
    header('Location: ' . BASE_URL . '/supervisor/view_student.php?placement_id=' . $placement['id']);
    exit;
}

$entries = $pdo->prepare("SELECT * FROM logbook_entries WHERE placement_id = ? ORDER BY entry_date DESC, id DESC");
$entries->execute([$placement['id']]);
$entries = $entries->fetchAll();

$reports = $pdo->prepare("SELECT * FROM reports WHERE placement_id = ? ORDER BY submitted_at DESC");
$reports->execute([$placement['id']]);
$reports = $reports->fetchAll();

$page_title = $placement['full_name'];
$active_nav = 'applicants';
require __DIR__ . '/../includes/header.php';
?>

<p>
    <?= h($placement['matric_no']) ?> &middot; <?= h($placement['department']) ?> (<?= h($placement['level']) ?>)
    &middot; <span class="badge <?= status_badge_class($placement['status']) ?>"><?= h($placement['status']) ?></span>
</p>

<?php if ($placement['status'] === 'accepted'): ?>
    <form method="post" style="margin-bottom:18px;">
        <button type="submit" name="mark_completed" value="1" class="btn btn-secondary btn-small">Mark Placement as Completed</button>
    </form>
<?php endif; ?>

<div class="card">
    <h2>Logbook Entries</h2>
    <?php if (empty($entries)): ?>
        <p class="muted">No entries submitted yet.</p>
    <?php else: ?>
        <?php foreach ($entries as $e): ?>
            <div class="card" style="background:var(--bg);">
                <p style="margin:0 0 6px;">
                    <strong><?= h(date('d M Y', strtotime($e['entry_date']))) ?></strong>
                    &nbsp;<span class="badge <?= status_badge_class($e['status']) ?>"><?= h($e['status']) ?></span>
                </p>
                <p style="margin:0 0 10px;"><?= nl2br(h($e['activity_description'])) ?></p>
                <form method="post">
                    <input type="hidden" name="comment_entry_id" value="<?= (int)$e['id'] ?>">
                    <div class="field">
                        <label>Your Comment</label>
                        <textarea name="supervisor_comment" rows="2"><?= h($e['supervisor_comment']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-small">Save Comment</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Reports</h2>
    <?php if (empty($reports)): ?>
        <p class="muted">No reports submitted yet.</p>
    <?php else: ?>
        <?php foreach ($reports as $r): ?>
            <div class="card" style="background:var(--bg);">
                <p style="margin:0 0 6px;">
                    <strong><?= h(ucfirst($r['report_type'])) ?> &middot; <?= h($r['period_label']) ?></strong>
                    &nbsp;<span class="badge <?= status_badge_class($r['status']) ?>"><?= h($r['status']) ?></span>
                </p>
                <p style="margin:0 0 10px;"><?= nl2br(h($r['content'])) ?></p>
                <form method="post">
                    <input type="hidden" name="feedback_report_id" value="<?= (int)$r['id'] ?>">
                    <div class="two-col">
                        <div class="field">
                            <label>Feedback</label>
                            <textarea name="supervisor_feedback" rows="2"><?= h($r['supervisor_feedback']) ?></textarea>
                        </div>
                        <div class="field">
                            <label>Score (0-100)</label>
                            <input type="number" name="score" min="0" max="100" value="<?= h((string)$r['score']) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-small">Save Feedback</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
