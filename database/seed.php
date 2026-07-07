<?php
/**
 * ONE-TIME SEED SCRIPT
 * --------------------
 * Run this once in your browser (e.g. http://localhost/siwes-system/database/seed.php)
 * AFTER importing schema.sql, to create a working admin account and a few
 * demo companies/students so you have something to log in with immediately.
 *
 * Delete this file (or rename it) once you have seeded your database -
 * leaving it on a live/public server would let anyone re-run it.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function upsert_user($pdo, $role, $name, $email, $password) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    if ($existing) {
        return $existing['id'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (role, full_name, email, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->execute([$role, $name, $email, $hash]);
    return $pdo->lastInsertId();
}

$created = [];

// 1. Admin account
$adminId = upsert_user($pdo, 'admin', 'System Administrator', 'admin@aust.edu.ng', 'Admin@123');
$created[] = "Admin login -> email: admin@aust.edu.ng | password: Admin@123";

// 2. Demo companies (supervisors)
$brightId = upsert_user($pdo, 'supervisor', 'HR Manager - BrightTech', 'hr@brighttech.test', 'Company@123');
$stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->execute([$brightId]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO companies (user_id, company_name, industry, location, description, skills_required, slots_available) VALUES (?,?,?,?,?,?,?)")
        ->execute([$brightId, 'BrightTech Solutions', 'Software Development', 'Abuja, Nigeria',
            'A software house building web and mobile applications for SMEs.', 'php, javascript, mysql, html, css', 3]);
}
$created[] = "Supervisor login -> email: hr@brighttech.test | password: Company@123 (BrightTech Solutions)";

$waveId = upsert_user($pdo, 'supervisor', 'HR Manager - DataWave Labs', 'hr@datawave.test', 'Company@123');
$stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->execute([$waveId]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO companies (user_id, company_name, industry, location, description, skills_required, slots_available) VALUES (?,?,?,?,?,?,?)")
        ->execute([$waveId, 'DataWave Labs', 'Data and Analytics', 'Lagos, Nigeria',
            'A data analytics consultancy working with Python and SQL.', 'python, sql, data analysis, excel', 2]);
}
$created[] = "Supervisor login -> email: hr@datawave.test | password: Company@123 (DataWave Labs)";

// 3. A demo student
$studId = upsert_user($pdo, 'student', 'Demo Student', 'student@aust.edu.ng', 'Student@123');
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$studId]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO students (user_id, matric_no, department, level, phone, skills, bio) VALUES (?,?,?,?,?,?,?)")
        ->execute([$studId, 'AUST/CS/2022/001', 'Computer Science', '400', '08000000000',
            'php, html, css, mysql', 'Final-year Computer Science student interested in web development.']);
}
$created[] = "Student login -> email: student@aust.edu.ng | password: Student@123";

echo "<h1>Seed complete</h1><ul>";
foreach ($created as $line) {
    echo "<li>" . htmlspecialchars($line) . "</li>";
}
echo "</ul><p>You can now go to <a href='" . BASE_URL . "/auth/login.php'>the login page</a>.</p>";
echo "<p><strong>Please delete or rename this file (seed.php) now that you have run it.</strong></p>";
