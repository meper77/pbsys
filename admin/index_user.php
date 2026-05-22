<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

if (!isset($_SESSION['email'])) {
    header('location:/auth/login.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

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
];

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

  <div class="kpi-grid mt-6">
    <?php foreach ($dataStatus as $d):
      $status = $d[0]; $key = $d[1]; $icon = $d[2];
      $q = mysqli_query($con, "SELECT * FROM owner WHERE status='$status'");
      $count = $q ? mysqli_num_rows($q) : 0;
    ?>
    <a href="/search/car_user.php?status=<?= urlencode($status) ?>" style="text-decoration:none;color:inherit;">
      <div class="kpi">
        <div class="lbl"><i data-lucide="<?= $icon ?>" style="width:14px;height:14px;vertical-align:-2px;"></i> <?= htmlspecialchars($t[$key]) ?></div>
        <div class="val"><?= $count ?></div>
        <div class="delta"><?= htmlspecialchars($t['view']) ?> &rarr;</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

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
