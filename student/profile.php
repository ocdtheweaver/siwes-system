<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('student');

$student = get_student_by_user($pdo, current_user_id());
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = trim($_POST['department'] ?? '');
    $level      = trim($_POST['level'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $skills     = trim($_POST['skills'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');

    if ($department === '') $errors[] = 'Department is required.';
    if ($level === '')      $errors[] = 'Level is required.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE students SET department=?, level=?, phone=?, skills=?, bio=? WHERE id=?")
            ->execute([$department, $level, $phone, $skills, $bio, $student['id']]);
        flash('success', 'Profile updated.');
        header('Location: ' . BASE_URL . '/student/profile.php');
        exit;
    }
    $student = array_merge($student, compact('department', 'level', 'phone', 'skills', 'bio'));
}

$page_title = 'My Profile';
$active_nav = 'profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <?php if ($errors): ?>
        <div class="flash flash-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label>Matriculation Number</label>
            <input type="text" value="<?= h($student['matric_no']) ?>" disabled>
            <div class="hint">Matric number cannot be changed. Contact admin if this is incorrect.</div>
        </div>
        <div class="field">
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?= h($student['department']) ?>" required>
        </div>
        <div class="field">
            <label for="level">Level</label>
            <input type="text" id="level" name="level" value="<?= h($student['level']) ?>" required>
        </div>
        <div class="field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?= h($student['phone']) ?>">
        </div>
        <div class="field">
            <label for="skills">Skills</label>
            <input type="text" id="skills" name="skills" value="<?= h($student['skills']) ?>" placeholder="e.g. php, mysql, html, css">
            <div class="hint">Comma-separated keywords. This drives your match score with companies.</div>
        </div>
        <div class="field">
            <label for="bio">Short Bio</label>
            <textarea id="bio" name="bio"><?= h($student['bio']) ?></textarea>
        </div>
        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
