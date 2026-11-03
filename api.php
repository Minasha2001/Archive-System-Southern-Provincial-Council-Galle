<?php
/**
 * api.php - single JSON endpoint used by the user, subject officer and admin dashboards.
 *
 * File workflow:
 *   pending           user submitted -> subject officer
 *   waiting_approval  subject officer added archive no + tree no -> admin
 *   active            approved by admin -> part of the archive
 *   rejected          admin sent it back to the subject officer (with a reason)
 *   deleted           removed from the archive by the admin (reason + date kept)
 *
 * File Location (entered by the user when submitting):
 *   loc_rack_no  -> rackNo     (Rack No)
 *   loc_row      -> rackRow    (Row)
 *   loc_section  -> rackRoman  (Section: I | II | III)
 * "rack" (archive_no's Archive Tree Number, rack_no column) is a different field set by the Subject Officer.
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $message, int $code = 422): void {
    out(['error' => $message], $code);
}

/* ---------- logged-in, active account ---------- */
$user = null;
if (!empty($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ? AND status = \'active\'');
    $st->execute([(int)$_SESSION['user_id']]);
    $user = $st->fetch() ?: null;
}
if (!$user) {
    out(['error' => 'Please log in again.', 'logout' => true], 401);
}
$uid  = (int)$user['id'];
$role = $user['role'];

if (empty($_SESSION['api_csrf'])) {
    $_SESSION['api_csrf'] = bin2hex(random_bytes(32));
}

/* ---------- department names: English (UI) <-> Sinhala (DB) ---------- */
const DEPT_DB = ['Institutions' => 'ආයතන', 'Councils' => 'සභා', 'Accounts' => 'ගිණුම්'];
const DEPT_UI = ['ආයතන' => 'Institutions', 'සභා' => 'Councils', 'ගිණුම්' => 'Accounts'];

/* ---------- File Location: allowed Section values ---------- */
const LOC_SECTIONS = ['I', 'II', 'III'];

function dept_ui(string $dep): string { return DEPT_UI[$dep] ?? $dep; }

function map_file(array $r): array {
    $r['department'] = dept_ui((string)$r['department']);
    foreach (['id', 'pages', 'right', 'left'] as $k) {
        if (array_key_exists($k, $r) && $r[$k] !== null) $r[$k] = (int)$r[$k];
    }
    return $r;
}

const FILE_COLUMNS =
    "a.id, a.file_name AS name, a.department, a.subject_no AS subject, a.file_no AS fileNo,
     a.archive_no AS archive, a.rack_no AS rack,
     a.loc_rack_no AS rackNo, a.loc_row AS rackRow, a.loc_section AS rackRoman,
     a.start_date AS `start`, a.end_date AS `end`,
     a.right_side AS `right`, a.left_side AS `left`, a.pages, a.status,
     a.submit_date AS submissionDate,
     DATE(COALESCE(a.approved_at, a.created_at)) AS `date`,
     DATE(a.sent_date) AS sentDate, a.reject_reason AS rejectReason,
     DATE(a.deleted_at) AS deletedDate, a.delete_reason AS reason,
     a.file_path AS fileUrl, sb.full_name AS submittedBy, sn.full_name AS sentBy";

const FILE_JOINS =
    "FROM archive_files a
     LEFT JOIN users sb ON sb.id = a.submitted_by
     LEFT JOIN users sn ON sn.id = a.sent_by";

function valid_date_str($d): bool {
    return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
}

function open_loan_ids(PDO $pdo): array {
    return $pdo->query('SELECT file_id FROM file_issues WHERE returned_date IS NULL')
               ->fetchAll(PDO::FETCH_COLUMN);
}

$action = $_GET['action'] ?? 'state';

/* =====================================================================
   STATE
   ===================================================================== */
