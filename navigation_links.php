<?php
/**
 * Navigation links based on user role
 * Include this in dashboards to add navigation
 */

$nav_links = [];

if ($user['role'] === 'user') {
    $nav_links = [
        ['url' => 'user_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
        ['url' => 'my_files.php', 'label' => 'My Files', 'icon' => 'fa-folder-open'],
        ['url' => 'submit_file.php', 'label' => 'Submit File', 'icon' => 'fa-plus'],
        ['url' => 'change_password.php', 'label' => 'Change Password', 'icon' => 'fa-key'],
        ['url' => 'logout.php', 'label' => 'Logout', 'icon' => 'fa-sign-out-alt']
    ];
} elseif ($user['role'] === 'subject') {
    $nav_links = [
        ['url' => 'subject_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
        ['url' => 'review_files.php', 'label' => 'Review Files', 'icon' => 'fa-clipboard-check'],
        ['url' => 'subject_change_password.php', 'label' => 'Change Password', 'icon' => 'fa-key'],
        ['url' => 'logout.php', 'label' => 'Logout', 'icon' => 'fa-sign-out-alt']
    ];
} elseif ($user['role'] === 'admin') {
    $nav_links = [
        ['url' => 'admin_dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-home'],
        ['url' => 'admin_manage.php', 'label' => 'Manage Users', 'icon' => 'fa-users'],
        ['url' => 'admin_change_password.php', 'label' => 'Change Password', 'icon' => 'fa-key'],
        ['url' => 'logout.php', 'label' => 'Logout', 'icon' => 'fa-sign-out-alt']
    ];
}
?>

<!-- Quick Navigation -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap">
    <?php foreach ($nav_links as $link): ?>
        <a href="<?= e($link['url']) ?>" 
           style="display:flex;align-items:center;gap:8px;padding:10px 20px;background:#fff;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:var(--navy);font-weight:500;transition:.2s;font-size:13px"
           onmouseover="this.style.background='var(--bg)';this.style.borderColor='var(--blue)'"
           onmouseout="this.style.background='#fff';this.style.borderColor='var(--line)'">
            <i class="fa-solid <?= e($link['icon']) ?>"></i>
            <?= e($link['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
