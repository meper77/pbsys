<?php
session_start();

if (isset($_GET['logout'])) {
    header('Location: /auth/logout.php');
    exit();
}

if (!isset($_SESSION['email'])) {
    header('location:/auth/login.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chart.php';

$userName = $_SESSION['nama'] ?? 'Pengguna';

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pengguna',
    'title'   => 'Anjung pengguna',
    'welcome' => 'Selamat datang',
    'staff' => 'Kenderaan staf',
    'student' => 'Kenderaan pelajar',
    'visitor' => 'Kenderaan pelawat',
    'contractor' => 'Kenderaan kontraktor',
    'alumni' => 'Kenderaan pesara',
    'view' => 'Lihat',
    'search' => 'Cari kenderaan',
    'timeLabel' => 'Masa',
    'dateLabel' => 'Tarikh',
] : [
    'eyebrow' => 'User',
    'title'   => 'User dashboard',
    'welcome' => 'Welcome',
    'staff' => 'Staff vehicles',
    'student' => 'Student vehicles',
    'visitor' => 'Visitor vehicles',
    'contractor' => 'Contractor vehicles',
    'alumni' => 'Alumni vehicles',
    'view' => 'View',
    'search' => 'Search vehicle',
    'timeLabel' => 'Time',
    'dateLabel' => 'Date',
];

date_default_timezone_set('Asia/Kuala_Lumpur');
if ($lang == 'bm') {
    $hari = ['Ahad','Isnin','Selasa','Rabu','Khamis','Jumaat','Sabtu'];
    $bulan = ['Januari','Februari','Mac','April','Mei','Jun','Julai','Ogos','September','Oktober','November','Disember'];
} else {
    $hari = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $bulan = ['January','February','March','April','May','June','July','August','September','October','November','December'];
}
$today = $hari[date('w')] . ', ' . date('j') . ' ' . $bulan[date('n')-1] . ' ' . date('Y');

$dataStatus = [
    ['Staf','staff','user-cog'],
    ['Pelajar','student','graduation-cap'],
    ['Pelawat','visitor','user-round'],
    ['Kontraktor','contractor','hard-hat'],
    ['Pesara','alumni','award'],
];

// Year-scoped metrics + monthly chart (one selector drives both).
$cy      = (isset($_GET['cy']) && ctype_digit($_GET['cy'])) ? (int) $_GET['cy'] : (int) date('Y');
$cyYears = nv_owner_years($con, $cy);
$yc      = nv_owner_year_counts($con, $cy);
$uMonths = $lang === 'bm'
    ? [1=>'Jan',2=>'Feb',3=>'Mac',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ogo',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dis']
    : [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<body>
<div class="nv-shell">
<?php
$nv_active = 'dashboard';
$nv_admin_display = $userName;
$nv_admin_role = $lang === 'bm' ? 'Pengguna' : 'User';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php';
?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['welcome']) ?>, <?= htmlspecialchars($userName) ?></h1>
      <div class="sub text-mono"><?= htmlspecialchars($today) ?></div>
    </div>
    <div class="actions">
      <form method="GET" class="actions" style="margin:0;gap:6px;">
        <label class="text-mono" style="font-size:12px;color:var(--fg-3);align-self:center;"><?= $lang === 'bm' ? 'Tahun' : 'Year' ?></label>
        <select name="cy" class="select" onchange="this.form.submit()" style="min-width:110px;">
          <?php foreach ($cyYears as $yy): ?><option value="<?= $yy ?>" <?= $yy === $cy ? 'selected' : '' ?>><?= $yy ?></option><?php endforeach; ?>
        </select>
      </form>
      <a class="btn btn-primary" href="/search/car_user.php"><i data-lucide="search"></i> <?= htmlspecialchars($t['search']) ?></a>
    </div>
  </div>

  <div class="nv-grid cols-2">
    <div class="kpi dark">
      <div class="lbl"><?= htmlspecialchars($t['timeLabel']) ?></div>
      <div class="val" id="realTimeClock" style="font-family:var(--font-mono);letter-spacing:0.06em;">00:00:00</div>
    </div>
    <div class="kpi">
      <div class="lbl"><?= htmlspecialchars($t['dateLabel']) ?></div>
      <div class="val" id="realTimeDate" style="font-size:24px;"><?= htmlspecialchars($today) ?></div>
    </div>
  </div>

  <div class="eyebrow mt-6" style="margin-bottom:8px;"><?= ($lang === 'bm' ? 'Metrik · ' : 'Metrics · ') . $cy ?></div>
  <div class="kpi-grid">
    <div class="kpi signal">
      <div class="lbl"><i data-lucide="car" style="width:14px;height:14px;vertical-align:-2px;"></i> <?= $lang === 'bm' ? 'Jumlah kenderaan' : 'Total vehicles' ?></div>
      <div class="val"><?= number_format($yc['total']) ?></div>
    </div>
    <?php foreach ($dataStatus as $d):
      $status = $d[0]; $key = $d[1]; $icon = $d[2];
      $count = (int) ($yc[$status] ?? 0);
    ?>
    <a href="/search/car_user.php?status=<?= urlencode($status) ?>" style="text-decoration:none;color:inherit;">
      <div class="kpi">
        <div class="lbl"><i data-lucide="<?= $icon ?>" style="width:14px;height:14px;vertical-align:-2px;"></i> <?= htmlspecialchars($t[$key]) ?></div>
        <div class="val"><?= number_format($count) ?></div>
        <div class="delta"><?= htmlspecialchars($t['view']) ?> &rarr;</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

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
      'Staf'       => ['label' => ($lang === 'bm' ? 'Staf' : 'Staff'),            'color' => '#6b21a8'],
      'Pelajar'    => ['label' => ($lang === 'bm' ? 'Pelajar' : 'Student'),       'color' => '#f5c518'],
      'Pelawat'    => ['label' => ($lang === 'bm' ? 'Pelawat' : 'Visitor'),       'color' => '#0ea5e9'],
      'Kontraktor' => ['label' => ($lang === 'bm' ? 'Kontraktor' : 'Contractor'), 'color' => '#16a34a'],
      'Pesara'     => ['label' => ($lang === 'bm' ? 'Pesara' : 'Alumni'),     'color' => '#ef4444'],
    ],
    'months'   => $uMonths,
    'lump'     => '',
    'title'    => ($lang === 'bm' ? 'Pendaftaran bulanan mengikut kategori' : 'Monthly registrations by category'),
    'sub'      => ($lang === 'bm' ? 'Jumlah setiap kategori setiap bulan' : 'Each category per month'),
    'empty'    => ($lang === 'bm' ? 'Tiada data untuk tahun ini.' : 'No data for this year.'),
  ]);
  ?>

  <script>
  function updateRealTimeClock(){
    var now = new Date();
    var hh = String(now.getHours()).padStart(2,'0');
    var mm = String(now.getMinutes()).padStart(2,'0');
    var ss = String(now.getSeconds()).padStart(2,'0');
    var el = document.getElementById('realTimeClock');
    if (el) el.textContent = hh + ':' + mm + ':' + ss;
  }
  updateRealTimeClock();
  setInterval(updateRealTimeClock, 1000);
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
<?php mysqli_close($con); ?>
