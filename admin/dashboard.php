<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$totalStudents  = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$placedCount    = $pdo->query("SELECT COUNT(*) FROM students WHERE status IN ('placed','completed')")->fetchColumn();
$unplacedCount  = $pdo->query("SELECT COUNT(*) FROM students WHERE status IN ('unplaced','pending')")->fetchColumn();

$pendingPlacements   = $pdo->query("SELECT COUNT(*) FROM placements WHERE status='pending'")->fetchColumn();
$acceptedPlacements  = $pdo->query("SELECT COUNT(*) FROM placements WHERE status='accepted'")->fetchColumn();
$completedPlacements = $pdo->query("SELECT COUNT(*) FROM placements WHERE status='completed'")->fetchColumn();
$rejectedPlacements  = $pdo->query("SELECT COUNT(*) FROM placements WHERE status='rejected'")->fetchColumn();

$totalLogbookEntries = $pdo->query("SELECT COUNT(*) FROM logbook_entries")->fetchColumn();
$totalReports        = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$pendingReports       = $pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn();

$page_title = 'Admin Dashboard';
$active_nav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="card-grid" style="margin-bottom:22px;">
    <div class="stat-box"><div class="stat-number"><?= (int)$totalStudents ?></div><div class="stat-label">Total Students</div></div>
    <div class="stat-box"><div class="stat-number"><?= (int)$totalCompanies ?></div><div class="stat-label">Registered Companies</div></div>
    <div class="stat-box"><div class="stat-number"><?= (int)$placedCount ?></div><div class="stat-label">Students Placed</div></div>
    <div class="stat-box"><div class="stat-number"><?= (int)$unplacedCount ?></div><div class="stat-label">Students Unplaced</div></div>
</div>

<div class="two-col">
    <div class="card">
        <h2>Placements by Status</h2>
        <div class="table-wrap">
        <table>
            <tbody>
                <tr><td>Pending</td><td><span class="badge badge-amber"><?= (int)$pendingPlacements ?></span></td></tr>
                <tr><td>Accepted</td><td><span class="badge badge-green"><?= (int)$acceptedPlacements ?></span></td></tr>
                <tr><td>Completed</td><td><span class="badge badge-green"><?= (int)$completedPlacements ?></span></td></tr>
                <tr><td>Rejected</td><td><span class="badge badge-red"><?= (int)$rejectedPlacements ?></span></td></tr>
            </tbody>
        </table>
        </div>
    </div>
    <div class="card">
        <h2>Reporting Activity</h2>
        <div class="table-wrap">
        <table>
            <tbody>
                <tr><td>Logbook Entries Submitted</td><td><?= (int)$totalLogbookEntries ?></td></tr>
                <tr><td>Reports Submitted</td><td><?= (int)$totalReports ?></td></tr>
                <tr><td>Reports Awaiting Review</td><td><span class="badge badge-amber"><?= (int)$pendingReports ?></span></td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