if ($action === 'state') {
    $files = array_map('map_file', $pdo->query(
        "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . " WHERE a.status = 'active'
         ORDER BY COALESCE(a.approved_at, a.created_at) DESC"
    )->fetchAll());

    $data = [
        'csrf' => $_SESSION['api_csrf'],
        'user' => [
            'id'         => $uid,
            'name'       => $user['full_name'],
            'username'   => $user['username'],
            'role'       => $role,
            'department' => dept_ui($user['department']),
        ],
        'files' => $files,
    ];

    if ($role === 'user') {
        $data['submissions'] = array_map('map_file', $pdo->query(
            "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . "
             WHERE a.submitted_by = " . $uid . " AND a.status <> 'deleted'
             ORDER BY a.id DESC"
        )->fetchAll());
    }

    if ($role === 'subject') {
        $data['pending'] = array_map('map_file', $pdo->query(
            "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . "
             WHERE a.status IN ('pending', 'rejected') ORDER BY a.id DESC"
        )->fetchAll());

        $data['waiting'] = array_map('map_file', $pdo->query(
            "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . "
             WHERE a.status = 'waiting_approval' ORDER BY a.sent_date DESC, a.id DESC"
        )->fetchAll());

        $data['loans'] = array_map(function (array $l): array {
            $l['id'] = (int)$l['id'];
            $l['fileId'] = (int)$l['fileId'];
            $l['department'] = dept_ui((string)$l['department']);
            return $l;
        }, $pdo->query(
            "SELECT l.id, l.file_id AS fileId, l.taken_by AS takenBy, l.taken_date AS takenDate,
                    l.returned_date AS returnedDate, f.file_name AS name, f.archive_no AS archive,
                    f.rack_no AS rack, f.department
             FROM file_issues l JOIN archive_files f ON f.id = l.file_id
             ORDER BY l.id DESC"
        )->fetchAll());

        $open = array_flip(open_loan_ids($pdo));
        foreach ($data['files'] as &$f) {
            $f['issued'] = isset($open[$f['id']]);
        }
        unset($f);
    }

    if ($role === 'admin') {
        $data['approvals'] = array_map('map_file', $pdo->query(
            "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . "
             WHERE a.status = 'waiting_approval' ORDER BY a.sent_date DESC, a.id DESC"
        )->fetchAll());

        $data['deletedFiles'] = array_map('map_file', $pdo->query(
            "SELECT " . FILE_COLUMNS . " " . FILE_JOINS . "
             WHERE a.status = 'deleted' ORDER BY a.deleted_at DESC"
        )->fetchAll());

        $activeUsers = $pdo->query(
            "SELECT id, full_name AS name, username, role, department, DATE(created_at) AS registered
             FROM users WHERE status = 'active'
             ORDER BY FIELD(role, 'admin', 'subject', 'user'), id"
        )->fetchAll();
        $data['users'] = array_map(function (array $u) use ($uid): array {
            $u['id'] = (int)$u['id'];
            $u['department'] = dept_ui((string)$u['department']);
            $u['isSelf'] = $u['id'] === $uid;
            return $u;
        }, $activeUsers);

        $data['pendingUsers'] = array_map(function (array $u): array {
            $u['id'] = (int)$u['id'];
            $u['department'] = dept_ui((string)$u['department']);
            return $u;
        }, $pdo->query(
            "SELECT id, full_name AS name, username, role, department, email, DATE(created_at) AS registered
             FROM users WHERE status = 'pending' ORDER BY id"
        )->fetchAll());

        $data['deletedUsers'] = array_map(function (array $u): array {
            $u['id'] = (int)$u['id'];
            $u['department'] = dept_ui((string)$u['department']);
            return $u;
        }, $pdo->query(
            "SELECT id, full_name AS name, username, role, department, DATE(created_at) AS registered,
                    DATE(deleted_at) AS deletedDate
             FROM users WHERE status = 'deleted' ORDER BY deleted_at DESC"
        )->fetchAll());
    }

    out($data);
}

/* =====================================================================
   POST only + CSRF
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('POST required.', 405);
}
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['api_csrf'], $token)) {
    fail('Security token expired. Please reload the page.', 419);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = [];

/* =====================================================================
   USER: submit / delete own submission
   ===================================================================== */
