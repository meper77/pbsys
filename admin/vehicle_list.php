<?php
session_start();

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?type=' . ($_GET['type'] ?? ''));
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pentadbir',
    'page_title' => 'Senarai kenderaan',
    'back' => 'Kembali',
    'staff' => 'Staf', 'student' => 'Pelajar', 'visitor' => 'Pelawat', 'contractor' => 'Kontraktor',
    'no' => 'Bil.', 'name' => 'Nama', 'phone' => 'No. Telefon',
    'id_number' => 'No. Pengenalan', 'model' => 'Model', 'plate_number' => 'No. Plat',
    'active' => 'Aktif', 'removed' => 'Dibuang',
    'no_records' => 'Tiada rekod', 'company_name' => 'Syarikat',
    'total_records' => 'Jumlah rekod',
] : [
    'eyebrow' => 'Administration',
    'page_title' => 'Vehicle list',
    'back' => 'Back',
    'staff' => 'Staff', 'student' => 'Student', 'visitor' => 'Visitor', 'contractor' => 'Contractor',
    'no' => 'No.', 'name' => 'Name', 'phone' => 'Phone',
    'id_number' => 'ID number', 'model' => 'Model', 'plate_number' => 'Plate',
    'active' => 'Active', 'removed' => 'Removed',
    'no_records' => 'No records', 'company_name' => 'Company',
    'total_records' => 'Total records',
];

$type = mysqli_real_escape_string($con, $_GET['type'] ?? '');

if (empty($type) || !in_array($type, ['staff', 'student', 'visitor', 'contractor'])) {
    header('Location: /admin/superadmin.php');
    exit();
}

$title = $t[$type];

$vehicles = [];
if ($type === 'staff') {
    $q = "SELECT staffid, name, phone, staffno as id_number, model, platenum, created_at FROM staffcar ORDER BY created_at DESC";
} elseif ($type === 'student') {
    $q = "SELECT studentid, name, phone, matric as id_number, model, platenum, created_at FROM studentcar ORDER BY created_at DESC";
} elseif ($type === 'visitor') {
    $q = "SELECT visitorid, name, phone, ic_passport as id_number, model, platenum, created_at FROM visitorcar ORDER BY created_at DESC";
} else {
    $q = "SELECT contractorid, name, phone, ic_passport as id_number, company_name, model, platenum, created_at FROM contractorcar ORDER BY created_at DESC";
}
$result = @mysqli_query($con, $q);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) { $vehicles[] = $row; }
}
$total = count($vehicles);

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<body>
<div class="nv-shell">
<?php $nv_active = $type; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($title) ?> — <?= htmlspecialchars($t['page_title']) ?></h1>
      <div class="sub text-mono"><?= htmlspecialchars($t['total_records']) ?>: <?= $total ?></div>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/superadmin.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <div class="card">
    <?php if ($total > 0): ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
          <th><?= htmlspecialchars($t['name']) ?></th>
          <th><?= htmlspecialchars($t['phone']) ?></th>
          <th><?= htmlspecialchars($t['id_number']) ?></th>
          <?php if ($type === 'contractor'): ?><th><?= htmlspecialchars($t['company_name']) ?></th><?php endif; ?>
          <th><?= htmlspecialchars($t['model']) ?></th>
          <th><?= htmlspecialchars($t['plate_number']) ?></th>
          
          
          <th><?= htmlspecialchars($t['created_at']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vehicles as $i => $v): ?>
        <tr>
          <td class="meta"><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($v['name']) ?></td>
          <td class="meta"><?= htmlspecialchars($v['phone']) ?></td>
          <td class="meta"><?= htmlspecialchars($v['id_number']) ?></td>
          <?php if ($type === 'contractor'): ?><td><?= htmlspecialchars($v['company_name'] ?? '—') ?></td><?php endif; ?>
          <td><?= htmlspecialchars($v['model']) ?></td>
          <td><span class="plate"><?= htmlspecialchars($v['platenum']) ?></span></td>
          
          <td>
          </td>
          <td class="meta"><?= htmlspecialchars(date('d M Y, H:i', strtotime($v['created_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="flash info"><i data-lucide="info"></i> <?= htmlspecialchars($t['no_records']) ?></div>
    <?php endif; ?>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
