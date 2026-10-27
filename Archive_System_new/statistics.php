<?php
require_once __DIR__ . '/config.php';

// Login කරන්න ඕන
$user = require_login($pdo);

// Get statistics
$stats = get_statistics($pdo);

// Department-wise statistics
$dept_stats = [];
foreach (DEPARTMENTS as $dept) {
    $dept_stats[$dept] = get_department_statistics($pdo, $dept);
}

// Recent files
$recent_files = $pdo->query(
    "SELECT af.*, u.full_name as submitted_by_name 
     FROM archive_files af 
     LEFT JOIN users u ON af.submitted_by = u.id 
     ORDER BY af.created_at DESC 
     LIMIT 10"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistics - Archive System</title>
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
.stat-icon.blue{background:linear-gradient(135deg,var(--blue),#1d4ed8);color:#fff}
.stat-icon.green{background:linear-gradient(135deg,var(--green),#15803d);color:#fff}
.stat-icon.gold{background:linear-gradient(135deg,var(--gold),#b45309);color:#fff}
.stat-icon.red{background:linear-gradient(135deg,var(--red),#b91c1c);color:#fff}
.stat-value{font-size:32px;font-weight:700;color:var(--navy);margin-bottom:4px}
.stat-label{font-size:13px;color:var(--muted);font-weight:500}

.section{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line);margin-bottom:24px}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.section-header h2{font-size:18px;font-weight:600;color:var(--navy);display:flex;align-items:center;gap:10px}
.section-header h2 i{color:var(--gold)}

.dept-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.dept-card{background:linear-gradient(135deg,#fff,#f8fafd);border:1px solid var(--line);border-radius:12px;padding:20px}
.dept-card h3{font-size:16px;font-weight:600;color:var(--navy);margin-bottom:16px;display:flex;align-items:center;gap:8px}
.dept-card h3 i{color:var(--gold)}
.dept-stat{display:flex;justify-content:space-between;margin-bottom:12px;padding-bottom:12px;border-bottom:1px dashed var(--line)}
.dept-stat:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.dept-stat-label{font-size:13px;color:var(--muted)}
.dept-stat-value{font-size:14px;font-weight:600;color:var(--navy)}

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

@media (max-width:768px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .dept-grid{grid-template-columns:1fr}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-chart-pie" style="color:var(--gold);margin-right:12px"></i>Statistics & Reports</h1>
        <a href="<?= ROLE_PAGES[$user['role']] ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <!-- Overall Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-folder"></i></div>
            <div class="stat-value"><?= $stats['total_files'] ?></div>
            <div class="stat-label">Total Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-value"><?= $stats['pending_files'] ?></div>
            <div class="stat-label">Pending Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-value"><?= $stats['archived_files'] ?></div>
            <div class="stat-label">Archived Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-users"></i></div>
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>

    <!-- Department Statistics -->
    <div class="section">
        <div class="section-header">
            <h2><i class="fa-solid fa-building"></i>Department Statistics</h2>
        </div>
        <div class="dept-grid">
            <?php foreach ($dept_stats as $dept => $dstat): ?>
                <div class="dept-card">
                    <h3><i class="fa-solid fa-building-columns"></i><?= e($dept) ?></h3>
                    <div class="dept-stat">
                        <span class="dept-stat-label">Total Files</span>
                        <span class="dept-stat-value"><?= $dstat['total_files'] ?></span>
                    </div>
                    <div class="dept-stat">
                        <span class="dept-stat-label">Archived</span>
                        <span class="dept-stat-value"><?= $dstat['archived_files'] ?></span>
                    </div>
                    <div class="dept-stat">
                        <span class="dept-stat-label">Pending</span>
                        <span class="dept-stat-value"><?= $dstat['pending_files'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="section">
        <div class="section-header">
            <h2><i class="fa-solid fa-users"></i>User Statistics</h2>
        </div>
        <div class="dept-grid">
            <div class="dept-card">
                <h3><i class="fa-solid fa-user"></i>Regular Users</h3>
                <div class="dept-stat">
                    <span class="dept-stat-label">Active Users</span>
                    <span class="dept-stat-value"><?= $stats['users_by_role']['user'] ?></span>
                </div>
            </div>
            <div class="dept-card">
                <h3><i class="fa-solid fa-book-open"></i>Subject Officers</h3>
                <div class="dept-stat">
                    <span class="dept-stat-label">Active Officers</span>
                    <span class="dept-stat-value"><?= $stats['users_by_role']['subject'] ?></span>
                </div>
            </div>
            <div class="dept-card">
                <h3><i class="fa-solid fa-shield-halved"></i>Administrators</h3>
                <div class="dept-stat">
                    <span class="dept-stat-label">Active Admins</span>
                    <span class="dept-stat-value"><?= $stats['users_by_role']['admin'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Files -->
    <div class="section">
        <div class="section-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i>Recent Files</h2>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>File Number</th>
                        <th>File Name</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Submitted By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_files as $file): ?>
                        <tr>
                            <td><strong><?= e($file['file_number']) ?></strong></td>
                            <td><?= e($file['file_name']) ?></td>
                            <td><?= e($file['department']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $file['status'] ?>">
                                    <?= translate_status($file['status']) ?>
                                </span>
                            </td>
                            <td><?= e($file['submitted_by_name'] ?? 'Unknown') ?></td>
                            <td><?= format_sinhala_date($file['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