if ($action === 'submit_file') {
    if ($role !== 'user') fail('Not allowed.', 403);

    $name      = trim((string)($in['name'] ?? ''));
    $dept      = (string)($in['department'] ?? '');
    $subject   = trim((string)($in['subject'] ?? ''));
    $fileNo    = trim((string)($in['fileNo'] ?? ''));
    $start     = (string)($in['start'] ?? '');
    $end       = (string)($in['end'] ?? '');
    $submit    = (string)($in['submitDate'] ?? '');
    $rackNo    = trim((string)($in['rackNo'] ?? ''));
    $rackRow   = trim((string)($in['rackRow'] ?? ''));
    $rackRoman = trim((string)($in['rackRoman'] ?? ''));
    $right     = $in['right'] ?? null;
    $left      = $in['left'] ?? null;
    $pages     = $in['pages'] ?? null;

    if ($name === '') fail('Enter the file name.');
    if ($subject === '') fail('Enter the subject number.');
    if ($fileNo === '') fail('Enter the file number.');
    if (!isset(DEPT_DB[$dept])) fail('Select a valid department.');
    if (!valid_date_str($start) || !valid_date_str($end)) fail('Enter a valid file start date and end date.');
    if ($end < $start) fail('File End Date must be on or after File Start Date.');
    if (!valid_date_str($submit)) fail('Enter a valid submission date.');
    if ($submit < $end) fail('Submission Date cannot be earlier than the File End Date.');
    if ($rackNo === '' || mb_strlen($rackNo) > 20) fail('Enter a valid Rack No (up to 20 characters).');
    if ($rackRow === '' || mb_strlen($rackRow) > 20) fail('Enter a valid Row (up to 20 characters).');
    if (!in_array($rackRoman, LOC_SECTIONS, true)) fail('Select the Section (I, II or III).');
    if (!is_numeric($right) || (int)$right < 0) fail('Enter a valid Right Side page count.');
    if (!is_numeric($left) || (int)$left < 0) fail('Enter a valid Left Side page count.');
    if (!is_numeric($pages) || (int)$pages < 1) fail('Enter a valid Total Pages number.');

    $depDb = DEPT_DB[$dept];
    $st = $pdo->prepare(
        "INSERT INTO archive_files
         (submitted_by, submit_date, subject_no, subject_number, department, file_no, file_number,
          file_name, start_date, end_date, pages, page_count, right_side, left_side,
          loc_rack_no, loc_row, loc_section, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')"
    );
    $st->execute([$uid, $submit, $subject, $subject, $depDb, $fileNo, $fileNo, $name,
                  $start, $end, (int)$pages, (int)$pages, (int)$right, (int)$left,
                  $rackNo, $rackRow, $rackRoman]);
    out(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($action === 'delete_submission') {
    if ($role !== 'user') fail('Not allowed.', 403);
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid file.');
    $st = $pdo->prepare("DELETE FROM archive_files WHERE id = ? AND submitted_by = ? AND status IN ('pending','rejected')");
    $st->execute([$id, $uid]);
    if ($st->rowCount() === 0) fail('This file can no longer be deleted (it may already be with the Subject Officer).', 409);
    out(['ok' => true]);
}

/* =====================================================================
   SUBJECT OFFICER
   ===================================================================== */
if ($action === 'send_for_approval') {
    if ($role !== 'subject') fail('Not allowed.', 403);

    $id      = (int)($in['id'] ?? 0);
    $name    = trim((string)($in['name'] ?? ''));
    $dept    = (string)($in['department'] ?? '');
    $subject = trim((string)($in['subject'] ?? ''));
    $fileNo  = trim((string)($in['fileNo'] ?? ''));
    $start   = (string)($in['start'] ?? '');
    $end     = (string)($in['end'] ?? '');
    $right   = $in['right'] ?? null;
    $left    = $in['left'] ?? null;
    $pages   = $in['pages'] ?? null;
    $archive = trim((string)($in['archive'] ?? ''));
    $rack    = trim((string)($in['rack'] ?? ''));

    if ($id <= 0) fail('Invalid file.');
    if ($name === '' || $subject === '' || $fileNo === '') fail('File name, subject number and file number are required.');
    if (!isset(DEPT_DB[$dept])) fail('Select a valid department.');
    if (!valid_date_str($start) || !valid_date_str($end) || $end < $start) fail('Enter valid file start and end dates.');
    if (!is_numeric($right) || (int)$right < 0 || !is_numeric($left) || (int)$left < 0) fail('Enter valid Right Side and Left Side page numbers.');
    if (!is_numeric($pages) || (int)$pages < 1) fail('Enter a valid Total Pages number.');
    if ($archive === '') fail('Enter the Archive Number.');
    if ($rack === '') fail('Enter the Archive Tree Number.');

    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM archive_files
         WHERE LOWER(archive_no) = LOWER(?) AND id <> ? AND status IN ('active','waiting_approval')"
    );
    $st->execute([$archive, $id]);
    if ((int)$st->fetchColumn() > 0) {
        fail('This Archive Number is already used by another file (or is waiting for admin approval).', 409);
    }

    $st = $pdo->prepare(
        "UPDATE archive_files SET
            file_name = ?, department = ?, subject_no = ?, subject_number = ?, file_no = ?, file_number = ?,
            start_date = ?, end_date = ?, pages = ?, page_count = ?, right_side = ?, left_side = ?,
            archive_no = ?, archive_number = ?, rack_no = ?,
            status = 'waiting_approval', reviewed_by = ?, reviewed_at = NOW(),
            sent_by = ?, sent_date = NOW(), reject_reason = NULL, rejected_at = NULL
         WHERE id = ? AND status IN ('pending','rejected')"
    );
    $st->execute([$name, DEPT_DB[$dept], $subject, $subject, $fileNo, $fileNo, $start, $end,
                  (int)$pages, (int)$pages, (int)$right, (int)$left, $archive, $archive, $rack,
                  $uid, $uid, $id]);
    if ($st->rowCount() === 0) fail('File not found or already sent for approval.', 404);
    out(['ok' => true]);
}

if ($action === 'update_record') {
    if ($role !== 'subject') fail('Not allowed.', 403);

    $id      = (int)($in['id'] ?? 0);
    $name    = trim((string)($in['name'] ?? ''));
    $dept    = (string)($in['department'] ?? '');
    $subject = trim((string)($in['subject'] ?? ''));
    $fileNo  = trim((string)($in['fileNo'] ?? ''));
    $start   = (string)($in['start'] ?? '');
    $end     = (string)($in['end'] ?? '');
    $right   = $in['right'] ?? null;
    $left    = $in['left'] ?? null;
    $pages   = $in['pages'] ?? null;
    $archive = trim((string)($in['archive'] ?? ''));
    $rack    = trim((string)($in['rack'] ?? ''));

    if ($id <= 0) fail('Invalid file.');
    if ($name === '' || $subject === '' || $fileNo === '') fail('File name, subject number and file number are required.');
    if (!isset(DEPT_DB[$dept])) fail('Select a valid department.');
    if (!valid_date_str($start) || !valid_date_str($end) || $end < $start) fail('Enter valid file start and end dates.');
    if (!is_numeric($right) || (int)$right < 0 || !is_numeric($left) || (int)$left < 0) fail('Enter valid Right Side and Left Side page numbers.');
    if (!is_numeric($pages) || (int)$pages < 1) fail('Enter a valid Total Pages number.');
    if ($archive === '') fail('Enter the Archive Number.');
    if ($rack === '') fail('Enter the Archive Tree Number.');

    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM archive_files
         WHERE LOWER(archive_no) = LOWER(?) AND id <> ? AND status IN ('active','waiting_approval')"
    );
    $st->execute([$archive, $id]);
    if ((int)$st->fetchColumn() > 0) {
        fail('This Archive Number is already used by another file.', 409);
    }

    $st = $pdo->prepare(
        "UPDATE archive_files SET
            file_name = ?, department = ?, subject_no = ?, subject_number = ?, file_no = ?, file_number = ?,
            start_date = ?, end_date = ?, pages = ?, page_count = ?, right_side = ?, left_side = ?,
            archive_no = ?, archive_number = ?, rack_no = ?
         WHERE id = ? AND status = 'active'"
    );
    $st->execute([$name, DEPT_DB[$dept], $subject, $subject, $fileNo, $fileNo, $start, $end,
                  (int)$pages, (int)$pages, (int)$right, (int)$left, $archive, $archive, $rack, $id]);
    if ($st->rowCount() === 0) fail('Archive file not found.', 404);
    out(['ok' => true]);
}

if ($action === 'issue_file') {
    if ($role !== 'subject') fail('Not allowed.', 403);

    $fileId   = (int)($in['fileId'] ?? 0);
    $takenBy  = trim((string)($in['takenBy'] ?? ''));
    $takenOn  = (string)($in['takenDate'] ?? '');

    if ($fileId <= 0) fail('Select the archive file.');
    if (mb_strlen($takenBy) < 2) fail('Enter the name of the person who took the file.');
    if (!valid_date_str($takenOn)) fail('Enter a valid date taken.');

    $st = $pdo->prepare("SELECT status FROM archive_files WHERE id = ?");
    $st->execute([$fileId]);
    if ($st->fetchColumn() !== 'active') fail('Only archive files can be issued.', 404);

    $st = $pdo->prepare('SELECT COUNT(*) FROM file_issues WHERE file_id = ? AND returned_date IS NULL');
    $st->execute([$fileId]);
    if ((int)$st->fetchColumn() > 0) fail('This file is already issued.', 409);

    $pdo->prepare('INSERT INTO file_issues (file_id, taken_by, taken_date, issued_by) VALUES (?,?,?,?)')
        ->execute([$fileId, $takenBy, $takenOn, $uid]);
    out(['ok' => true]);
}

if ($action === 'return_file') {
    if ($role !== 'subject') fail('Not allowed.', 403);

    $loanId  = (int)($in['id'] ?? 0);
    $date    = (string)($in['returnedDate'] ?? '');
    if ($loanId <= 0) fail('Invalid record.');
    if (!valid_date_str($date)) fail('Enter a valid date returned.');

    $st = $pdo->prepare('SELECT taken_date FROM file_issues WHERE id = ? AND returned_date IS NULL');
    $st->execute([$loanId]);
    $taken = $st->fetchColumn();
    if ($taken === false) fail('This file is already returned or does not exist.', 404);
    if ($date < $taken) fail('The return date cannot be earlier than the date the file was taken.');

    $pdo->prepare('UPDATE file_issues SET returned_date = ?, received_by = ? WHERE id = ?')
        ->execute([$date, $uid, $loanId]);
    out(['ok' => true]);
}

/* =====================================================================
   ADMIN
   ===================================================================== */
if ($role !== 'admin' && in_array($action, [
    'approve_file', 'reject_file', 'delete_file', 'restore_file',
    'approve_user', 'reject_user', 'delete_user'
], true)) {
    fail('Not allowed.', 403);
}

if ($action === 'approve_file') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid file.');

    $st = $pdo->prepare(
        "UPDATE archive_files SET status = 'active', approved_at = NOW(), approved_by = ?
         WHERE id = ? AND status = 'waiting_approval'"
    );
    $st->execute([$uid, $id]);
    if ($st->rowCount() === 0) fail('File not found or already handled.', 404);
    out(['ok' => true]);
}

