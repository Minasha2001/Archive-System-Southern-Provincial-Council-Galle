<?php
/**
 * functions.php - ප්‍රධාන ශ්‍රිත ගොනුව
 * Archive System සඳහා සියලු reusable functions
 */

require_once __DIR__ . '/config.php';

// ============ DATABASE FUNCTIONS ============

/**
 * ගොනුවක් ලබා ගැනීම ID අනුව
 */
function get_file_by_id(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM archive_files WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * User ගේ ගොනු ලබා ගැනීම
 */
function get_files_by_user(PDO $pdo, int $user_id, string $status = null): array {
    $sql = "SELECT af.*, u.full_name as submitted_by_name 
            FROM archive_files af 
            LEFT JOIN users u ON af.submitted_by = u.id 
            WHERE af.submitted_by = ?";
    $params = [$user_id];
    
    if ($status) {
        $sql .= " AND af.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY af.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Department එක අනුව ගොනු ලබා ගැනීම
 */
function get_files_by_department(PDO $pdo, string $department, string $status = null): array {
    $sql = "SELECT af.*, u.full_name as submitted_by_name 
            FROM archive_files af 
            LEFT JOIN users u ON af.submitted_by = u.id 
            WHERE af.department = ?";
    $params = [$department];
    
    if ($status) {
        $sql .= " AND af.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY af.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * සියලු archived ගොනු ලබා ගැනීම
 */
function get_archived_files(PDO $pdo, string $department = null, string $search = null): array {
    $sql = "SELECT af.*, u.full_name as submitted_by_name, r.full_name as reviewed_by_name 
            FROM archive_files af 
            LEFT JOIN users u ON af.submitted_by = u.id 
            LEFT JOIN users r ON af.reviewed_by = r.id 
            WHERE af.status = 'archived'";
    $params = [];
    
    if ($department) {
        $sql .= " AND af.department = ?";
        $params[] = $department;
    }
    
    if ($search) {
        $sql .= " AND (af.file_name LIKE ? OR af.file_number LIKE ? OR af.archive_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $sql .= " ORDER BY af.archive_number ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Pending ගොනු ලබා ගැනීම (Subject Officer සඳහා)
 */
function get_pending_files(PDO $pdo, string $department = null): array {
    $sql = "SELECT af.*, u.full_name as submitted_by_name 
            FROM archive_files af 
            LEFT JOIN users u ON af.submitted_by = u.id 
            WHERE af.status = 'pending'";
    $params = [];
    
    if ($department) {
        $sql .= " AND af.department = ?";
        $params[] = $department;
    }
    
    $sql .= " ORDER BY af.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * User ලැයිස්තුව ලබා ගැනීම
 */
function get_users(PDO $pdo, string $role = null, string $status = 'active'): array {
    $sql = "SELECT * FROM users WHERE status = ?";
    $params = [$status];
    
    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * නව Archive Number එකක් ජනනය කිරීම
 */
function generate_archive_number(PDO $pdo, string $department): string {
    $prefix = '';
    switch ($department) {
        case 'ආයතන': $prefix = 'ARC-INST-'; break;
        case 'සභා': $prefix = 'ARC-COUN-'; break;
        case 'ගිණුම්': $prefix = 'ARC-ACCT-'; break;
        default: $prefix = 'ARC-';
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM archive_files WHERE department = ? AND status = 'archived'");
    $stmt->execute([$department]);
    $count = $stmt->fetch()['count'] + 1;
    
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ============ FILE MANAGEMENT FUNCTIONS ============

/**
 * නව ගොනුවක් ඉදිරිපත් කිරීම
 */
function submit_file(PDO $pdo, array $data): bool {
    $stmt = $pdo->prepare(
        "INSERT INTO archive_files 
        (submitted_by, submit_date, subject_number, department, file_number, file_name, 
         start_date, end_date, page_count, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    return $stmt->execute([
        $data['submitted_by'],
        $data['submit_date'],
        $data['subject_number'],
        $data['department'],
        $data['file_number'],
        $data['file_name'],
        $data['start_date'],
        $data['end_date'],
        $data['page_count']
    ]);
}

/**
 * ගොනුවක් archive කිරීම (Subject Officer විසින්)
 */
function archive_file(PDO $pdo, int $file_id, array $data): bool {
    $stmt = $pdo->prepare(
        "UPDATE archive_files 
        SET archive_number = ?, reviewed_by = ?, reviewed_at = NOW(), status = 'archived'
        WHERE id = ? AND status = 'pending'"
    );
    
    return $stmt->execute([
        $data['archive_number'],
        $data['reviewed_by'],
        $file_id
    ]);
}

/**
 * ගොනුවක් reject කිරීම
 */
function reject_file(PDO $pdo, int $file_id, string $reason, int $reviewed_by): bool {
    $stmt = $pdo->prepare(
        "UPDATE archive_files 
        SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ? AND status = 'pending'"
    );
    
    return $stmt->execute([$reviewed_by, $file_id]);
}

/**
 * ගොනුවක් update කිරීම
 */
function update_file(PDO $pdo, int $file_id, array $data): bool {
    $fields = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    
    $params[] = $file_id;
    $sql = "UPDATE archive_files SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($params);
}

/**
 * ගොනුවක් මකා දැමීම
 */
function delete_file(PDO $pdo, int $file_id): bool {
    $stmt = $pdo->prepare("DELETE FROM archive_files WHERE id = ?");
    return $stmt->execute([$file_id]);
}

// ============ USER MANAGEMENT FUNCTIONS ============

/**
 * User එකක් activate කිරීම
 */
function activate_user(PDO $pdo, int $user_id): bool {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND status = 'pending'");
    return $stmt->execute([$user_id]);
}

/**
 * User එකක් disable කිරීම
 */
function disable_user(PDO $pdo, int $user_id): bool {
    $stmt = $pdo->prepare("UPDATE users SET status = 'disabled' WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * User එකක් මකා දැමීම
 */
function delete_user(PDO $pdo, int $user_id): bool {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    return $stmt->execute([$user_id]);
}

// ============ STATISTICS FUNCTIONS ============

/**
 * සංඛ්‍යා ලේඛන ලබා ගැනීම
 */
function get_statistics(PDO $pdo): array {
    $stats = [];
    
    // Total files by status
    $stats['total_files'] = $pdo->query("SELECT COUNT(*) FROM archive_files")->fetchColumn();
    $stats['pending_files'] = $pdo->query("SELECT COUNT(*) FROM archive_files WHERE status = 'pending'")->fetchColumn();
    $stats['archived_files'] = $pdo->query("SELECT COUNT(*) FROM archive_files WHERE status = 'archived'")->fetchColumn();
    $stats['rejected_files'] = $pdo->query("SELECT COUNT(*) FROM archive_files WHERE status = 'rejected'")->fetchColumn();
    
    // Files by department
    $stats['by_department'] = [
        'ආයතන' => $pdo->query("SELECT COUNT(*) FROM archive_files WHERE department = 'ආයතන'")->fetchColumn(),
        'සභා' => $pdo->query("SELECT COUNT(*) FROM archive_files WHERE department = 'සභා'")->fetchColumn(),
        'ගිණුම්' => $pdo->query("SELECT COUNT(*) FROM archive_files WHERE department = 'ගිණුම්'")->fetchColumn()
    ];
    
    // Total users by role
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $stats['users_by_role'] = [
        'user' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'")->fetchColumn(),
        'subject' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'subject' AND status = 'active'")->fetchColumn(),
        'admin' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn()
    ];
    
    // Pending users
    $stats['pending_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    
    return $stats;
}

/**
 * Department එකක් සඳහා සංඛ්‍යා ලේඛන
 */
function get_department_statistics(PDO $pdo, string $department): array {
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE department = ?");
    $stmt->execute([$department]);
    $stats['total_files'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE department = ? AND status = 'archived'");
    $stmt->execute([$department]);
    $stats['archived_files'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE department = ? AND status = 'pending'");
    $stmt->execute([$department]);
    $stats['pending_files'] = $stmt->fetchColumn();
    
    return $stats;
}

// ============ VALIDATION FUNCTIONS ============

/**
 * File number unique දැයි පරීක්ෂා කිරීම
 */
function is_file_number_unique(PDO $pdo, string $file_number, int $exclude_id = null): bool {
    $sql = "SELECT COUNT(*) FROM archive_files WHERE file_number = ?";
    $params = [$file_number];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

/**
 * Archive number unique දැයි පරීක්ෂා කිරීම
 */
function is_archive_number_unique(PDO $pdo, string $archive_number, int $exclude_id = null): bool {
    $sql = "SELECT COUNT(*) FROM archive_files WHERE archive_number = ?";
    $params = [$archive_number];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

/**
 * Username unique දැයි පරීක්ෂා කිරීම
 */
function is_username_unique(PDO $pdo, string $username, int $exclude_id = null): bool {
    $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
    $params = [$username];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

/**
 * Email unique දැයි පරීක්ෂා කිරීම
 */
function is_email_unique(PDO $pdo, string $email, int $exclude_id = null): bool {
    $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
    $params = [$email];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

// ============ UTILITY FUNCTIONS ============

/**
 * Sinhala date format එක
 */
function format_sinhala_date(string $date): string {
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    
    $months = [
        1 => 'ජනවාරි', 2 => 'පෙබරවාරි', 3 => 'මාර්තු', 4 => 'අප්‍රේල්',
        5 => 'මැයි', 6 => 'ජූනි', 7 => 'ජූලි', 8 => 'අගෝස්තු',
        9 => 'සැප්තැම්බර්', 10 => 'ඔක්තෝබර්', 11 => 'නොවැම්බර්', 12 => 'දෙසැම්බර්'
    ];
    
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

/**
 * File status එක Sinhala වලට හැරවීම
 */
function translate_status(string $status): string {
    $translations = [
        'pending' => 'පිරික්සීමට',
        'archived' => 'අත්තැබූ',
        'rejected' => 'ප්‍රතික්ෂේපිත',
        'active' => 'සක්‍රීය',
        'disabled' => 'අක්‍රීය'
    ];
    
    return $translations[$status] ?? $status;
}

/**
 * Role එක Sinhala වලට හැරවීම
 */
function translate_role(string $role): string {
    $translations = [
        'user' => 'පරිශීලක',
        'admin' => 'පරිපාලක',
        'subject' => 'විෂය නිලධාරී'
    ];
    
    return $translations[$role] ?? $role;
}

/**
 * Department එක English වලට හැරවීම
 */
function translate_department_to_en(string $dept): string {
    $translations = [
        'ආයතන' => 'Institutions',
        'සභා' => 'Councils',
        'ගිණුම්' => 'Accounts'
    ];
    
    return $translations[$dept] ?? $dept;
}

/**
 * Department එක Sinhala වලට හැරවීම
 */
function translate_department_to_si(string $dept): string {
    $translations = [
        'Institutions' => 'ආයතන',
        'Councils' => 'සභා',
        'Accounts' => 'ගිණුම්'
    ];
    
    return $translations[$dept] ?? $dept;
}
