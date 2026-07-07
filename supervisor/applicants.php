<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('supervisor');

$company = get_company_by_user($pdo, current_user_id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placementId = (int) ($_POST['placement_id'] ?? 0);
    $action      = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM placements WHERE id = ? AND company_id = ?");
    $stmt->execute([$placementId, $company['id']]);
    $placement = $stmt->fetch();

    if ($placement && $placement['status'] === 'pending') {
        if ($action === 'accept') {
            if ((int)$company['slots_available'] <= 0) {
                flash('error', 'You have no slots available. Increase your slot count in your profile before accepting more students.');
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE placements SET status='accepted', decided_at=NOW() WHERE id=?")->execute([$placementId]);
                $pdo->prepare("UPDATE students SET status='placed' WHERE id=?")->execute([$placement['student_id']]);
                $pdo->prepare("UPDATE companies SET slots_available = slots_available - 1 WHERE id=?")->execute([$company['id']]);
                $pdo->commit();
                flash('success', 'Applicant accepted.');
            }
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE placements SET status='rejected', decided_at=NOW() WHERE id=?")->execute([$placementId]);

            // If the student has no other pending/accepted application, return them to "unplaced"
            $check = $pdo->prepare("SELECT COUNT(*) FROM placements WHERE student_id = ? AND status IN ('pending','accepted')");
            $check->execute([$placement['student_id']]);
            if ((int)$check->fetchColumn() === 0) {
                $pdo->prepare("UPDATE students SET status='unplaced' WHERE id=?")->execute([$placement['student_id']]);
            }
            flash('success', 'Applicant rejected.');
        }
    }
    header('Location: ' . BASE_URL . '/supervisor/applicants.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT pl.*, s.matric_no, s.department, s.level, s.skills, u.full_name, u.email
    FROM placements pl
    JOIN students s ON s.id = pl.student_id
    JOIN users u ON u.id = s.user_id
    WHERE pl.company_id = ? AND pl.status = 'pending'
    ORDER BY pl.match_score DESC
");
$stmt->execute([$company['id']]);
$pending = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT pl.*, s.matric_no, s.department, s.level, u.full_name
    FROM placements pl
    JOIN students s ON s.id = pl.student_id
    JOIN users u ON u.id = s.user_id
    WHERE pl.company_id = ? AND pl.status IN ('accepted', 'completed')
    ORDER BY pl.decided_at DESC
");
$stmt->execute([$company['id']]);
$active = $stmt->fetchAll();

$page_title = 'Applicants';
$active_nav = 'applicants';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <h2>Pending Applications</h2>
    <?php if (empty($pending)): ?>
        <p class="muted">No pending applications right now.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Department</th><th>Skills</th><th>Match</th><th>Applied</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $p): ?>
                <tr>
                    <td><?= h($p['full_name']) ?><br><span class="muted"><?= h($p['matric_no']) ?></span></td>
                    <td><?= h($p['department']) ?> (<?= h($p['level']) ?>)</td>
                    <td><?= h($p['skills']) ?></td>
                    <td>
                        <div class="score-wrap">
                            <div class="score-bar <?= match_score_class($p['match_score']) ?>"><span style="width:<?= (int)$p['match_score'] ?>%;"></span></div>
                            <div class="score-label"><?= (int)$p['match_score'] ?>%</div>
                        </div>
                    </td>
                    <td><?= h(date('d M Y', strtotime($p['applied_at']))) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="placement_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-small">Accept</button>
                            <button type="submit" name="action" value="reject" class="btn btn-small btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Your Students</h2>
    <?php if (empty($active)): ?>
        <p class="muted">No students placed with you yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Department</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($active as $a): ?>
                <tr>
                    <td><?= h($a['full_name']) ?><br><span class="muted"><?= h($a['matric_no']) ?></span></td>
                    <td><?= h($a['department']) ?> (<?= h($a['level']) ?>)</td>
                    <td><span class="badge <?= status_badge_class($a['status']) ?>"><?= h($a['status']) ?></span></td>
                    <td><a class="btn btn-small btn-secondary" href="<?= BASE_URL ?>/supervisor/view_student.php?placement_id=<?= (int)$a['id'] ?>">View Logbook &amp; Reports</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
