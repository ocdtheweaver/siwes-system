<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_url_for_role(current_role()));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        header('Location: ' . dashboard_url_for_role($user['role']));
        exit;
    }
    $error = 'Incorrect email or password.';
}

$page_title = 'Log In';
require __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:420px;">
    <?php if ($error): ?><div class="flash flash-error"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Log In</button>
    </form>
    <p class="muted" style="margin-top:14px;">No account yet? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a>.</p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
