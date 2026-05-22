<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = mysqli_prepare($con, "SELECT * FROM vehicle_reports WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$report = mysqli_fetch_assoc($result);

if (!$report) {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
    ?>
    <link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
    <link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
    <link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
    <body>
    <div class="nv-shell">
    <?php $nv_active = 'reports'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
    <main class="page">
      <div class="flash bad"><i data-lucide="alert-triangle"></i> Report not found. <a href="/admin/reports.php">Back to list</a></div>
    <?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
    <?php
    exit;
}

$photos = json_decode($report['photo_paths'] ?? '[]', true) ?: [];
$mapEmbed = 'https://maps.google.com/maps?q=' . urlencode($report['latitude'] . ',' . $report['longitude']) . '&z=17&output=embed';
$mapLink  = 'https://www.google.com/maps?q=' . urlencode($report['latitude'] . ',' . $report['longitude']);

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'reports'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow">Laporan</span>
      <h1>Report #<?= (int)$report['id'] ?></h1>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/reports.php"><i data-lucide="arrow-left"></i> Back to list</a>
      <a class="btn btn-ghost text-danger" href="/admin/delete_report.php?id=<?= (int)$report['id'] ?>" onclick="return confirm('Padam laporan #<?= (int)$report['id'] ?>?')"><i data-lucide="trash-2"></i> Delete</a>
    </div>
  </div>

  <div class="nv-grid cols-2">
    <div class="card">
      <span class="eyebrow">Record</span>
      <div class="kv mt-4">
        <div class="k">Submitted</div><div class="v"><?= htmlspecialchars(date('d M Y, H:i', strtotime($report['created_at']))) ?></div>
        <div class="k">Plate</div><div class="v"><span class="plate"><?= htmlspecialchars($report['plate_number']) ?></span></div>
        <div class="k">Reporter</div><div class="v"><?= htmlspecialchars($report['reporter_name']) ?> <span class="text-muted">(<?= htmlspecialchars($report['reporter_role']) ?>)</span></div>
        <div class="k">Reporter email</div><div class="v"><?= htmlspecialchars($report['reporter_email'] ?: '—') ?></div>
        <div class="k">Vehicle type</div><div class="v"><?= htmlspecialchars($report['vehicle_type'] ?: '—') ?></div>
        <div class="k">Owner</div><div class="v"><?= htmlspecialchars($report['owner_name'] ?: '—') ?></div>
        <div class="k">Phone</div><div class="v"><?= htmlspecialchars($report['phone'] ?: '—') ?></div>
        <div class="k">ID number</div><div class="v"><?= htmlspecialchars($report['id_number'] ?: '—') ?></div>
        <div class="k">Vehicle status</div><div class="v"><?= htmlspecialchars($report['vehicle_status'] ?: '—') ?></div>
      </div>
    </div>

    <div class="card">
      <span class="eyebrow">Activity</span>
      <div class="timeline mt-4">
        <div class="ev">
          <div class="ev-time"><?= htmlspecialchars(date('d M Y, H:i', strtotime($report['created_at']))) ?></div>
          <div class="ev-title">Report submitted</div>
          <div class="ev-meta">By <?= htmlspecialchars($report['reporter_name']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-6">
    <span class="eyebrow">Offense details</span>
    <p class="mt-2"><?= nl2br(htmlspecialchars($report['offense_details'])) ?></p>
  </div>

  <div class="card mt-6">
    <div class="nv-row between">
      <span class="eyebrow">Location</span>
      <a href="<?= $mapLink ?>" target="_blank" rel="noopener"><i data-lucide="external-link" style="width:14px;height:14px;vertical-align:-2px;"></i> <?= htmlspecialchars($report['latitude'] . ', ' . $report['longitude']) ?></a>
    </div>
    <iframe class="mt-4" style="width:100%;height:380px;border:0;border-radius:12px;" src="<?= $mapEmbed ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>

  <div class="card mt-6">
    <span class="eyebrow">Photos (<?= count($photos) ?>)</span>
    <div class="mt-4" style="display:flex;flex-wrap:wrap;gap:10px;">
      <?php if (empty($photos)): ?>
        <p class="text-muted">No photos attached.</p>
      <?php else: foreach ($photos as $p):
          $src = htmlspecialchars($p);
      ?>
        <a href="<?= $src ?>" target="_blank" rel="noopener"><img src="<?= $src ?>" alt="Report photo" style="width:180px;height:180px;object-fit:cover;border-radius:12px;border:1px solid var(--border);"></a>
      <?php endforeach; endif; ?>
    </div>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
