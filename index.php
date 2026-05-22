<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// LANGUAGE SYSTEM
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$lang = $_SESSION['language'];

$text = [];

$text['bm'] = [
    'page_title' => 'Anjung',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading' => 'Selamat datang ke NEO V-TRACK',
    'subhead' => 'Pilih ruang kerja anda untuk meneruskan.',
    'admin_title' => 'Panel pentadbir',
    'admin_desc' => 'Statistik kenderaan, pengurusan pengguna dan laporan.',
    'search_title' => 'Carian kenderaan',
    'search_desc' => 'Cari rekod pas kenderaan staf, pelajar, pelawat dan kontraktor.',
    'logout' => 'Log keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'total_users' => 'Jumlah pengguna berdaftar',
    'total_vehicles' => 'Jumlah kenderaan',
    'staff_vehicles' => 'Kenderaan staf',
    'student_vehicles' => 'Kenderaan pelajar',
    'visitor_vehicles' => 'Kenderaan pelawat',
    'contractor_vehicles' => 'Kenderaan kontraktor',
    'signed_in_as' => 'Log masuk sebagai',
    'brand_sub' => 'Anjung'
];

$text['en'] = [
    'page_title' => 'Home',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading' => 'Welcome to NEO V-TRACK',
    'subhead' => 'Pick your workspace to continue.',
    'admin_title' => 'Admin panel',
    'admin_desc' => 'Vehicle statistics, user management and reports.',
    'search_title' => 'Vehicle search',
    'search_desc' => 'Search vehicle pass records for staff, students, visitors and contractors.',
    'logout' => 'Log out',
    'logout_confirm' => 'Are you sure you want to log out?',
    'total_users' => 'Registered users',
    'total_vehicles' => 'Total vehicles',
    'staff_vehicles' => 'Staff vehicles',
    'student_vehicles' => 'Student vehicles',
    'visitor_vehicles' => 'Visitor vehicles',
    'contractor_vehicles' => 'Contractor vehicles',
    'signed_in_as' => 'Signed in as',
    'brand_sub' => 'Home'
];

$t = $text[$lang];

// Get admin display name
$admin_email = $_SESSION['email_Admin'];
$admin_display = $admin_email;

$admin_query = @mysqli_query($con, "SELECT name FROM admin WHERE email = '$admin_email'");
if ($admin_query && mysqli_num_rows($admin_query) > 0) {
    $admin_data = mysqli_fetch_assoc($admin_query);
    if (!empty($admin_data['name'])) {
        $admin_display = $admin_data['name'];
    } else {
        $admin_display = strstr($admin_email, '@', true) ?: $admin_email;
    }
}

// Vehicle totals per category
$category_map = ['staff' => 'Staf', 'student' => 'Pelajar', 'visitor' => 'Pelawat', 'contractor' => 'Kontraktor'];
$counts = ['staff' => 0, 'student' => 0, 'visitor' => 0, 'contractor' => 0, 'total' => 0];
foreach ($category_map as $slug => $category) {
    $result = @mysqli_query($con, "SELECT COUNT(*) as count FROM owner WHERE status = '$category'");
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $counts[$slug] = (int)($data['count'] ?? 0);
        $counts['total'] += $counts[$slug];
    }
}
$total_vehicles = $counts['total']; // back-compat

// Total users
$total_users_count = 0;
$total_users_query = @mysqli_query($con, "SELECT COUNT(*) as total FROM user");
if ($total_users_query && mysqli_num_rows($total_users_query) > 0) {
    $total_users_data = mysqli_fetch_assoc($total_users_query);
    $total_users_count = (int)($total_users_data['total'] ?? 0);
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'dashboard'; $nv_admin_display = $admin_display; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['heading']) ?></h1>
      <p class="sub"><?= htmlspecialchars($t['subhead']) ?></p>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/search/car_admin.php"><i data-lucide="search"></i> <?= htmlspecialchars($t['search_title']) ?></a>
      <a class="btn btn-primary" href="/admin/dashboard.php"><i data-lucide="shield-check"></i> <?= htmlspecialchars($t['admin_title']) ?></a>
    </div>
  </div>

  <div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="kpi signal">
      <div class="lbl"><?= htmlspecialchars($t['total_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['total']) ?></div>
    </div>
    <a class="kpi" href="/vehicles/staff/list.php" style="text-decoration:none;color:inherit;">
      <div class="lbl"><?= htmlspecialchars($t['staff_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['staff']) ?></div>
    </a>
    <a class="kpi" href="/vehicles/student/list.php" style="text-decoration:none;color:inherit;">
      <div class="lbl"><?= htmlspecialchars($t['student_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['student']) ?></div>
    </a>
    <a class="kpi" href="/vehicles/visitor/list.php" style="text-decoration:none;color:inherit;">
      <div class="lbl"><?= htmlspecialchars($t['visitor_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['visitor']) ?></div>
    </a>
    <a class="kpi" href="/vehicles/contractor/list.php" style="text-decoration:none;color:inherit;">
      <div class="lbl"><?= htmlspecialchars($t['contractor_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['contractor']) ?></div>
    </a>
  </div>
</main>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
