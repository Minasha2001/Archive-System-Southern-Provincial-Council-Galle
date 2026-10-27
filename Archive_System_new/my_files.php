<?php
require_once __DIR__ . '/config.php';

// User login කරන්න ඕන
$user = require_login($pdo);

// User ගේ ගොනු ලබා ගැනීම
$my_files = get_files_by_user($pdo, $user['id']);

// Statistics
$stats = [
    'total' => count($my_files),
    'pending' => 0,
    'archived' => 0,
    'rejected' => 0
];

foreach ($my_files as $file) {
    if ($file['status'] === 'pending') $stats['pending']++;
    elseif ($file['status'] === 'archived') $stats['archived']++;
    elseif ($file['status'] === 'rejected') $stats['rejected']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Files - Archive System</title>
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
button,input,select{font:inherit}
button{cursor:pointer}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
.container{max-width:1400px;margin:0 auto;padding:30px 20px}

.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.header h1{font-size:28px;font-weight:700;color:var(--navy)}
.back-btn{display:flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:var(--navy);font-weight:500;transition:.2s}
.back-btn:hover{background:var(--bg);border-color:var(--blue)}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px}
.stat-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line);transition:.2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;margin-bottom:16px;font-size:20px}
.stat-icon.total{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2)}
.stat-icon.pending{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.stat-icon.archived{background:linear-gradient(135deg,var(--green),#15803d);color:#fff}
.stat-icon.rejected{background:linear-gradient(135deg,var(--red),#b91c1c);color:#fff}
.stat-value{font-size:32px;font-weight:700;color:var(--navy);margin-bottom:4px}
.stat-label{font-size:13px;color:var(--muted);font-weight:500}

.files-section{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line)}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.section-header h2{font-size:20px;font-weight:600;color:var(--navy)}
.section-header i{color:var(--gold);margin-right:12px}

.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--line)}
table{width:100%;border-collapse:collapse;white-space:nowrap}
thead{background:linear-gradient(180deg,#eff3fa,#e6ecf6)}
th{text-align:left;padding:14px 16px;color:#2f4570;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
td{padding:12px 16px;color:#4b5d7c;border-top:1px solid #edf1f6;font-size:13px}
tbody tr:nth-child(even){background:#fafbfe}
tbody tr:hover{background:#f1f6ff}

.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.status-pending{background:#fef3c7;color:#92400e}
.status-archived{background:#d1fae5;color:#065f46}
.status-rejected{background:#fee2e2;color:#991b1b}

.btn{padding:10px 20px;border-radius:10px;font-weight:600;font-size:13px;border:none;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(11,33,70,.2)}
.btn-sm{padding:8px 14px;font-size:12px}

.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state i{font-size:64px;margin-bottom:16px;color:var(--line)}
.empty-state p{font-size:16px;margin-bottom:20px}

@media (max-width:768px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
    .section-header{flex-direction:column;align-items:flex-start;gap:12px}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-folder-open" style="color:var(--gold);margin-right:12px"></i>My Submitted Files</h1>
        <div style="display:flex;gap:12px">
            <a href="submit_file.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>Submit New File
            </a>
            <a href="<?= ROLE_PAGES[$user['role']] ?>" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total"><i class="fa-solid fa-folder"></i></div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-value"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon archived"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-value"><?= $stats['archived'] ?></div>
            <div class="stat-label">Archived</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rejected"><i class="fa-solid fa-xmark-circle"></i></div>
            <div class="stat-value"><?= $stats['rejected'] ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <div class="files-section">
        <div class="section-header">
            <h2><i class="fa-solid fa-list"></i>File List</h2>
        </div>

        <?php if (empty($my_files)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <p>You haven't submitted any files yet.</p>
                <a href="submit_file.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i>Submit Your First File
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>File Number</th>
                            <th>File Name</th>
                            <th>Department</th>
                            <th>Subject No</th>
                            <th>Submit Date</th>
                            <th>Status</th>
                            <th>Archive No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_files as $file): ?>
                            <tr>
                                <td><strong><?= e($file['file_number']) ?></strong></td>
                                <td><?= e($file['file_name']) ?></td>
                                <td><?= e($file['department']) ?></td>
                                <td><?= e($file['subject_number']) ?></td>
                                <td><?= format_sinhala_date($file['submit_date']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $file['status'] ?>">
                                        <?= translate_status($file['status']) ?>
                                    </span>
                                </td>
                                <td><?= e($file['archive_number'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
