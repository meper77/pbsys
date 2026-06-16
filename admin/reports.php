<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';

// Admins, or users granted the 'reports' page (view-only — delete stays admin).
nv_guard_page($con, 'reports');
$isAdmin = isAdmin();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'reports' => 'Laporan',
    'vehicle_reports' => 'Laporan kenderaan',
    'new_report' => 'Laporan baru',
    'back' => 'Kembali ke pentadbir',
    'search_label' => 'Cari laporan',
    'search_placeholder' => 'Plat, pelapor, pemilik, kesalahan…',
    'bulk_delete' => 'Padam terpilih',
    'filter' => 'Penapis',
    'status' => 'Status',
    'date_from' => 'Dari tarikh',
    'date_to' => 'Ke tarikh',
    'all_status' => 'Semua status',
    'resolved' => 'Selesai',
    'pending' => 'Menunggu',
    'id' => 'ID',
    'submitted' => 'Dihantar',
    'plate' => 'Plat',
    'reporter' => 'Pelapor',
    'owner' => 'Pemilik',
    'vehicle_type' => 'Jenis kenderaan',
    'offense' => 'Kesalahan',
    'location' => 'Lokasi',
    'photos' => 'Foto',
    'action' => 'Tindakan',
    'view' => 'Lihat',
    'delete' => 'Padam',
    'no_reports' => 'Tiada laporan dipilih.',
    'selected_count' => 'dipilih',
] : [
    'reports' => 'Reports',
    'vehicle_reports' => 'Vehicle reports',
    'new_report' => 'New report',
    'back' => 'Back to admin',
    'search_label' => 'Search reports',
    'search_placeholder' => 'Plate, reporter, owner, offense…',
    'bulk_delete' => 'Delete selected',
    'filter' => 'Filters',
    'status' => 'Status',
    'date_from' => 'From date',
    'date_to' => 'To date',
    'all_status' => 'All status',
    'resolved' => 'Resolved',
    'pending' => 'Pending',
    'id' => 'ID',
    'submitted' => 'Submitted',
    'plate' => 'Plate',
    'reporter' => 'Reporter',
    'owner' => 'Owner',
    'vehicle_type' => 'Vehicle Type',
    'offense' => 'Offense',
    'location' => 'Location',
    'photos' => 'Photos',
    'action' => 'Action',
    'view' => 'View',
    'delete' => 'Delete',
    'no_reports' => 'No reports selected.',
    'selected_count' => 'selected',
];

$flash = $_SESSION['reports_flash'] ?? null;
unset($_SESSION['reports_flash']);

// Get filters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters. Columns match the actual vehicle_reports schema.
$query = "SELECT * FROM vehicle_reports WHERE 1=1";

if ($date_from) {
    $date_from = mysqli_real_escape_string($con, $date_from);
    $query .= " AND DATE(created_at) >= '$date_from'";
}

if ($date_to) {
    $date_to = mysqli_real_escape_string($con, $date_to);
    $query .= " AND DATE(created_at) <= '$date_to'";
}

$query .= " ORDER BY created_at DESC";

