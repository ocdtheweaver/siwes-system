<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = (int) ($_POST['company_id'] ?? 0);
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM companies WHERE id = ?")->execute([$companyId]);
        flash('success', 'Company record deleted.');
    } elseif (isset($_POST['update_slots'])) {
        $slots = max(0, (int) ($_POST['slots_available'] ?? 0));
        $pdo->prepare("UPDATE companies SET slots_available = ? WHERE id = ?")->execute([$slots, $companyId]);
        flash('success', 'Slot count updated.');
    }
    header('Location: ' . BASE_URL . '/admin/companies.php');
    exit;
}

$stmt = $pdo->query("
    SELECT c.*, u.full_name, u.email,
        (SELECT COUNT(*) FROM placements pl WHERE pl.company_id = c.id AND pl.status IN ('accepted','completed')) AS active_students
    FROM companies c JOIN users u ON u.id = c.user_id
    ORDER BY c.company_name
");
$companies = $stmt->fetchAll();

$page_title = 'Manage Companies';
$active_nav = 'companies';
require __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <?php if (empty($companies)): ?>
        <p class="muted">No companies registered yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Company</th><th>Industry</th><th>Location</th><th>Slots</th><th>Students</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($companies as $c): ?>
                <tr>
                    <td><?= h($c['company_name']) ?><br><span class="muted"><?= h($c['email']) ?></span></td>
                    <td><?= h($c['industry']) ?></td>
                    <td><?= h($c['location']) ?></td>
                    <td>
                        <form method="post" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="company_id" value="<?= (int)$c['id'] ?>">
                            <input type="number" name="slots_available" min="0" value="<?= (int)$c['slots_available'] ?>" style="width:70px; padding:4px 8px;">
                            <button type="submit" name="update_slots" value="1" class="btn btn-small btn-secondary">Update</button>
                        </form>
                    </td>
                    <td><?= (int)$c['active_students'] ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this company record? This cannot be undone.');">
                            <input type="hidden" name="company_id" value="<?= (int)$c['id'] ?>">
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
