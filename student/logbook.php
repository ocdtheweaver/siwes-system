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
    $entryDate = $_POST['entry_date'] ?? '';
    $activity  = trim($_POST['activity_description'] ?? '');

    if ($entryDate === '') $errors[] = 'Date is required.';
    if ($activity === '')  $errors[] = 'Please describe what you did.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO logbook_entries (placement_id, entry_date, activity_description) VALUES (?, ?, ?)")
            ->execute([$placement['id'], $entryDate, $activity]);
        flash('success', 'Logbook entry added.');
        header('Location: ' . BASE_URL . '/student/logbook.php');
        exit;
    }
}

$entries = [];
if ($placement) {
    $stmt = $pdo->prepare("SELECT * FROM logbook_entries WHERE placement_id = ? ORDER BY entry_date DESC, id DESC");
    $stmt->execute([$placement['id']]);
    $entries = $stmt->fetchAll();
}

$page_title = 'Logbook';
$active_nav = 'logbook';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$placement): ?>
    <div class="card empty-state">
        You need an accepted placement before you can use the logbook.
        <br><a href="<?= BASE_URL ?>/student/companies.php">Find a placement</a>.
    </div>
<?php else: ?>
    <p class="muted" style="margin-top:-6px;">Placement: <strong><?= h($placement['company_name']) ?></strong></p>

    <div class="card">
        <h2>Add Entry</h2>
        <?php if ($errors): ?>
            <div class="flash flash-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="two-col">
                <div class="field">
                    <label for="entry_date">Date</label>
                    <input type="date" id="entry_date" name="entry_date" value="<?= h(date('Y-m-d')) ?>" required>
                </div>
                <div></div>
            </div>
            <div class="field">
                <label for="activity_description">What did you do today?</label>
                <textarea id="activity_description" name="activity_description" required></textarea>
            </div>
            <button type="submit" class="btn">Add Entry</button>
        </form>
    </div>

    <div class="card">
        <h2>Your Entries</h2>
        <?php if (empty($entries)): ?>
            <p class="muted">No entries yet.</p>
        <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Activity</th><th>Supervisor Comment</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= h($e['entry_date']) ?></td>
                        <td><?= nl2br(h($e['activity_description'])) ?></td>
                        <td><?= $e['supervisor_comment'] ? nl2br(h($e['supervisor_comment'])) : '<span class="muted">No comment yet</span>' ?></td>
                        <td><span class="badge <?= status_badge_class($e['status']) ?>"><?= h($e['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
