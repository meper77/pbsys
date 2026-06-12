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
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_guard_page($con, 'search');   // per-user page access (permission control)
include $_SERVER['DOCUMENT_ROOT'].'/includes/search_backend.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/lang_switch.php';   // handles ?lang=, sets $lang

$userName = $_SESSION['nama'] ?? ($_SESSION['name'] ?? 'Pengguna');

$t = $lang === 'bm' ? [
    'eyebrow' => 'Carian Kenderaan',
    'title'   => 'Cari kenderaan',
    'sub'     => 'Cari kenderaan berdaftar mengikut plat, nama pemilik atau nombor ID.',
    'placeholder' => 'Masukkan no. plat, nama, atau no. ID',
    'search'  => 'Cari',
    'clear'   => 'Kosongkan',
    'quick'   => 'Carian pantas',
    'f_staff' => 'Staf', 'f_student' => 'Pelajar', 'f_visitor' => 'Pelawat', 'f_contractor' => 'Kontraktor', 'f_all' => 'Semua',
    'results' => 'Hasil carian', 'records' => 'rekod', 'export' => 'Eksport Excel', 'again' => 'Cari lagi',
    'col_no' => 'No.', 'col_status' => 'Status', 'col_id' => 'No. ID', 'col_name' => 'Nama', 'col_phone' => 'Telefon', 'col_plate' => 'Plat', 'col_type' => 'Jenis',
    'none_title' => 'Tiada padanan', 'none_sub' => 'Cuba sebahagian plat atau nama.',
    'idle_title' => 'Mulakan carian anda', 'idle_sub' => 'Maklumat kenderaan akan dipaparkan di sini.',
] : [
    'eyebrow' => 'Vehicle Search',
    'title'   => 'Search vehicles',
    'sub'     => 'Look up registered vehicles by plate, owner name, or ID number.',
    'placeholder' => 'Enter plate, name, or ID',
    'search'  => 'Search',
    'clear'   => 'Clear',
    'quick'   => 'Quick filters',
    'f_staff' => 'Staff', 'f_student' => 'Student', 'f_visitor' => 'Visitor', 'f_contractor' => 'Contractor', 'f_all' => 'All',
    'results' => 'Search results', 'records' => 'records', 'export' => 'Export Excel', 'again' => 'Search again',
    'col_no' => 'No.', 'col_status' => 'Status', 'col_id' => 'ID No.', 'col_name' => 'Name', 'col_phone' => 'Phone', 'col_plate' => 'Plate', 'col_type' => 'Type',
    'none_title' => 'No match', 'none_sub' => 'Check the plate or try a partial name.',
    'idle_title' => 'Start your search', 'idle_sub' => 'Vehicle details will appear here.',
];

$search   = isset($_POST['search']) ? trim($_POST['search']) : '';
$status   = isset($_GET['status']) ? trim($_GET['status']) : '';
$showAll  = isset($_GET['showAll']) && $_GET['showAll'] === 'true';

$results = [];
$hasResults = false;
if (isset($_POST['submit']) && $search !== '') {
    $results = searchVehicleRecords($con, $search)['data'];
    $hasResults = true;
} elseif ($status !== '') {
    $results = searchVehicleRecords($con, '', $status, false)['data'];
    $hasResults = true;
} elseif ($showAll) {
    $results = searchVehicleRecords($con, '', '', true)['data'];
    $hasResults = true;
}

