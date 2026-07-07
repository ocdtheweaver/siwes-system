<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url_for_role(current_role()));
    exit;
}

$errors = [];
$old = ['role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $role     = $_POST['role'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!in_array($role, ['student', 'supervisor'], true)) {
        $errors[] = 'Please choose whether you are registering as a student or a supervisor/company.';
    }
    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if ($role === 'student') {
        $matricNo   = trim($_POST['matric_no'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $level      = trim($_POST['level'] ?? '');
        if ($matricNo === '')   $errors[] = 'Matriculation number is required.';
        if ($department === '') $errors[] = 'Department is required.';
        if ($level === '')      $errors[] = 'Level is required.';
    } elseif ($role === 'supervisor') {
        $companyName = trim($_POST['company_name'] ?? '');
        $industry    = trim($_POST['industry'] ?? '');
        if ($companyName === '') $errors[] = 'Company name is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Please log in instead.';
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role, full_name, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$role, $fullName, $email, $hash]);
            $userId = $pdo->lastInsertId();

            if ($role === 'student') {
                $pdo->prepare("INSERT INTO students (user_id, matric_no, department, level, phone, skills, bio) VALUES (?,?,?,?,?,?,?)")
                    ->execute([
                        $userId, $matricNo, $department, $level,
                        trim($_POST['phone'] ?? ''),
                        trim($_POST['skills'] ?? ''),
                        trim($_POST['bio'] ?? ''),
                    ]);
            } else {
                $pdo->prepare("INSERT INTO companies (user_id, company_name, industry, location, description, skills_required, slots_available) VALUES (?,?,?,?,?,?,?)")
                    ->execute([
                        $userId, $companyName, $industry,
                        trim($_POST['location'] ?? ''),
                        trim($_POST['description'] ?? ''),
                        trim($_POST['skills_required'] ?? ''),
                        max(0, (int)($_POST['slots_available'] ?? 0)),
                    ]);
            }

            $pdo->commit();

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            login_user($stmt->fetch());

            flash('success', 'Welcome to the SIWES Portal! Your account has been created.');
            header('Location: ' . dashboard_url_for_role($role));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}

$page_title = 'Create an Account';
require __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <?php if ($errors): ?>
        <div class="flash flash-error">
            <?php foreach ($errors as $err): ?><div><?= h($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" id="register-form">
        <div class="field">
            <label>I am registering as</label>
            <div class="radio-row">
                <label><input type="radio" name="role" value="student" onchange="toggleRoleFields()"
                    <?= ($old['role'] ?? 'student') === 'student' ? 'checked' : '' ?>> Student</label>
                <label><input type="radio" name="role" value="supervisor" onchange="toggleRoleFields()"
                    <?= ($old['role'] ?? '') === 'supervisor' ? 'checked' : '' ?>> Supervisor / Company</label>
            </div>
        </div>

        <div class="field">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= h($old['full_name'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= h($old['email'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>

        <div id="student-fields">
            <hr class="divider">
            <div class="field">
                <label for="matric_no">Matriculation Number</label>
                <input type="text" id="matric_no" name="matric_no" value="<?= h($old['matric_no'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?= h($old['department'] ?? '') ?>" placeholder="e.g. Computer Science">
            </div>
            <div class="field">
                <label for="level">Level</label>
                <input type="text" id="level" name="level" value="<?= h($old['level'] ?? '') ?>" placeholder="e.g. 400">
            </div>
            <div class="field">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= h($old['phone'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="skills">Skills</label>
                <input type="text" id="skills" name="skills" value="<?= h($old['skills'] ?? '') ?>" placeholder="e.g. php, mysql, html, css">
                <div class="hint">Comma-separated keywords. Used to match you to suitable companies.</div>
            </div>
            <div class="field">
                <label for="bio">Short Bio (optional)</label>
                <textarea id="bio" name="bio"><?= h($old['bio'] ?? '') ?></textarea>
            </div>
        </div>

        <div id="supervisor-fields">
            <hr class="divider">
            <div class="field">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="<?= h($old['company_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="industry">Industry</label>
                <input type="text" id="industry" name="industry" value="<?= h($old['industry'] ?? '') ?>" placeholder="e.g. Software Development">
            </div>
            <div class="field">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?= h($old['location'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="skills_required">Skills Required</label>
                <input type="text" id="skills_required" name="skills_required" value="<?= h($old['skills_required'] ?? '') ?>" placeholder="e.g. php, mysql, html, css">
                <div class="hint">Comma-separated keywords. Used to match suitable students to you.</div>
            </div>
            <div class="field">
                <label for="slots_available">Placement Slots Available</label>
                <input type="number" id="slots_available" name="slots_available" min="0" value="<?= h($old['slots_available'] ?? '0') ?>">
            </div>
            <div class="field">
                <label for="description">Company Description</label>
                <textarea id="description" name="description"><?= h($old['description'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>
    <p class="muted" style="margin-top:14px;">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a>.</p>
</div>

<script>
function toggleRoleFields() {
    const role = document.querySelector('input[name="role"]:checked').value;
    document.getElementById('student-fields').style.display = (role === 'student') ? 'block' : 'none';
    document.getElementById('supervisor-fields').style.display = (role === 'supervisor') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleRoleFields);
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
