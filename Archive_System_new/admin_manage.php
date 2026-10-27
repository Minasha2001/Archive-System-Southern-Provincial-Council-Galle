<?php
require_once __DIR__ . '/config.php';

// Admin login කරන්න ඕන
$user = require_role($pdo, 'admin');

$error = '';
$success = '';

// User management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_ok()) {
        $error = 'Security check failed. Please reload the page and try again.';
    } else {
        $action = $_POST['action'];

        if ($action === 'activate_user') {
            $user_id = (int)$_POST['user_id'];
            if (activate_user($pdo, $user_id)) {
                $success = 'User activated successfully!';
            } else {
                $error = 'Failed to activate user.';
            }
        } elseif ($action === 'disable_user') {
            $user_id = (int)$_POST['user_id'];
            if (disable_user($pdo, $user_id)) {
                $success = 'User disabled successfully!';
            } else {
                $error = 'Failed to disable user.';
            }
        } elseif ($action === 'delete_user') {
            $user_id = (int)$_POST['user_id'];
            if (delete_user($pdo, $user_id)) {
                $success = 'User deleted successfully!';
            } else {
                $error = 'Failed to delete user.';
            }
        }
    }
}

// Get users
$active_users = get_users($pdo, null, 'active');
$pending_users = get_users($pdo, null, 'pending');
$disabled_users = get_users($pdo, null, 'disabled');

// Get statistics
$stats = get_statistics($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Management - Archive System</title>
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

.alert{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px}
.alert i{margin-top:2px}
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d}
.alert-ok{background:#e8f6ee;border:1px solid #bfe3cd;color:#14633a}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px}
.stat-card{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line);transition:.2s}
.stat-card:hover{transform:translateY(-2px)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;margin-bottom:16px;font-size:20px}
.stat-icon.users{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2)}
.stat-icon.pending{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.stat-icon.files{background:linear-gradient(135deg,var(--green),#15803d);color:#fff}
.stat-icon.dept{background:linear-gradient(135deg,var(--blue),#1d4ed8);color:#fff}
.stat-value{font-size:32px;font-weight:700;color:var(--navy);margin-bottom:4px}
.stat-label{font-size:13px;color:var(--muted);font-weight:500}

.tabs{display:flex;gap:4px;margin-bottom:24px;background:var(--card);padding:6px;border-radius:12px;border:1px solid var(--line)}
.tab{flex:1;padding:12px 20px;border:none;background:transparent;border-radius:10px;font-weight:600;font-size:14px;color:var(--muted);cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px}
.tab:hover{background:var(--bg)}
.tab.active{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff}
.tab i{font-size:14px}

.tab-content{display:none}
.tab-content.active{display:block}

.section{background:var(--card);border-radius:16px;box-shadow:var(--shadow);padding:24px;border:1px solid var(--line);margin-bottom:24px}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.section-header h2{font-size:18px;font-weight:600;color:var(--navy)}
.section-header i{color:var(--gold);margin-right:12px}

.table-wrapper{overflow-x:auto;border-radius:12px;border:1px solid var(--line)}
table{width:100%;border-collapse:collapse;white-space:nowrap}
thead{background:linear-gradient(180deg,#eff3fa,#e6ecf6)}
th{text-align:left;padding:14px 16px;color:#2f4570;font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
td{padding:12px 16px;color:#4b5d7c;border-top:1px solid #edf1f6;font-size:13px}
tbody tr:nth-child(even){background:#fafbfe}
tbody tr:hover{background:#f1f6ff}

.role-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.role-admin{background:#dbeafe;color:#1e40af}
.role-user{background:#d1fae5;color:#065f46}
.role-subject{background:#fef3c7;color:#92400e}

.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
.status-active{background:#d1fae5;color:#065f46}
.status-pending{background:#fef3c7;color:#92400e}
.status-disabled{background:#fee2e2;color:#991b1b}

.btn-group{display:flex;gap:8px}
.btn{padding:8px 14px;border-radius:8px;font-weight:600;font-size:12px;border:none;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:6px}
.btn-success{background:linear-gradient(135deg,var(--green),#15803d);color:#fff}
.btn-danger{background:linear-gradient(135deg,var(--red),#b91c1c);color:#fff}
.btn-secondary{background:#fff;border:1px solid var(--line);color:var(--navy)}
.btn:hover{transform:translateY(-1px)}

.empty-state{text-align:center;padding:40px 20px;color:var(--muted)}
.empty-state i{font-size:48px;margin-bottom:12px;color:var(--line)}
.empty-state p{font-size:14px}

@media (max-width:768px){
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
    .tabs{flex-direction:column}
    .btn-group{flex-direction:column}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-shield-halved" style="color:var(--gold);margin-right:12px"></i>Admin Management</h1>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i><span><?= e($success) ?></span></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon users"><i class="fa-solid fa-users"></i></div>
            <div class="stat-value"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fa-solid fa-user-clock"></i></div>
            <div class="stat-value"><?= $stats['pending_users'] ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon files"><i class="fa-solid fa-folder"></i></div>
            <div class="stat-value"><?= $stats['archived_files'] ?></div>
            <div class="stat-label">Archived Files</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon dept"><i class="fa-solid fa-building"></i></div>
            <div class="stat-value"><?= count(DEPARTMENTS) ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="showTab('active')">
            <i class="fa-solid fa-user-check"></i>Active Users
        </button>
        <button class="tab" onclick="showTab('pending')">
            <i class="fa-solid fa-user-clock"></i>Pending Users
        </button>
        <button class="tab" onclick="showTab('disabled')">
            <i class="fa-solid fa-user-xmark"></i>Disabled Users
        </button>
    </div>

    <div class="tab-content active" id="tab-active">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-users"></i>Active Users</h2>
            </div>
            <?php if (empty($active_users)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users"></i>
                    <p>No active users found.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_users as $u): ?>
                                <tr>
                                    <td><strong><?= e($u['full_name']) ?></strong></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><span class="role-badge role-<?= $u['role'] ?>"><?= translate_role($u['role']) ?></span></td>
                                    <td><?= e($u['department']) ?></td>
                                    <td><?= format_sinhala_date($u['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="post" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="disable_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Disable this user?')">
                                                    <i class="fa-solid fa-ban"></i>Disable
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-content" id="tab-pending">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-user-clock"></i>Pending Users</h2>
            </div>
            <?php if (empty($pending_users)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-user-clock"></i>
                    <p>No pending users to approve.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_users as $u): ?>
                                <tr>
                                    <td><strong><?= e($u['full_name']) ?></strong></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><span class="role-badge role-<?= $u['role'] ?>"><?= translate_role($u['role']) ?></span></td>
                                    <td><?= e($u['department']) ?></td>
                                    <td><?= format_sinhala_date($u['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="post" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="activate_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa-solid fa-check"></i>Approve
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-content" id="tab-disabled">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-user-xmark"></i>Disabled Users</h2>
            </div>
            <?php if (empty($disabled_users)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-user-xmark"></i>
                    <p>No disabled users found.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disabled_users as $u): ?>
                                <tr>
                                    <td><strong><?= e($u['full_name']) ?></strong></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td><span class="role-badge role-<?= $u['role'] ?>"><?= translate_role($u['role']) ?></span></td>
                                    <td><?= e($u['department']) ?></td>
                                    <td><?= format_sinhala_date($u['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="post" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="activate_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa-solid fa-check"></i>Re-activate
                                                </button>
                                            </form>
                                            <form method="post" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Permanently delete this user?')">
                                                    <i class="fa-solid fa-trash"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}
</script>
</body>
</html>
