<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('supervisor');

$company = get_company_by_user($pdo, current_user_id());
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $industry    = trim($_POST['industry'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $skills      = trim($_POST['skills_required'] ?? '');
    $slots       = max(0, (int)($_POST['slots_available'] ?? 0));

    if ($companyName === '') $errors[] = 'Company name is required.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE companies SET company_name=?, industry=?, location=?, description=?, skills_required=?, slots_available=? WHERE id=?")
            ->execute([$companyName, $industry, $location, $description, $skills, $slots, $company['id']]);
        flash('success', 'Company profile updated.');
        header('Location: ' . BASE_URL . '/supervisor/profile.php');
        exit;
    }
    $company = array_merge($company, compact('companyName', 'industry', 'location', 'description', 'skills', 'slots'));
}

$page_title = 'Company Profile';
$active_nav = 'profile';
require __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <?php if ($errors): ?>
        <div class="flash flash-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="field">
            <label for="company_name">Company Name</label>
            <input type="text" id="company_name" name="company_name" value="<?= h($company['company_name']) ?>" required>
        </div>
        <div class="field">
            <label for="industry">Industry</label>
            <input type="text" id="industry" name="industry" value="<?= h($company['industry']) ?>">
        </div>
        <div class="field">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= h($company['location']) ?>">
        </div>
        <div class="field">
            <label for="skills_required">Skills Required</label>
            <input type="text" id="skills_required" name="skills_required" value="<?= h($company['skills_required']) ?>" placeholder="e.g. php, mysql, html, css">
            <div class="hint">Comma-separated keywords. Drives the match score shown to students.</div>
        </div>
        <div class="field">
            <label for="slots_available">Placement Slots Available</label>
            <input type="number" id="slots_available" name="slots_available" min="0" value="<?= h($company['slots_available']) ?>">
        </div>
        <div class="field">
            <label for="description">Company Description</label>
            <textarea id="description" name="description"><?= h($company['description']) ?></textarea>
        </div>
        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
