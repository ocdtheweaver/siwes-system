<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$student = get_student_by_user($pdo, current_user_id());
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_company_id'])) {
    $companyId = (int) $_POST['apply_company_id'];

    if (in_array($student['status'], ['placed', 'completed'], true)) {
        flash('error', 'You already have an active placement, so you cannot apply elsewhere.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM placements WHERE student_id = ? AND company_id = ?");
        $stmt->execute([$student['id'], $companyId]);
        if ($stmt->fetch()) {
            flash('error', 'You have already applied to this company.');
        } else {
            $companyStmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $companyStmt->execute([$companyId]);
            $company = $companyStmt->fetch();

            $score = compute_match_score($student['skills'], $student['department'], $company['skills_required'], $company['industry']);

            $pdo->prepare("INSERT INTO placements (student_id, company_id, match_score, status) VALUES (?, ?, ?, 'pending')")
                ->execute([$student['id'], $companyId, $score]);

            if ($student['status'] === 'unplaced') {
                $pdo->prepare("UPDATE students SET status = 'pending' WHERE id = ?")->execute([$student['id']]);
                $student['status'] = 'pending';
            }

            flash('success', 'Application submitted to ' . $company['company_name'] . '.');
        }
    }
    header('Location: ' . BASE_URL . '/student/companies.php');
    exit;
}

// All companies with at least one slot, plus whether the student already applied
$stmt = $pdo->prepare("
    SELECT c.*,
           (SELECT pl.status FROM placements pl WHERE pl.student_id = ? AND pl.company_id = c.id) AS my_status
    FROM companies c
    ORDER BY c.company_name
");
$stmt->execute([$student['id']]);
$companies = $stmt->fetchAll();

// Compute match scores in PHP and sort best-first
foreach ($companies as &$c) {
    $c['match_score'] = compute_match_score($student['skills'], $student['department'], $c['skills_required'], $c['industry']);
}
unset($c);
usort($companies, fn($a, $b) => $b['match_score'] - $a['match_score']);

$page_title = 'Find a Placement';
$active_nav = 'companies';
require __DIR__ . '/../includes/header.php';
?>

<p class="muted" style="margin-top:-6px;">
    Companies are ranked by how well they match your declared skills and department.
    The score is calculated from overlapping skill keywords (80%) and department/industry relevance (20%).
</p>

<?php if (empty($companies)): ?>
    <div class="card empty-state">No companies have registered yet. Check back soon.</div>
<?php endif; ?>

<?php foreach ($companies as $c): ?>
    <div class="card">
        <div class="two-col">
            <div>
                <h2 style="margin-bottom:4px;"><?= h($c['company_name']) ?></h2>
                <p class="muted" style="margin:0 0 8px;"><?= h($c['industry']) ?> &middot; <?= h($c['location']) ?></p>
                <p style="margin:0 0 8px;"><?= h($c['description']) ?></p>
                <p class="muted" style="margin:0;">Skills sought: <?= h($c['skills_required']) ?></p>
                <p class="muted" style="margin:4px 0 0;">Slots available: <?= (int)$c['slots_available'] ?></p>
            </div>
            <div>
                <div class="score-wrap" style="margin-bottom:14px;">
                    <div class="score-bar <?= match_score_class($c['match_score']) ?>"><span style="width:<?= (int)$c['match_score'] ?>%;"></span></div>
                    <div class="score-label"><?= (int)$c['match_score'] ?>%</div>
                </div>
                <?php if ($c['my_status']): ?>
                    <span class="badge <?= status_badge_class($c['my_status']) ?>"><?= h($c['my_status']) ?></span>
                <?php elseif (in_array($student['status'], ['placed', 'completed'], true)): ?>
                    <span class="muted">Not available - already placed</span>
                <?php elseif ((int)$c['slots_available'] <= 0): ?>
                    <span class="muted">No slots available</span>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="apply_company_id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="btn btn-small">Apply</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
