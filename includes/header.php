<?php
/**
 * Shared header. Every page sets $page_title (and optionally $active_nav)
 * then does: require __DIR__ . '/../includes/header.php';
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($page_title) ? h($page_title) . ' - SIWES Portal' : 'SIWES Portal' ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">SIWES&nbsp;Portal</a>
    <nav class="top-nav">
        <?php if (is_logged_in()): ?>
            <span class="user-chip"><?= h($_SESSION['full_name']) ?> &middot; <?= h(ucfirst(current_role())) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php">Log out</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">Log in</a>
            <a class="btn-link" href="<?= BASE_URL ?>/auth/register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="layout">
<?php if (is_logged_in()): ?>
    <aside class="sidebar">
        <?php
        $links = [];
        if (current_role() === 'student') {
            $links = [
                'dashboard'      => ['Dashboard', '/student/dashboard.php'],
                'profile'        => ['My Profile', '/student/profile.php'],
                'companies'      => ['Find a Placement', '/student/companies.php'],
                'logbook'        => ['Logbook', '/student/logbook.php'],
                'reports'        => ['Reports', '/student/reports.php'],
            ];
        } elseif (current_role() === 'supervisor') {
            $links = [
                'dashboard'  => ['Dashboard', '/supervisor/dashboard.php'],
                'profile'    => ['Company Profile', '/supervisor/profile.php'],
                'applicants' => ['Applicants', '/supervisor/applicants.php'],
            ];
        } elseif (current_role() === 'admin') {
            $links = [
                'dashboard'  => ['Dashboard', '/admin/dashboard.php'],
                'students'   => ['Students', '/admin/students.php'],
                'companies'  => ['Companies', '/admin/companies.php'],
                'placements' => ['Placements', '/admin/placements.php'],
                'reports'    => ['All Reports', '/admin/reports.php'],
            ];
        }
        foreach ($links as $key => [$label, $url]):
            $isActive = (isset($active_nav) && $active_nav === $key);
        ?>
            <a class="<?= $isActive ? 'active' : '' ?>" href="<?= BASE_URL . $url ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </aside>
<?php endif; ?>

    <main class="content">
        <?php if (isset($page_title)): ?><h1 class="page-title"><?= h($page_title) ?></h1><?php endif; ?>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="flash flash-<?= h($_SESSION['flash']['type']) ?>">
                <?= h($_SESSION['flash']['message']) ?>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