$langQ = $lang === 'en' ? '&lang=en' : '';

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php
$nv_active = 'search';
$nv_admin_display = $userName;
$nv_admin_role = $lang === 'bm' ? 'Pengguna' : 'User';
include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php';
?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
      <p class="sub"><?= htmlspecialchars($t['sub']) ?></p>
    </div>
  </div>

  <form class="card nv-stack" method="POST">
    <div class="field">
      <label class="field-label" for="search"><?= htmlspecialchars($t['col_plate']) ?> / <?= htmlspecialchars($t['col_name']) ?> / <?= htmlspecialchars($t['col_id']) ?></label>
      <input class="input mono" id="search" name="search" type="text" placeholder="<?= htmlspecialchars($t['placeholder']) ?>" value="<?= htmlspecialchars($search) ?>" required autofocus data-nv-suggest="any" data-nv-submit data-nv-field="plate">
    </div>
    <div class="nv-row end gap-2">
      <a class="btn btn-ghost" href="/search/car_user.php<?= $lang === 'en' ? '?lang=en' : '' ?>"><?= htmlspecialchars($t['clear']) ?></a>
      <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="search"></i> <?= htmlspecialchars($t['search']) ?></button>
    </div>
  </form>

  <div class="nv-row gap-2 mt-4" style="flex-wrap:wrap;align-items:center;">
    <span class="text-muted" style="font-size:13px;"><?= htmlspecialchars($t['quick']) ?>:</span>
    <?php foreach ([['Staf','f_staff'],['Pelajar','f_student'],['Pelawat','f_visitor'],['Kontraktor','f_contractor']] as $f): ?>
      <a class="btn btn-ghost<?= $status === $f[0] ? ' btn-primary' : '' ?>" style="padding:6px 14px;font-size:13px;" href="/search/car_user.php?status=<?= $f[0] . $langQ ?>"><?= htmlspecialchars($t[$f[1]]) ?></a>
    <?php endforeach; ?>
    <a class="btn btn-ghost<?= $showAll ? ' btn-primary' : '' ?>" style="padding:6px 14px;font-size:13px;" href="/search/car_user.php?showAll=true<?= $langQ ?>"><?= htmlspecialchars($t['f_all']) ?></a>
  </div>

  <?php if ($hasResults && count($results) > 0): ?>
  <div class="page-head mt-6">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['results']) ?></span>
      <h2 class="text-display"><?= count($results) ?> <?= htmlspecialchars($t['records']) ?></h2>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/search/car_user.php<?= $lang === 'en' ? '?lang=en' : '' ?>"><i data-lucide="rotate-ccw"></i> <?= htmlspecialchars($t['again']) ?></a>
    </div>
  </div>
  <div class="card flat">
    <table class="table" id="vehicleTable">
      <thead>
        <tr>
          <th style="width:50px;"><?= htmlspecialchars($t['col_no']) ?></th>
          <th style="width:120px;"><?= htmlspecialchars($t['col_status']) ?></th>
          <th><?= htmlspecialchars($t['col_id']) ?></th>
          <th><?= htmlspecialchars($t['col_name']) ?></th>
          <th><?= htmlspecialchars($t['col_phone']) ?></th>
          <th><?= htmlspecialchars($t['col_plate']) ?></th>
          <th><?= htmlspecialchars($t['col_type']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($results as $row):
          $sraw = strtolower($row['status'] ?? '');
          $tone = 'neutral'; $stext = $row['status'] ?? '';
          if (in_array($sraw, ['staf','staff'])) { $tone='info'; $stext = $lang==='bm'?'Staf':'Staff'; }
          elseif (in_array($sraw, ['pelajar','student'])) { $tone='info'; $stext = $lang==='bm'?'Pelajar':'Student'; }
          elseif (in_array($sraw, ['pelawat','visitor'])) { $tone='warn'; $stext = $lang==='bm'?'Pelawat':'Visitor'; }
          elseif (in_array($sraw, ['kontraktor','contractor'])) { $tone='ok'; $stext = $lang==='bm'?'Kontraktor':'Contractor'; }
        ?>
        <tr>
          <td class="meta"><?= $no++ ?></td>
          <td><span class="pill <?= $tone ?>"><span class="dot"></span> <?= htmlspecialchars($stext) ?></span></td>
          <td><?= htmlspecialchars($row['idnumber'] ?? '') ?></td>
          <td><strong><?= htmlspecialchars($row['name'] ?? '') ?></strong></td>
          <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
          <td><span class="plate"><?= htmlspecialchars(strtoupper($row['platenum'] ?? '')) ?></span></td>
          <td><?= htmlspecialchars($row['type'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php elseif ($hasResults): ?>
  <div class="card flat mt-6 text-center" style="padding:40px;">
    <span class="eyebrow"><?= htmlspecialchars($t['none_title']) ?></span>
    <h3 class="text-display" style="margin-top:8px;"><?= htmlspecialchars($t['none_title']) ?></h3>
    <p class="text-muted"><?= htmlspecialchars($t['none_sub']) ?></p>
    <div class="nv-row" style="justify-content:center;margin-top:16px;">
      <a class="btn btn-primary" href="/search/car_user.php<?= $lang === 'en' ? '?lang=en' : '' ?>"><i data-lucide="rotate-ccw"></i> <?= htmlspecialchars($t['again']) ?></a>
    </div>
  </div>
  <?php else: ?>
  <div class="card flat mt-6 text-center" style="padding:40px;">
    <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
    <h3 class="text-display" style="margin-top:8px;"><?= htmlspecialchars($t['idle_title']) ?></h3>
    <p class="text-muted"><?= htmlspecialchars($t['idle_sub']) ?></p>
  </div>
  <?php endif; ?>
</main>
</div>

<style>
/* Consistent pagination hover (previous/next match the numbered buttons). */
.dataTables_paginate .paginate_button { cursor: pointer; border-radius: 6px; }
.dataTables_paginate .paginate_button:hover {
  background: var(--brand-yellow, #f5c518) !important;
  color: #1a1a1a !important;
  border-color: var(--brand-yellow, #f5c518) !important;
}
.dataTables_paginate .paginate_button.current,
.dataTables_paginate .paginate_button.current:hover {
  background: var(--accent, #6b21a8) !important;
  color: #fff !important;
  border-color: var(--accent, #6b21a8) !important;
}
.dataTables_paginate .paginate_button.disabled,
.dataTables_paginate .paginate_button.disabled:hover {
  background: transparent !important; color: var(--fg-3, #999) !important;
  border-color: transparent !important; cursor: default;
}
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  var table = $('#vehicleTable');
  if (table.length) {
    table.DataTable({ pageLength: 10, order: [[0, 'asc']], autoWidth: false, dom: 'rtip' });
  }
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
