<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/lang_switch.php';

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
      <div class="flash bad"><i data-lucide="alert-triangle"></i> Laporan tidak ditemui. <a href="/admin/reports.php">Kembali ke senarai</a></div>
    <?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
    <?php
    exit;
}

$photos = json_decode($report['photo_paths'] ?? '[]', true) ?: [];

// Resolve a stored photo path to a working URL (new uploads live at /uploads/reports;
// older ones were saved under /api/upload/reports). Returns null if the file is gone.
function nv_report_photo_url(string $p): ?string {
    $doc = $_SERVER['DOCUMENT_ROOT'];
    if ($p !== '' && is_file($doc . $p)) { return $p; }
    $legacy = '/api/upload/reports/' . basename($p);
    if (is_file($doc . $legacy)) { return $legacy; }
    return null;
}
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
      <h1>Laporan #<?= (int)$report['id'] ?></h1>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/reports.php"><i data-lucide="arrow-left"></i> Kembali ke senarai</a>
      <form method="POST" action="/admin/delete_report.php" style="display:inline" onsubmit="return confirm('Padam laporan #<?= (int)$report['id'] ?>?')">
        <input type="hidden" name="ids[]" value="<?= (int)$report['id'] ?>">
        <button type="submit" class="btn btn-ghost text-danger"><i data-lucide="trash-2"></i> Padam</button>
      </form>
    </div>
  </div>

  <div class="nv-grid cols-2">
    <div class="card">
      <span class="eyebrow">Rekod</span>
      <div class="kv mt-4">
        <div class="k">Dihantar</div><div class="v"><?= htmlspecialchars(date('d M Y, H:i', strtotime($report['created_at']))) ?></div>
        <div class="k">Plat</div><div class="v"><span class="plate"><?= htmlspecialchars($report['plate_number']) ?></span></div>
        <div class="k">Pelapor</div><div class="v"><?= htmlspecialchars($report['reporter_name']) ?> <span class="text-muted">(<?= htmlspecialchars($report['reporter_role']) ?>)</span></div>
        <div class="k">E-mel pelapor</div><div class="v"><?= htmlspecialchars($report['reporter_email'] ?: '—') ?></div>
        <div class="k">Jenis kenderaan</div><div class="v"><?= htmlspecialchars($report['vehicle_type'] ?: '—') ?></div>
        <div class="k">Pemilik</div><div class="v"><?= htmlspecialchars($report['owner_name'] ?: '—') ?></div>
        <div class="k">No. telefon</div><div class="v"><?= htmlspecialchars($report['phone'] ?: '—') ?></div>
        <div class="k">No. pengenalan</div><div class="v"><?= htmlspecialchars($report['id_number'] ?: '—') ?></div>
        <div class="k">Status kenderaan</div><div class="v"><?= htmlspecialchars($report['vehicle_status'] ?: '—') ?></div>
      </div>
    </div>

    <div class="card">
      <span class="eyebrow">Aktiviti</span>
      <div class="timeline mt-4">
        <div class="ev">
          <div class="ev-time"><?= htmlspecialchars(date('d M Y, H:i', strtotime($report['created_at']))) ?></div>
          <div class="ev-title">Laporan dihantar</div>
          <div class="ev-meta">Oleh <?= htmlspecialchars($report['reporter_name']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-6">
    <span class="eyebrow">Butiran kesalahan</span>
    <p class="mt-2"><?= nl2br(htmlspecialchars($report['offense_details'])) ?></p>
  </div>

  <div class="card mt-6">
    <div class="nv-row between">
      <span class="eyebrow">Lokasi</span>
      <a href="<?= $mapLink ?>" target="_blank" rel="noopener"><i data-lucide="external-link" style="width:14px;height:14px;vertical-align:-2px;"></i> <?= htmlspecialchars($report['latitude'] . ', ' . $report['longitude']) ?></a>
    </div>
    <iframe class="mt-4" style="width:100%;height:380px;border:0;border-radius:12px;" src="<?= $mapEmbed ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>

  <div class="card mt-6">
    <span class="eyebrow">Foto (<?= count($photos) ?>)</span>
    <div class="mt-4" style="display:flex;flex-wrap:wrap;gap:14px;">
      <?php if (empty($photos)): ?>
        <p class="text-muted">Tiada foto dilampirkan.</p>
      <?php else: foreach ($photos as $i => $p):
          $resolved = nv_report_photo_url($p);
          $name = htmlspecialchars('report_' . (int)$report['id'] . '_' . ($i + 1) . '.' . (pathinfo($p, PATHINFO_EXTENSION) ?: 'jpg'));
          if ($resolved === null): ?>
        <div style="width:200px;height:200px;border-radius:12px;border:1px dashed var(--border);display:flex;align-items:center;justify-content:center;text-align:center;color:var(--fg-3);font-size:12px;padding:10px;">
          <span><i data-lucide="image-off"></i><br>Foto tidak ditemui</span>
        </div>
          <?php else: $url = htmlspecialchars($resolved); ?>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <a href="<?= $url ?>" target="_blank" rel="noopener" title="Lihat saiz penuh">
            <img src="<?= $url ?>" alt="Report photo" style="width:200px;height:200px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
          </a>
          <div class="nv-row gap-2" style="justify-content:center;">
            <a class="btn btn-quiet" href="<?= $url ?>" target="_blank" rel="noopener" title="Lihat"><i data-lucide="eye"></i></a>
            <a class="btn btn-quiet" href="<?= $url ?>" download="<?= $name ?>" title="Muat turun"><i data-lucide="download"></i></a>
          </div>
        </div>
      <?php endif; endforeach; endif; ?>
    </div>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
