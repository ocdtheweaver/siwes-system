<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placementId = (int) ($_POST['placement_id'] ?? 0);
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM placements WHERE id = ?")->execute([$placementId]);
        flash('success', 'Placement record deleted.');
    } elseif (isset($_POST['update_status'])) {
        $status = $_POST['status'] ?? 'pending';
        if (in_array($status, ['pending', 'accepted', 'rejected', 'completed'], true)) {
            $pdo->prepare("UPDATE placements SET status = ?, decided_at = NOW() WHERE id = ?")->execute([$status, $placementId]);
            flash('success', 'Placement status updated.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/placements.php');
    exit;
}

$stmt = $pdo->query("
    SELECT pl.*, s.matric_no, su.full_name AS student_name, c.company_name
    FROM placements pl
    JOIN students s ON s.id = pl.student_id
    JOIN users su ON su.id = s.user_id
    JOIN companies c ON c.id = pl.company_id
    ORDER BY pl.applied_at DESC
");
$placements = $stmt->fetchAll();

$page_title = 'All Placements';
$active_nav = 'placements';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <?php if (empty($placements)): ?>
        <p class="muted">No placement applications yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Student</th><th>Company</th><th>Match</th><th>Applied</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($placements as $p): ?>
                <tr>
                    <td><?= h($p['student_name']) ?><br><span class="muted"><?= h($p['matric_no']) ?></span></td>
                    <td><?= h($p['company_name']) ?></td>
                    <td><?= (int)$p['match_score'] ?>%</td>
                    <td><?= h(date('d M Y', strtotime($p['applied_at']))) ?></td>
                    <td>
                        <form method="post" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="placement_id" value="<?= (int)$p['id'] ?>">
                            <select name="status" style="padding:4px 8px;">
                                <?php foreach (['pending','accepted','rejected','completed'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" value="1" class="btn btn-small btn-secondary">Update</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this placement record?');">
                            <input type="hidden" name="placement_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" name="delete" value="1" class="btn btn-small btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
