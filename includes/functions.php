<?php
/** Escape a string for safe HTML output. */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Fetch the student profile row that belongs to a given user_id. */
function get_student_by_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Fetch the company profile row that belongs to a given user_id. */
function get_company_by_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Split a comma-separated keyword string into a clean, lower-cased array. */
function split_keywords(?string $csv): array {
    if (!$csv) {
        return [];
    }
    $parts = array_map('trim', explode(',', $csv));
    $parts = array_map('strtolower', $parts);
    return array_values(array_filter($parts, fn($p) => $p !== ''));
}

/**
 * Rule-based recommendation engine.
 *
 * Compares a student's declared skills against a company's required skills
 * (80% of the score) and checks whether the student's department relates to
 * the company's industry (20% of the score). This keeps the matching logic
 * fully transparent and explainable, which is intentional: a black-box score
 * would be hard to justify to students and supervisors using the system.
 *
 * @return int Match score from 0 to 100.
 */
function compute_match_score(?string $studentSkills, ?string $studentDept, ?string $companySkills, ?string $companyIndustry): int {
    $studentSkillSet  = split_keywords($studentSkills);
    $companySkillSet  = split_keywords($companySkills);

    $skillScore = 0.0;
    if (count($companySkillSet) > 0) {
        $overlap    = array_intersect($studentSkillSet, $companySkillSet);
        $skillScore = (count($overlap) / count($companySkillSet)) * 80;
    }

    $deptScore = 0;
    if ($studentDept && $companyIndustry) {
        $dept     = strtolower($studentDept);
        $industry = strtolower($companyIndustry);
        if (str_contains($industry, $dept) || str_contains($dept, $industry)) {
            $deptScore = 20;
        }
    }

    return (int) round($skillScore + $deptScore);
}

/** Return a CSS class name for a match score, used to colour the progress bar. */
function match_score_class(int $score): string {
    if ($score >= 70) return 'score-high';
    if ($score >= 40) return 'score-mid';
    return 'score-low';
}

/** Return a CSS class name for a status badge. */
function status_badge_class(string $status): string {
    return match ($status) {
        'accepted', 'reviewed', 'placed', 'completed' => 'badge-green',
        'pending', 'submitted', 'unplaced'            => 'badge-amber',
        'rejected'                                     => 'badge-red',
        default                                         => 'badge-grey',
    };
}
