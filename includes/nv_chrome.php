<?php
/**
 * NEO V-TRACK shared top-header chrome.
 *
 * Usage (after session_start + auth check):
 *   $nv_active = 'staff'; // dashboard | search | staff | student | visitor | contractor | users | admin | reports | bulk
 *   include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php';
 *
 * Optional overrides set before include:
 *   $nv_admin_display, $nv_admin_role, $nv_lang, $nv_show_lang_switcher
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nv_active = $nv_active ?? '';
$nv_lang   = $nv_lang ?? ($_SESSION['language'] ?? 'bm');
$nv_show_lang_switcher = $nv_show_lang_switcher ?? true;
$nv_admin_role = $nv_admin_role ?? ($nv_lang === 'bm' ? 'Pentadbir' : 'Administrator');

if (!isset($nv_admin_display)) {
    $nv_admin_display = $_SESSION['email_Admin'] ?? ($_SESSION['email'] ?? 'guest');
    $nv_admin_display = strstr($nv_admin_display, '@', true) ?: $nv_admin_display;
}

$nv_t = $nv_lang === 'bm' ? [
    'dashboard' => 'Anjung',
    'search'    => 'Carian',
    'staff'     => 'Staf',
    'student'   => 'Pelajar',
    'visitor'   => 'Pelawat',
    'contractor'=> 'Kontraktor',
    'users'     => 'Pengguna',
    'admin'     => 'Pentadbir',
    'reports'   => 'Laporan',
    'bulk'      => 'Import',
    'logout'    => 'Log keluar',
    'profile'   => 'Profil',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
] : [
    'dashboard' => 'Dashboard',
    'search'    => 'Search',
    'staff'     => 'Staff',
    'student'   => 'Student',
    'visitor'   => 'Visitor',
    'contractor'=> 'Contractor',
    'users'     => 'Users',
    'admin'     => 'Admins',
    'reports'   => 'Reports',
    'bulk'      => 'Import',
    'logout'    => 'Sign out',
    'profile'   => 'Profile',
    'logout_confirm' => 'Sign out of NEO V-TRACK?',
];

$nv_avatar_letter = strtoupper(substr($nv_admin_display, 0, 1));

function nv_item($slug, $href, $lucide, $label, $active) {
    $cls = 'nv-nav-item' . ($active === $slug ? ' active' : '');
    echo "<a class='$cls' href='" . htmlspecialchars($href) . "'><i data-lucide='" . htmlspecialchars($lucide) . "'></i><span>" . htmlspecialchars($label) . "</span></a>";
}
?>
<header class="nv-header">
    <div class="nv-header-row">
        <div class="nv-brand">
            <img class="nv-uitm" src="/assets/images/uitm-logo-white.png" alt="UiTM">
            <img class="nv-neo"  src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
        </div>
        <div class="nv-wordmark">
            <span class="nv-name">NEO <span class="y">V-TRACK</span></span>
            <span class="nv-sub">UiTM Cawangan Johor</span>
        </div>

        <div class="nv-spacer"></div>

        <?php if ($nv_show_lang_switcher): ?>
            <a href="?lang=bm" class="nv-btn nv-btn-ghost" style="<?php echo $nv_lang === 'bm' ? 'background:var(--brand-yellow);color:var(--neutral-900);border-color:var(--brand-yellow);' : ''; ?>padding:5px 10px;font-size:12px;">BM</a>
            <a href="?lang=en" class="nv-btn nv-btn-ghost" style="<?php echo $nv_lang === 'en' ? 'background:var(--brand-yellow);color:var(--neutral-900);border-color:var(--brand-yellow);' : ''; ?>padding:5px 10px;font-size:12px;">EN</a>
        <?php endif; ?>

        <div class="nv-who">
            <a href="/auth/profile.php" class="nv-who-link" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;" title="<?php echo htmlspecialchars($nv_t['profile']); ?>">
                <div class="nv-avatar"><?php echo htmlspecialchars($nv_avatar_letter); ?></div>
                <div class="hide-on-mobile">
                    <div class="nv-who-name"><?php echo htmlspecialchars($nv_admin_display); ?></div>
                    <div class="nv-who-role"><?php echo htmlspecialchars($nv_admin_role); ?></div>
                </div>
            </a>
            <a href="?logout=1" class="nv-btn nv-btn-ghost" style="padding:7px 12px;margin-left:8px;" onclick="return confirm('<?php echo addslashes($nv_t['logout_confirm']); ?>')" title="<?php echo $nv_t['logout']; ?>">
                <i data-lucide="log-out"></i>
            </a>
        </div>
    </div>
</header>

<nav class="nv-nav">
    <div class="nv-nav-row">
        <?php
        // Role-aware home + search so users land on their own pages, not the admin ones.
        $nv_is_admin = isset($_SESSION['email_Admin']) && !empty($_SESSION['email_Admin']);
        nv_item('dashboard',  $nv_is_admin ? '/index.php' : '/admin/index_user.php', 'layout-dashboard', $nv_t['dashboard'], $nv_active);
        nv_item('search',     $nv_is_admin ? '/search/car_admin.php' : '/search/car_user.php', 'search', $nv_t['search'], $nv_active);
        nv_item('staff',      '/vehicles/staff/list.php',         'user-cog',         $nv_t['staff'],      $nv_active);
        nv_item('student',    '/vehicles/student/list.php',       'graduation-cap',   $nv_t['student'],    $nv_active);
        nv_item('visitor',    '/vehicles/visitor/list.php',       'user-round',       $nv_t['visitor'],    $nv_active);
        nv_item('contractor', '/vehicles/contractor/list.php',    'hard-hat',         $nv_t['contractor'], $nv_active);

        // Admin-only sections (view permission): users / admins / reports / import.
        if ($nv_is_admin) {
            nv_item('users',      '/admin/users.php',                 'users',            $nv_t['users'],      $nv_active);
            nv_item('admin',      '/admin/admins.php',             'shield-check',     $nv_t['admin'],      $nv_active);
            nv_item('reports',    '/admin/reports.php',               'file-text',        $nv_t['reports'],    $nv_active);
            nv_item('bulk',       '/admin/bulk_import.php',           'upload-cloud',     $nv_t['bulk'],       $nv_active);
        }
        ?>
        <span class="nv-nav-pin"><span class="nv-dot"></span> UiTM Segamat</span>
    </div>
</nav>
