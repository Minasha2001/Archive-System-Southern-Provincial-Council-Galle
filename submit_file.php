<?php
require_once __DIR__ . '/config.php';

// User login කරන්න ඕන
$user = require_login($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $error = 'Security check failed. Please reload the page and try again.';
    } else {
        $submit_date = trim($_POST['submit_date'] ?? '');
        $subject_number = trim($_POST['subject_number'] ?? '');
        $department = $_POST['department'] ?? '';
        $file_number = trim($_POST['file_number'] ?? '');
        $file_name = trim($_POST['file_name'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $page_count = (int)($_POST['page_count'] ?? 0);

        // Validation
        if ($submit_date === '' || !valid_date($submit_date)) {
            $error = 'Please enter a valid submit date.';
        } elseif ($subject_number === '') {
            $error = 'Subject number is required.';
        } elseif (!in_array($department, DEPARTMENTS, true)) {
            $error = 'Please select a valid department.';
        } elseif ($file_number === '') {
            $error = 'File number is required.';
        } elseif ($file_name === '') {
            $error = 'File name is required.';
        } elseif ($start_date === '' || !valid_date($start_date)) {
            $error = 'Please enter a valid start date.';
        } elseif ($end_date === '' || !valid_date($end_date)) {
            $error = 'Please enter a valid end date.';
        } elseif (strtotime($end_date) < strtotime($start_date)) {
            $error = 'End date must be after start date.';
        } elseif ($page_count < 1) {
            $error = 'Page count must be at least 1.';
        } elseif (!is_file_number_unique($pdo, $file_number)) {
            $error = 'This file number already exists. Please use a different number.';
        } else {
            // File submit කරන්න
            $data = [
                'submitted_by' => $user['id'],
                'submit_date' => $submit_date,
                'subject_number' => $subject_number,
                'department' => $department,
                'file_number' => $file_number,
                'file_name' => $file_name,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'page_count' => $page_count
            ];

            if (submit_file($pdo, $data)) {
                $success = 'File submitted successfully! It will be reviewed by the Subject Officer.';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Failed to submit file. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit File - Archive System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{
    --ink:#14233d; --navy:#0b2146; --navy-2:#16386a; --gold:#d4a24a; --gold-2:#f0cf8a;
    --bg:#eef2f8; --card:#fff; --line:#e1e7f1; --muted:#6a7890; --blue:#2563d0; --red:#c93636; --green:#17946a;
    --shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);
    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;
    background:radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%),var(--bg)}
button,input,select,textarea{font:inherit}
button{cursor:pointer}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
.container{max-width:900px;margin:0 auto;padding:30px 20px}

.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.header h1{font-size:28px;font-weight:700;color:var(--navy)}
.back-btn{display:flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:var(--navy);font-weight:500;transition:.2s}
.back-btn:hover{background:var(--bg);border-color:var(--blue)}

.card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:30px;margin-bottom:20px}
.card-header{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.card-header i{font-size:24px;color:var(--gold)}
.card-header h2{font-size:20px;font-weight:600;color:var(--navy)}

.alert{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px}
.alert i{margin-top:2px}
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d}
.alert-ok{background:#e8f6ee;border:1px solid #bfe3cd;color:#14633a}

.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}
.form-group{margin-bottom:20px}
.form-group.full{grid-column:1/-1}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:var(--navy);font-size:13px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 16px;border:1px solid var(--line);border-radius:10px;font-size:14px;transition:.2s;background:#fff}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,208,.1)}
.form-group textarea{min-height:100px;resize:vertical}
.form-group small{display:block;margin-top:6px;color:var(--muted);font-size:11px}

.btn-group{display:flex;gap:12px;justify-content:flex-end;margin-top:24px}
.btn{padding:12px 28px;border-radius:10px;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:8px}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(11,33,70,.2)}
.btn-secondary{background:#fff;border:1px solid var(--line);color:var(--navy)}
.btn-secondary:hover{background:var(--bg)}

@media (max-width:768px){
    .form-grid{grid-template-columns:1fr}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-file-circle-plus" style="color:var(--gold);margin-right:12px"></i>Submit New File</h1>
        <a href="<?= ROLE_PAGES[$user['role']] ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-folder-plus"></i>
            <h2>File Information</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i><span><?= e($success) ?></span></div>
        <?php endif; ?>

        <form method="post" action="submit_file.php">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="submit_date">Submit Date *</label>
                    <input type="date" id="submit_date" name="submit_date" value="<?= e($_POST['submit_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="subject_number">Subject Number *</label>
                    <input type="text" id="subject_number" name="subject_number" value="<?= e($_POST['subject_number'] ?? $user['subject_number']) ?>" placeholder="e.g., SUB-001" required>
                </div>

                <div class="form-group">
                    <label for="department">Department *</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach (DEPARTMENTS as $dept): ?>
                            <option value="<?= e($dept) ?>" <?= (($_POST['department'] ?? '') === $dept ? 'selected' : '') ?>><?= e($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="file_number">File Number *</label>
                    <input type="text" id="file_number" name="file_number" value="<?= e($_POST['file_number'] ?? '') ?>" placeholder="e.g., F-001" required>
                    <small>Unique identifier for this file</small>
                </div>

                <div class="form-group full">
                    <label for="file_name">File Name *</label>
                    <input type="text" id="file_name" name="file_name" value="<?= e($_POST['file_name'] ?? '') ?>" placeholder="Enter file name/description" required>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" value="<?= e($_POST['start_date'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date *</label>
                    <input type="date" id="end_date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="page_count">Page Count *</label>
                    <input type="number" id="page_count" name="page_count" value="<?= e($_POST['page_count'] ?? '') ?>" min="1" placeholder="Total number of pages" required>
                </div>
            </div>

            <div class="btn-group">
                <a href="<?= ROLE_PAGES[$user['role']] ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-xmark"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i>Submit File
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-fill department based on user's department
document.addEventListener('DOMContentLoaded', function() {
    const userDept = '<?= e($user['department']) ?>';
    const deptSelect = document.getElementById('department');
    if (userDept && deptSelect) {
        deptSelect.value = userDept;
    }
});
</script>
</body>
</html>
