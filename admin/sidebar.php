<?php
// Tentukan halaman aktif berdasarkan nama fail semasa
$current_page = basename($_SERVER['PHP_SELF']);

// Kira bilangan tempahan pending untuk badge
$badge_pending = 0;
if (isset($conn)) {
    $pending_q = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status = 'Pending'");
    if ($pending_q) $badge_pending = $pending_q->fetch_assoc()['c'] ?? 0;
}

// Admin name from session
$admin_name_display = $_SESSION['full_name'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name_display, 0, 1));

// Nav items definition
$nav_items = [
    [
        'label' => 'Main',
        'items' => [
            ['file' => 'admin_dashboard.php', 'icon' => 'fas fa-th-large', 'label' => 'Dashboard'],
        ]
    ],
    [
        'label' => 'Bookings',
        'items' => [
            ['file' => 'manage_bookings.php', 'icon' => 'fas fa-calendar-check', 'label' => 'All Bookings', 'badge' => $badge_pending],
            ['file' => 'view_payments.php',   'icon' => 'fas fa-receipt',         'label' => 'Payment Verify'],
        ]
    ],
    [
        'label' => 'Catalog',
        'items' => [
            ['file' => 'manage_packages.php', 'icon' => 'fas fa-box-open',  'label' => 'Packages'],
            ['file' => 'view_gallery.php',    'icon' => 'fas fa-images',    'label' => 'Gallery'],
        ]
    ],
    [
        'label' => 'People',
        'items' => [
            ['file' => 'view_users.php',  'icon' => 'fas fa-users',      'label' => 'Users'],
            ['file' => 'edit_profile.php','icon' => 'fas fa-user-shield', 'label' => 'My Profile'],
        ]
    ],
    [
        'label' => 'Analytics',
        'items' => [
            ['file' => 'reports_analysis.php', 'icon' => 'fas fa-chart-line', 'label' => 'Reports'],
            ['file' => 'admin_log.php',         'icon' => 'fas fa-history',    'label' => 'Activity Log'],
        ]
    ],
];
?>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="admin_dashboard.php" class="sidebar-brand-logo">
            <div class="sidebar-brand-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="sidebar-brand-text">
                <h2>EasyStay</h2>
                <span>Admin Portal</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_items as $section): ?>
            <div class="sidebar-section-label"><?= $section['label'] ?></div>
            <?php foreach ($section['items'] as $item): 
                $is_active = ($current_page === $item['file']);
                $has_badge = !empty($item['badge']) && $item['badge'] > 0;
            ?>
                <a href="<?= $item['file'] ?>" class="sidebar-item <?= $is_active ? 'active' : '' ?>">
                    <div class="sidebar-item-icon">
                        <i class="<?= $item['icon'] ?>"></i>
                    </div>
                    <span><?= $item['label'] ?></span>
                    <?php if ($has_badge): ?>
                        <span class="sidebar-badge"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex; align-items:center; gap:8px; padding: 8px 0 12px; border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 10px;">
            <div class="topbar-avatar" style="background: rgba(255,255,255,0.08); color: var(--slate-300);">
                <?= $admin_initial ?>
            </div>
            <div>
                <div style="font-size:0.78rem; font-weight:700; color: var(--white);"><?= htmlspecialchars($admin_name_display) ?></div>
                <div style="font-size:0.65rem; color: var(--slate-500); font-weight:500;">Administrator</div>
            </div>
        </div>
        <a href="../logout.php" class="sidebar-logout-btn" onclick="return confirm('Confirm logout?');">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
