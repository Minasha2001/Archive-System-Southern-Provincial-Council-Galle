<?php
require_once __DIR__ . '/config.php';

// Login කරන්න ඕන
$user = require_login($pdo);

$search = trim($_GET['search'] ?? '');
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($search || $department || $status || $start_date || $end_date)) {
    $sql = "SELECT af.*, u.full_name as submitted_by_name, r.full_name as reviewed_by_name 
            FROM archive_files af 
            LEFT JOIN users u ON af.submitted_by = u.id 
            LEFT JOIN users r ON af.reviewed_by = r.id 
            WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (af.file_name LIKE ? OR af.file_number LIKE ? OR af.archive_number LIKE ? OR af.subject_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($department && in_array($department, DEPARTMENTS, true)) {
        $sql .= " AND af.department = ?";
        $params[] = $department;
    }
    
    if ($status) {
        $sql .= " AND af.status = ?";
        $params[] = $status;
    }
    
    if ($start_date && valid_date($start_date)) {
        $sql .= " AND af.submit_date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date && valid_date($end_date)) {
        $sql .= " AND af.submit_date <= ?";
        $params[] = $end_date;
    }
    
    $sql .= " ORDER BY af.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Files - Archive System</title>
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

.search-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line);margin-bottom:24px}
.search-card h2{font-size:18px;font-weight:600;color:var(--navy);margin-bottom:20px;display:flex;align-items:center;gap:10px}
.search-card h2 i{color:var(--gold)}

.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:var(--navy);font-size:13px}
.form-group input,.form-group select{width:100%;padding:12px 16px;border:1px solid var(--line);border-radius:10px;font-size:14px;transition:.2s;background:#fff}
.form-group input:focus,.form-group select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,208,.1)}
.form-group.full{grid-column:1/-1}

.btn-group{display:flex;gap:12px;justify-content:flex-end}
.btn{padding:12px 28px;border-radius:10px;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:8px}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(11,33,70,.2)}
.btn-secondary{background:#fff;border:1px solid var(--line);color:var(--navy)}
.btn-secondary:hover{background:var(--bg)}

.results-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line)}
.results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.results-header h2{font-size:18px;font-weight:600;color:var(--navy);display:flex;align-items:center;gap:10px}
.results-header h2 i{color:var(--gold)}
.result-count{font-size:13px;color:var(--muted)}

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

.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state i{font-size:64px;margin-bottom:16px;color:var(--line)}
.empty-state p{font-size:16px}

@media (max-width:768px){
    .form-grid{grid-template-columns:1fr}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
    .btn-group{flex-direction:column}
    .btn{width:100%;justify-content:center}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-search" style="color:var(--gold);margin-right:12px"></i>Search Files</h1>
        <a href="<?= ROLE_PAGES[$user['role']] ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <div class="search-card">
        <h2><i class="fa-solid fa-filter"></i>Search & Filter</h2>
        <form method="get" action="search_files.php">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?= e($search) ?>" placeholder="File name, file number, archive number, or subject number...">
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach (DEPARTMENTS as $dept): ?>
                            <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <input type="date" id="start_date" name="start_date" value="<?= e($start_date) ?>" placeholder="From">
                        <input type="date" id="end_date" name="end_date" value="<?= e($end_date) ?>" placeholder="To">
                    </div>
                </div>
            </div>

            <div class="btn-group">
                <a href="search_files.php" class="btn btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>Clear
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-search"></i>Search
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($results) || $search || $department || $status || $start_date || $end_date): ?>
        <div class="results-card">
            <div class="results-header">
                <h2><i class="fa-solid fa-list"></i>Search Results</h2>
                <span class="result-count"><?= count($results) ?> file(s) found</span>
            </div>

            <?php if (empty($results)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-search"></i>
                    <p>No files found matching your criteria.</p>
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
                                <th>Submitted By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $file): ?>
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
                                    <td><?= e($file['submitted_by_name'] ?? 'Unknown') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
