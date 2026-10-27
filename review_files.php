<?php
require_once __DIR__ . '/config.php';

// Subject officer login කරන්න ඕන
$user = require_role($pdo, 'subject');

$error = '';
$success = '';

// Pending files ලබා ගැනීම
$pending_files = get_pending_files($pdo, $user['department']);

// Archive file action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!csrf_ok()) {
        $error = 'Security check failed. Please reload the page and try again.';
    } else {
        $action = $_POST['action'];
        $file_id = (int)($_POST['file_id'] ?? 0);

        if ($action === 'archive') {
            $archive_number = trim($_POST['archive_number'] ?? '');
            $rack_no = trim($_POST['rack_no'] ?? '');
            $right_side = (int)($_POST['right_side'] ?? 0);
            $left_side = (int)($_POST['left_side'] ?? 0);
            $pages = $right_side + $left_side;

            if ($archive_number === '') {
                $error = 'Archive number is required.';
            } elseif (!is_archive_number_unique($pdo, $archive_number, $file_id)) {
                $error = 'This archive number already exists.';
            } else {
                $data = [
                    'archive_number' => $archive_number,
                    'rack_no' => $rack_no,
                    'right_side' => $right_side,
                    'left_side' => $left_side,
                    'pages' => $pages,
                    'reviewed_by' => $user['id']
                ];

                if (update_file($pdo, $file_id, $data)) {
                    // Update status to archived
                    $stmt = $pdo->prepare("UPDATE archive_files SET status = 'archived', reviewed_at = NOW() WHERE id = ?");
                    $stmt->execute([$file_id]);
                    $success = 'File archived successfully!';
                    $pending_files = get_pending_files($pdo, $user['department']);
                } else {
                    $error = 'Failed to archive file. Please try again.';
                }
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['reject_reason'] ?? '');

            if (mb_strlen($reason) < 5) {
                $error = 'Please provide a rejection reason (at least 5 characters).';
            } else {
                if (reject_file($pdo, $file_id, $reason, $user['id'])) {
                    $success = 'File rejected successfully!';
                    $pending_files = get_pending_files($pdo, $user['department']);
                } else {
                    $error = 'Failed to reject file. Please try again.';
                }
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
<title>Review Files - Subject Officer</title>
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
.container{max-width:1200px;margin:0 auto;padding:30px 20px}

.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:30px}
.header h1{font-size:28px;font-weight:700;color:var(--navy)}
.back-btn{display:flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:var(--navy);font-weight:500;transition:.2s}
.back-btn:hover{background:var(--bg);border-color:var(--blue)}

.alert{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px}
.alert i{margin-top:2px}
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d}
.alert-ok{background:#e8f6ee;border:1px solid #bfe3cd;color:#14633a}

.files-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:20px}
.file-card{background:var(--card);border-radius:12px;box-shadow:var(--shadow);padding:20px;border:1px solid var(--line);transition:.2s}
.file-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(16,37,71,.1)}
.file-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px}
.file-title{font-weight:600;color:var(--navy);font-size:15px;margin-bottom:4px}
.file-meta{font-size:12px;color:var(--muted);display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px}
.file-meta span{display:flex;align-items:center;gap:5px}
.file-meta i{color:var(--gold)}
.file-details{background:var(--bg);border-radius:8px;padding:12px;margin-bottom:16px}
.file-details p{font-size:12px;color:var(--ink);margin-bottom:6px}
.file-details p:last-child{margin-bottom:0}
.file-details strong{color:var(--navy)}

.btn-group{display:flex;gap:10px}
.btn{flex:1;padding:10px 16px;border-radius:8px;font-weight:500;font-size:13px;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff}
.btn-primary:hover{transform:translateY(-1px)}
.btn-danger{background:#fff;border:1px solid var(--red);color:var(--red)}
.btn-danger:hover{background:#fff5f5}

.empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
.empty-state i{font-size:64px;margin-bottom:16px;color:var(--line)}
.empty-state p{font-size:16px}

.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.modal.active{display:flex}
.modal-content{background:var(--card);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);max-width:500px;width:90%;max-height:90vh;overflow-y:auto;padding:30px}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.modal-header h3{font-size:20px;font-weight:600;color:var(--navy)}
.modal-close{width:36px;height:36px;border-radius:50%;border:none;background:var(--bg);cursor:pointer;font-size:18px;color:var(--muted);transition:.2s}
.modal-close:hover{background:var(--line)}

.form-group{margin-bottom:16px}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:var(--navy);font-size:13px}
.form-group input,.form-group textarea{width:100%;padding:10px 14px;border:1px solid var(--line);border-radius:8px;font-size:14px}
.form-group textarea{min-height:80px;resize:vertical}
.form-row{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}

