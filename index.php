<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    header('Location: /auth/logout.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chart.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/nv_category.php';
nv_require_login();
$nv_admin = nv_is_admin();

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
    'search_desc' => 'Cari rekod kenderaan staf, pelajar, pelawat dan kontraktor.',
    'logout' => 'Log keluar',
    'logout_confirm' => 'Adakah anda pasti ingin log keluar?',
    'total_users' => 'Jumlah pengguna berdaftar',
    'total_vehicles' => 'Jumlah kenderaan',
    'staff_vehicles' => 'Kenderaan staf',
    'student_vehicles' => 'Kenderaan pelajar',
    'visitor_vehicles' => 'Kenderaan pelawat',
    'contractor_vehicles' => 'Kenderaan kontraktor',
    'alumni_vehicles' => 'Kenderaan pesara',
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
    'alumni_vehicles' => 'Alumni vehicles',
    'signed_in_as' => 'Signed in as',
    'brand_sub' => 'Home'
];

$t = $text[$lang];

// Get admin display name
$admin_email = $_SESSION['email_Admin'] ?? $_SESSION['email'] ?? '';
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

// Year scope for the metrics + chart (one selector drives both).
$cy      = (isset($_GET['cy']) && ctype_digit($_GET['cy'])) ? (int) $_GET['cy'] : (int) date('Y');
$cyYears = nv_owner_years($con, $cy);

// Vehicle metrics per category + sum, scoped to the selected year.
$yc = nv_owner_year_counts($con, $cy);
$counts = [
    'staff'      => $yc['Staf'],
    'student'    => $yc['Pelajar'],
    'visitor'    => $yc['Pelawat'],
    'contractor' => $yc['Kontraktor'],
    'alumni'     => $yc['Pesara'],
    'total'      => $yc['total'],
];
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
      <h1><?= htmlspecialchars($t['heading']) ?><?= $admin_display !== '' ? ', ' . htmlspecialchars($admin_display) : '' ?></h1>
      <p class="sub"><?= htmlspecialchars($t['subhead']) ?></p>
    </div>
    <div class="actions">
      <form method="GET" class="actions" style="margin:0;gap:6px;">
        <label class="text-mono" style="font-size:12px;color:var(--fg-3);align-self:center;"><?= $lang === 'bm' ? 'Tahun' : 'Year' ?></label>
        <select name="cy" class="select" onchange="this.form.submit()" style="min-width:110px;">
          <?php foreach ($cyYears as $yy): ?><option value="<?= $yy ?>" <?= $yy === $cy ? 'selected' : '' ?>><?= $yy ?></option><?php endforeach; ?>
        </select>
      </form>
      <a class="btn btn-ghost" href="/search/car_admin.php"><i data-lucide="search"></i> <?= htmlspecialchars($t['search_title']) ?></a>
      <?php if ($nv_admin): ?>
      <a class="btn btn-primary" href="/admin/admins.php"><i data-lucide="shield-check"></i> <?= htmlspecialchars($t['admin_title']) ?></a>
      <?php endif; ?>
    </div>
  </div>

  <div class="eyebrow" style="margin:2px 0 8px;"><?= ($lang === 'bm' ? 'Metrik · ' : 'Metrics · ') . $cy ?></div>
  <div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
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
    <a class="kpi" href="/vehicles/alumni/list.php" style="text-decoration:none;color:inherit;">
      <div class="lbl"><?= htmlspecialchars($t['alumni_vehicles']) ?></div>
      <div class="val"><?= number_format($counts['alumni']) ?></div>
    </a>
  </div>

  <?php
  // Monthly registrations stacked by category, for the year chosen above.
  $cMonths = ($lang === 'bm')
    ? [1=>'Jan',2=>'Feb',3=>'Mac',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ogo',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dis']
    : [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
  ?>
  <div class="page-head mt-6" style="align-items:flex-end;">
    <div>
      <span class="eyebrow"><?= $lang === 'bm' ? 'Statistik' : 'Statistics' ?></span>
      <h2 class="text-display" style="margin-top:4px;"><?= ($lang === 'bm' ? 'Pendaftaran bulanan — ' : 'Monthly registrations — ') . $cy ?></h2>
    </div>
  </div>
  <?php
  echo nv_owner_chart_card($con, [
    'status'   => '',
    'year'     => $cy,
    'seriesBy' => 'status',
    'series'   => [
      'Staf'       => ['label' => ($lang === 'bm' ? 'Staf' : 'Staff'),        'color' => nv_category_color('Staf')],
      'Pelajar'    => ['label' => ($lang === 'bm' ? 'Pelajar' : 'Student'),   'color' => nv_category_color('Pelajar')],
      'Pelawat'    => ['label' => ($lang === 'bm' ? 'Pelawat' : 'Visitor'),   'color' => nv_category_color('Pelawat')],
      'Kontraktor' => ['label' => ($lang === 'bm' ? 'Kontraktor' : 'Contractor'), 'color' => nv_category_color('Kontraktor')],
      'Pesara'     => ['label' => ($lang === 'bm' ? 'Pesara' : 'Alumni'),     'color' => nv_category_color('Pesara')],
    ],
    'months'   => $cMonths,
    'lump'     => '',
    'title'    => ($lang === 'bm' ? 'Pendaftaran bulanan mengikut kategori' : 'Monthly registrations by category'),
    'sub'      => ($lang === 'bm' ? 'Jumlah setiap kategori setiap bulan' : 'Each category per month'),
    'empty'    => ($lang === 'bm' ? 'Tiada data untuk tahun ini.' : 'No data for this year.'),
  ]);
  ?>
</main>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
