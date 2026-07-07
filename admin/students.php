<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$studentId]);
        flash('success', 'Student record deleted.');
    } elseif (isset($_POST['update_status'])) {
        $status = $_POST['status'] ?? 'unplaced';
        if (in_array($status, ['unplaced', 'pending', 'placed', 'completed'], true)) {
            $pdo->prepare("UPDATE students SET status = ? WHERE id = ?")->execute([$status, $studentId]);
            flash('success', 'Student status updated.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/students.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name, u.email
        FROM students s JOIN users u ON u.id = s.user_id
        WHERE s.matric_no LIKE ? OR u.full_name LIKE ? OR s.department LIKE ?
        ORDER BY u.full_name
    ");
    $term = "%$search%";
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt = $pdo->query("
        SELECT s.*, u.full_name, u.email
        FROM students s JOIN users u ON u.id = s.user_id
        ORDER BY u.full_name
    ");
}
$students = $stmt->fetchAll();

$page_title = 'Manage Students';
$active_nav = 'students';
require __DIR__ . '/../includes/header.php';
?>

<form method="get" class="card" style="display:flex; gap:10px; align-items:center;">
    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name, matric no, or department" style="max-width:320px;">
    <button type="submit" class="btn btn-small">Search</button>
    <?php if ($search !== ''): ?><a href="<?= BASE_URL ?>/admin/students.php" class="muted">Clear</a><?php endif; ?>
</form>

<div class="card">
    <?php if (empty($students)): ?>
        <p class="muted">No students found.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Matric No.</th><th>Department</th><th>Level</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= h($s['full_name']) ?><br><span class="muted"><?= h($s['email']) ?></span></td>
                    <td><?= h($s['matric_no']) ?></td>
                    <td><?= h($s['department']) ?></td>
                    <td><?= h($s['level']) ?></td>
                    <td>
                        <form method="post" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
                            <select name="status" style="padding:4px 8px;">
                                <?php foreach (['unplaced','pending','placed','completed'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" value="1" class="btn btn-small btn-secondary">Update</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this student record? This cannot be undone.');">
                            <input type="hidden" name="student_id" value="<?= (int)$s['id'] ?>">
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