$res = mysqli_query($con, $query);

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'reports'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['reports']) ?></span>
      <h1><?= htmlspecialchars($t['vehicle_reports']) ?></h1>
    </div>
    <div class="actions">
      <a class="btn btn-signal" href="/vehicles/report.php"><i data-lucide="plus"></i> <?= htmlspecialchars($t['new_report']) ?></a>
      <?php if ($isAdmin): ?><a class="btn btn-ghost" href="/admin/admins.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a><?php endif; ?>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= htmlspecialchars($flash['type']) ?> mb-4"><i data-lucide="check-circle"></i> <?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form class="card nv-stack gap-4" method="GET" onsubmit="return true;">
    <div class="nv-grid cols-3 gap-4">
      <div class="field">
        <label class="field-label" for="reportsSearch"><?= htmlspecialchars($t['search_label']) ?></label>
        <input class="input mono" id="reportsSearch" type="text" placeholder="<?= htmlspecialchars($t['search_placeholder']) ?>" autofocus>
      </div>
      <div class="field">
        <label class="field-label" for="dateFrom"><?= htmlspecialchars($t['date_from']) ?></label>
        <input class="input" id="dateFrom" name="date_from" type="date" value="<?= htmlspecialchars($date_from) ?>">
      </div>
      <div class="field">
        <label class="field-label" for="dateTo"><?= htmlspecialchars($t['date_to']) ?></label>
        <input class="input" id="dateTo" name="date_to" type="date" value="<?= htmlspecialchars($date_to) ?>">
      </div>
    </div>
  </form>

  <form id="bulkForm" method="POST" action="/admin/delete_report.php" class="mt-6"
        onsubmit="return confirm('Padam ' + document.querySelectorAll('#reportsTable tbody input[name=&quot;ids[]&quot;]:checked').length + ' laporan?');">
    <?php if ($isAdmin): ?>
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;"><?= htmlspecialchars($t['no_reports']) ?></span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled>
        <i data-lucide="trash-2"></i> <?= htmlspecialchars($t['bulk_delete']) ?>
      </button>
    </div>
    <?php endif; ?>

    <div class="card flat">
      <table id="reportsTable" class="table">
        <thead>
          <tr>
            <?php if ($isAdmin): ?><th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Pilih semua"></th><?php endif; ?>
            <th><?= htmlspecialchars($t['id']) ?></th>
            <th><?= htmlspecialchars($t['submitted']) ?></th>
            <th><?= htmlspecialchars($t['status']) ?></th>
            <th><?= htmlspecialchars($t['plate']) ?></th>
            <th><?= htmlspecialchars($t['reporter']) ?></th>
            <th><?= htmlspecialchars($t['owner']) ?></th>
            <th><?= htmlspecialchars($t['vehicle_type']) ?></th>
            <th><?= htmlspecialchars($t['offense']) ?></th>
            <th><?= htmlspecialchars($t['location']) ?></th>
            <th><?= htmlspecialchars($t['photos']) ?></th>
            <th><?= htmlspecialchars($t['action']) ?></th>
          </tr>
        </thead>
        <tbody>
        <?php while ($res && $row = mysqli_fetch_assoc($res)):
            $photos = json_decode($row['photo_paths'] ?? '[]', true) ?: [];
            $mapUrl = 'https://www.google.com/maps?q=' . urlencode($row['latitude'] . ',' . $row['longitude']);
        ?>
          <tr>
            <?php if ($isAdmin): ?><td><input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" aria-label="Pilih laporan <?= (int)$row['id'] ?>"></td><?php endif; ?>
            <td class="meta">#<?= (int)$row['id'] ?></td>
            <td class="meta"><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))) ?></td>
            <td><?php $rc = !empty($row['closed_at']); ?><span class="pill <?= $rc ? 'ok' : 'warn' ?>"><span class="dot"></span> <?= $rc ? htmlspecialchars($t['resolved']) : htmlspecialchars($t['pending']) ?></span></td>
            <td><span class="plate"><?= htmlspecialchars($row['plate_number']) ?></span></td>
            <td>
              <div class="owner">
                <span class="name"><?= htmlspecialchars($row['reporter_name']) ?></span>
                <span class="id"><?= htmlspecialchars($row['reporter_role']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($row['owner_name'] ?: '—') ?></td>
            <td class="meta"><?= htmlspecialchars($row['vehicle_type'] ?: '—') ?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($row['offense_details']) ?>"><?= htmlspecialchars($row['offense_details']) ?></td>
            <td><a href="<?= $mapUrl ?>" target="_blank" rel="noopener"><i data-lucide="map-pin" style="width:14px;height:14px;vertical-align:-2px;"></i> Peta</a></td>
            <td class="meta"><?= count($photos) ?> <i data-lucide="camera" style="width:14px;height:14px;vertical-align:-2px;"></i></td>
            <td>
              <a href="/admin/report_view.php?id=<?= (int)$row['id'] ?>" class="btn btn-quiet" title="<?= htmlspecialchars($t['view']) ?>"><i data-lucide="eye"></i></a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </form>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    $(function(){
      var dt = $('#reportsTable').DataTable({
        language: NV_DT_BM,
        order: [[2, 'desc']],
        pageLength: 25,
        dom: 'rtip',
        columnDefs: [{ targets: 0, orderable: false, searchable: false }]
      });
      $('#reportsSearch').on('input', function () { dt.search(this.value).draw(); });

      var $selectAll = $('#selectAll');
      var $btn       = $('#bulkDeleteBtn');
      var $count     = $('#bulkCount');

      function refreshSelection() {
        var checked = $('#reportsTable tbody input[name="ids[]"]:checked').length;
        $btn.prop('disabled', checked === 0);
        $count.text(checked === 0 ? '<?= htmlspecialchars($t["no_reports"]) ?>' : checked + ' <?= htmlspecialchars($t["selected_count"]) ?>');
        var total = $('#reportsTable tbody input[name="ids[]"]').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }

      $('#reportsTable').on('change', 'input[name="ids[]"]', refreshSelection);

      $selectAll.on('change', function () {
        var on = this.checked;
        $('#reportsTable tbody input[name="ids[]"]').prop('checked', on);
        refreshSelection();
      });

      // Re-bind after DataTables page changes
      dt.on('draw', refreshSelection);
      refreshSelection();
    });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