if ($action === 'reject_file') {
    $id     = (int)($in['id'] ?? 0);
    $reason = trim((string)($in['reason'] ?? ''));
    if ($id <= 0) fail('Invalid file.');
    if (mb_strlen($reason) < 5) fail('Enter the reason (at least 5 characters).');

    $st = $pdo->prepare(
        "UPDATE archive_files SET status = 'rejected', reject_reason = ?, rejected_at = NOW()
         WHERE id = ? AND status = 'waiting_approval'"
    );
    $st->execute([$reason, $id]);
    if ($st->rowCount() === 0) fail('File not found or already handled.', 404);
    out(['ok' => true]);
}

if ($action === 'delete_file') {
    $id     = (int)($in['id'] ?? 0);
    $date   = (string)($in['date'] ?? '');
    $reason = trim((string)($in['reason'] ?? ''));
    if ($id <= 0) fail('Invalid file.');
    if (!valid_date_str($date)) fail('Select a valid deletion date.');
    if (mb_strlen($reason) < 5) fail('Enter the reason (at least 5 characters).');

    $st = $pdo->prepare('SELECT COUNT(*) FROM file_issues WHERE file_id = ? AND returned_date IS NULL');
    $st->execute([$id]);
    if ((int)$st->fetchColumn() > 0) {
        fail('This file is currently issued to someone. Mark it as returned before deleting it.', 409);
    }

    $st = $pdo->prepare(
        "UPDATE archive_files SET status = 'deleted', deleted_at = ?, delete_reason = ?, deleted_by = ?
         WHERE id = ? AND status = 'active'"
    );
    $st->execute([$date . ' ' . date('H:i:s'), $reason, $uid, $id]);
    if ($st->rowCount() === 0) fail('File not found or already deleted.', 404);
    out(['ok' => true]);
}

