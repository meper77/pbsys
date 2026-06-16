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
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';  // nv_can_access_page()

$nv_active = $nv_active ?? '';
$nv_lang   = $nv_lang ?? ($_SESSION['language'] ?? 'bm');
$nv_show_lang_switcher = $nv_show_lang_switcher ?? true;

// Reflect the signed-in person's REAL name + photo (and role) from the DB on every
// page, so a profile edit shows immediately in the header (not the email prefix).
$nv_is_admin_session = isset($_SESSION['email_Admin']) && !empty($_SESSION['email_Admin']);
$nv_email   = $nv_is_admin_session ? (string) $_SESSION['email_Admin'] : (string) ($_SESSION['email'] ?? '');
$nv_avatar_img = '';
$nv_real_name  = '';
if ($nv_email !== '' && ($nv_con = $GLOBALS['con'] ?? null)) {
    $nv_table = $nv_is_admin_session ? 'admin' : 'user';
    $nv_st = @$nv_con->prepare("SELECT name, profile_image FROM `$nv_table` WHERE email = ? LIMIT 1");
    if (!$nv_st) { $nv_st = @$nv_con->prepare("SELECT name FROM `$nv_table` WHERE email = ? LIMIT 1"); }
    if ($nv_st) {
        $nv_st->bind_param('s', $nv_email);
        if (@$nv_st->execute()) {
            $nv_row = $nv_st->get_result()->fetch_assoc();
            if ($nv_row) {
                if (!empty($nv_row['name']))          { $nv_real_name  = (string) $nv_row['name']; }
                if (!empty($nv_row['profile_image'])) { $nv_avatar_img = (string) $nv_row['profile_image']; }
            }
        }
        $nv_st->close();
    }
}

$nv_admin_role = $nv_admin_role ?? ($nv_is_admin_session
    ? ($nv_lang === 'bm' ? 'Pentadbir' : 'Administrator')
    : ($nv_lang === 'bm' ? 'Pengguna' : 'User'));

// Display name: real name, else any page-provided value, else the email prefix.
if ($nv_real_name !== '' || !isset($nv_admin_display)) {
    $nv_admin_display = $nv_real_name !== ''
        ? $nv_real_name
        : ($nv_email !== '' ? (strstr($nv_email, '@', true) ?: $nv_email) : 'guest');
}

$nv_t = $nv_lang === 'bm' ? [
    'dashboard' => 'Anjung',
    'search'    => 'Carian',
    'staff'     => 'Staf',
    'student'   => 'Pelajar',
    'visitor'   => 'Pelawat',
    'contractor'=> 'Kontraktor',
    'alumni'    => 'Pesara',
    'report'    => 'Lapor',
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
    'alumni'    => 'Alumni',
    'report'    => 'Report',
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


        <div class="nv-who">
            <a href="/auth/profile.php" class="nv-who-link" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;" title="<?php echo htmlspecialchars($nv_t['profile']); ?>">
                <div class="nv-avatar"<?php if ($nv_avatar_img !== ''): ?> style="background-image:url('<?php echo htmlspecialchars($nv_avatar_img, ENT_QUOTES); ?>');background-size:cover;background-position:center;background-color:transparent;"<?php endif; ?>><?php echo $nv_avatar_img !== '' ? '' : htmlspecialchars($nv_avatar_letter); ?></div>
                <div class="hide-on-mobile">
                    <div class="nv-who-name"><?php echo htmlspecialchars($nv_admin_display); ?></div>
                    <div class="nv-who-role"><?php echo htmlspecialchars($nv_admin_role); ?></div>
                </div>
            </a>
            <a href="/auth/logout.php" class="nv-btn nv-btn-ghost" style="padding:7px 12px;margin-left:8px;" onclick="return confirm('<?php echo addslashes($nv_t['logout_confirm']); ?>')" title="<?php echo $nv_t['logout']; ?>">
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
        $nv_con = $GLOBALS['con'] ?? null;
        $nv_see = function ($slug) use ($nv_con) { return nv_can_access_page($nv_con, $slug); };  // admin: all; user: per permission control
        nv_item('dashboard',  $nv_is_admin ? '/index.php' : '/admin/index_user.php', 'layout-dashboard', $nv_t['dashboard'], $nv_active);
        if ($nv_see('search'))     { nv_item('search',     $nv_is_admin ? '/search/car_admin.php' : '/search/car_user.php', 'search', $nv_t['search'], $nv_active); }
        if ($nv_see('staff'))      { nv_item('staff',      '/vehicles/staff/list.php',         'user-cog',         $nv_t['staff'],      $nv_active); }
        if ($nv_see('student'))    { nv_item('student',    '/vehicles/student/list.php',       'graduation-cap',   $nv_t['student'],    $nv_active); }
        if ($nv_see('visitor'))    { nv_item('visitor',    '/vehicles/visitor/list.php',       'user-round',       $nv_t['visitor'],    $nv_active); }
        if ($nv_see('contractor')) { nv_item('contractor', '/vehicles/contractor/list.php',    'hard-hat',         $nv_t['contractor'], $nv_active); }
        if ($nv_see('alumni'))     { nv_item('alumni',     '/vehicles/alumni/list.php',        'award',            $nv_t['alumni'],     $nv_active); }
        if ($nv_see('reports'))    { nv_item('reports',    '/admin/reports.php',               'file-text',        $nv_t['reports'],    $nv_active); }

        // Admin-only sections (view permission): users / admins.
        if ($nv_is_admin) {
            nv_item('users',      '/admin/users.php',                 'users',            $nv_t['users'],      $nv_active);
            nv_item('admin',      '/admin/admins.php',             'shield-check',     $nv_t['admin'],      $nv_active);
            // 'Import' standalone page retired: dedicated per-category import/export now lives
            // on the staff/student list pages (foundation.md import.md).
        }
        ?>
        <span class="nv-nav-pin"><span class="nv-dot"></span> UiTM Segamat</span>
    </div>
</nav>