@media (max-width:768px){
    .files-grid{grid-template-columns:1fr}
    .header{flex-direction:column;align-items:flex-start;gap:16px}
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-clipboard-check" style="color:var(--gold);margin-right:12px"></i>Review Pending Files</h1>
        <a href="subject_dashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>Back to Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i><span><?= e($success) ?></span></div>
    <?php endif; ?>

    <?php if (empty($pending_files)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <p>No pending files to review</p>
        </div>
    <?php else: ?>
        <div class="files-grid">
            <?php foreach ($pending_files as $file): ?>
                <div class="file-card">
                    <div class="file-header">
                        <div>
                            <div class="file-title"><?= e($file['file_name']) ?></div>
                            <div class="file-meta">
                                <span><i class="fa-solid fa-hashtag"></i><?= e($file['file_number']) ?></span>
                                <span><i class="fa-solid fa-building"></i><?= e($file['department']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="file-details">
                        <p><strong>Subject:</strong> <?= e($file['subject_number']) ?></p>
                        <p><strong>Period:</strong> <?= format_sinhala_date($file['start_date']) ?> - <?= format_sinhala_date($file['end_date']) ?></p>
                        <p><strong>Pages:</strong> <?= e($file['page_count']) ?></p>
                        <p><strong>Submitted:</strong> <?= format_sinhala_date($file['submit_date']) ?></p>
                        <p><strong>By:</strong> <?= e($file['submitted_by_name'] ?? 'Unknown') ?></p>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" onclick="openArchiveModal(<?= $file['id'] ?>, '<?= e($file['file_number']) ?>')">
                            <i class="fa-solid fa-archive"></i>Archive
                        </button>
                        <button type="button" class="btn btn-danger" onclick="openRejectModal(<?= $file['id'] ?>)">
                            <i class="fa-solid fa-xmark"></i>Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Archive Modal -->
<div class="modal" id="archiveModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Archive File</h3>
            <button type="button" class="modal-close" onclick="closeModal('archiveModal')">&times;</button>
        </div>
        <form method="post" action="review_files.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="archive">
            <input type="hidden" name="file_id" id="archive_file_id">

            <div class="form-group">
                <label for="archive_number">Archive Number *</label>
                <input type="text" id="archive_number" name="archive_number" placeholder="e.g., ARC-INST-0001" required>
            </div>

            <div class="form-group">
                <label for="rack_no">Rack/Tree Number *</label>
                <input type="text" id="rack_no" name="rack_no" placeholder="e.g., R-001" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="right_side">Right Side Pages *</label>
                    <input type="number" id="right_side" name="right_side" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label for="left_side">Left Side Pages *</label>
                    <input type="number" id="left_side" name="left_side" min="0" value="0" required>
                </div>
            </div>

            <div class="btn-group" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" onclick="closeModal('archiveModal')" style="background:#fff;border:1px solid var(--line);color:var(--navy)">Cancel</button>
                <button type="submit" class="btn btn-primary">Archive File</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal" id="rejectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reject File</h3>
            <button type="button" class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form method="post" action="review_files.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="file_id" id="reject_file_id">

            <div class="form-group">
                <label for="reject_reason">Rejection Reason *</label>
                <textarea id="reject_reason" name="reject_reason" placeholder="Please explain why this file is being rejected..." required></textarea>
            </div>

            <div class="btn-group" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')" style="background:#fff;border:1px solid var(--line);color:var(--navy)">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject File</button>
            </div>
        </form>
    </div>
</div>

<script>
function openArchiveModal(fileId, fileNo) {
    document.getElementById('archive_file_id').value = fileId;
    document.getElementById('archive_number').value = '';
    document.getElementById('rack_no').value = '';
    document.getElementById('right_side').value = 0;
    document.getElementById('left_side').value = 0;
    document.getElementById('archiveModal').classList.add('active');
}

function openRejectModal(fileId) {
    document.getElementById('reject_file_id').value = fileId;
    document.getElementById('reject_reason').value = '';
    document.getElementById('rejectModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>
</body>
</html>