if ($action === 'restore_file') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid file.');

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET status = 'active', deleted_at = NULL, delete_reason = NULL, deleted_by = NULL
         WHERE id = ? AND status = 'deleted'"
    );
    $st->execute([$id]);
    if ($st->rowCount() === 0) fail('File not found or not deleted.', 404);
    out(['ok' => true]);
}

if ($action === 'approve_user') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid account.');

    $st = $pdo->prepare(
        "UPDATE users SET status = 'active', approved_by = ?, approved_at = NOW()
         WHERE id = ? AND status = 'pending'"
    );
    $st->execute([$uid, $id]);
    if ($st->rowCount() === 0) fail('Account not found or already handled.', 404);
    out(['ok' => true]);
}

if ($action === 'reject_user') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid account.');

    $st = $pdo->prepare(
        "UPDATE users SET status = 'deleted', deleted_at = NOW()
         WHERE id = ? AND status = 'pending'"
    );
    $st->execute([$id]);
    if ($st->rowCount() === 0) fail('Account not found or already handled.', 404);
    out(['ok' => true]);
}

if ($action === 'delete_user') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Invalid account.');
    if ($id === $uid) fail('You cannot delete your own account.', 409);

    $st = $pdo->prepare("SELECT role FROM users WHERE id = ? AND status <> 'deleted'");
    $st->execute([$id]);
    $targetRole = $st->fetchColumn();
    if ($targetRole === false) fail('Account not found or already deleted.', 404);

    if ($targetRole === 'admin') {
        $admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'")->fetchColumn();
        if ($admins <= 1) fail('At least one active administrator must remain.', 409);
    }

    $pdo->prepare("UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = ?")
        ->execute([$id]);
    out(['ok' => true]);
}

/* =====================================================================
   CHANGE PASSWORD (all roles)
   ===================================================================== */
if ($action === 'change_password') {
    $current = (string)($in['current'] ?? '');
    $new     = (string)($in['new'] ?? '');
    $confirm = (string)($in['confirm'] ?? '');

    if ($current === '') fail('Enter your current password.');
    if (strlen($new) < 6) fail('New password must contain at least 6 characters.');
    if (strlen($new) > 72) fail('New password is too long (maximum 72 characters).');
    if ($new !== $confirm) fail('New Password and Confirm Password do not match.');
    if (!password_verify($current, $user['password_hash'])) fail('Current password is incorrect.', 403);
    if ($new === $current) fail('New password must be different from your current password.');

    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
    out(['ok' => true]);
}

fail('Unknown action.', 400);