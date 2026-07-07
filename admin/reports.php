<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$stmt = $pdo->query("
    SELECT r.*, s.matric_no, su.full_name AS student_name, c.company_name
    FROM reports r
    JOIN placements pl ON pl.id = r.placement_id
    JOIN students s ON s.id = pl.student_id
    JOIN users su ON su.id = s.user_id
    JOIN companies c ON c.id = pl.company_id
    ORDER BY r.submitted_at DESC
");
$reports = $stmt->fetchAll();

$page_title = 'All Reports';
$active_nav = 'reports';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <?php if (empty($reports)): ?>
        <p class="muted">No reports submitted yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Company</th><th>Type</th><th>Period</th><th>Status</th><th>Score</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= h($r['student_name']) ?><br><span class="muted"><?= h($r['matric_no']) ?></span></td>
                    <td><?= h($r['company_name']) ?></td>
                    <td><?= h(ucfirst($r['report_type'])) ?></td>
                    <td><?= h($r['period_label']) ?></td>
                    <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= h($r['status']) ?></span></td>
                    <td><?= $r['score'] !== null ? (int)$r['score'] . '/100' : '<span class="muted">-</span>' ?></td>
                    <td><?= h(date('d M Y', strtotime($r['submitted_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
