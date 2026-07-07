<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Store a logged-in user's identity in the session. */
function login_user(array $user): void {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

/** Destroy the current session. */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain']);
    }
    session_destroy();
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/** Save a one-time flash message to show on the next page load. */
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Redirect to login if nobody is logged in. Call BEFORE any HTML output. */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/** Redirect away if the logged-in user does not have the given role. */
function require_role(string $role): void {
    require_login();
    if (current_role() !== $role) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/** Where a logged-in user's dashboard lives, based on their role. */
function dashboard_url_for_role(string $role): string {
    switch ($role) {
        case 'student':    return BASE_URL . '/student/dashboard.php';
        case 'supervisor': return BASE_URL . '/supervisor/dashboard.php';
        case 'admin':      return BASE_URL . '/admin/dashboard.php';
        default:           return BASE_URL . '/index.php';
    }
}
