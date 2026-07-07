<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('supervisor');

$company = get_company_by_user($pdo, current_user_id());

$pendingCount = $pdo->prepare("SELECT COUNT(*) FROM placements WHERE company_id = ? AND status = 'pending'");
$pendingCount->execute([$company['id']]);
$pendingCount = $pendingCount->fetchColumn();

$activeCount = $pdo->prepare("SELECT COUNT(*) FROM placements WHERE company_id = ? AND status = 'accepted'");
$activeCount->execute([$company['id']]);
$activeCount = $activeCount->fetchColumn();

// Recommended students: unplaced/pending students, ranked by match score against this company
$stmt = $pdo->query("SELECT * FROM students WHERE status IN ('unplaced', 'pending')");
$candidates = $stmt->fetchAll();
foreach ($candidates as &$s) {
    $s['match_score'] = compute_match_score($s['skills'], $s['department'], $company['skills_required'], $company['industry']);
}
unset($s);
usort($candidates, fn($a, $b) => $b['match_score'] - $a['match_score']);
$candidates = array_slice($candidates, 0, 5);

$page_title = 'Supervisor Dashboard';
$active_nav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="card-grid" style="margin-bottom:22px;">
    <div class="stat-box">
        <div class="stat-number"><?= h($company['company_name']) ?></div>
        <div class="stat-label">Company</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$company['slots_available'] ?></div>
        <div class="stat-label">Slots Available</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$pendingCount ?></div>
        <div class="stat-label">Pending Applicants</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= (int)$activeCount ?></div>
        <div class="stat-label">Students Currently Placed</div>
    </div>
</div>

<div class="card">
    <h2>Recommended Students for You</h2>
    <p class="muted">Ranked by skill and department fit against your company profile.</p>
    <?php if (empty($candidates)): ?>
        <p class="muted">No unplaced students at the moment.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Department</th><th>Skills</th><th>Match</th></tr></thead>
            <tbody>
            <?php foreach ($candidates as $s): ?>
                <tr>
                    <td><?= h($s['matric_no']) ?></td>
                    <td><?= h($s['department']) ?> (<?= h($s['level']) ?>)</td>
                    <td><?= h($s['skills']) ?></td>
                    <td>
                        <div class="score-wrap">
                            <div class="score-bar <?= match_score_class($s['match_score']) ?>"><span style="width:<?= (int)$s['match_score'] ?>%;"></span></div>
                            <div class="score-label"><?= (int)$s['match_score'] ?>%</div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="muted" style="margin-top:10px;">Students apply directly; recommendations here are informational and help you anticipate good matches before they apply.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
