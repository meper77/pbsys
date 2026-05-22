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

$flash = $_SESSION['reports_flash'] ?? null;
unset($_SESSION['reports_flash']);

$res = mysqli_query($con, "SELECT id, reporter_name, reporter_role, plate_number, owner_name,
        vehicle_type, offense_details, latitude, longitude, photo_paths, created_at
        FROM vehicle_reports ORDER BY created_at DESC");

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'reports'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow">Laporan</span>
      <h1>Vehicle reports</h1>
    </div>
    <div class="actions">
      <a class="btn btn-signal" href="/vehicles/report.php"><i data-lucide="plus"></i> New report</a>
      <a class="btn btn-ghost" href="/admin/dashboard.php"><i data-lucide="arrow-left"></i> Back to admin</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= htmlspecialchars($flash['type']) ?> mb-4"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form class="card nv-stack" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="reportsSearch">Cari laporan</label>
      <input class="input mono" id="reportsSearch" type="text" placeholder="Plate, reporter, owner, offense…" autofocus>
    </div>
  </form>

  <form id="bulkForm" method="POST" action="/admin/delete_report.php" class="mt-6"
        onsubmit="return confirm('Padam ' + document.querySelectorAll('#reportsTable tbody input[name=&quot;ids[]&quot;]:checked').length + ' laporan?');">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;">No reports selected.</span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled>
        <i data-lucide="trash-2"></i> Delete selected
      </button>
    </div>

    <div class="card flat">
      <table id="reportsTable" class="table">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th>ID</th>
            <th>Submitted</th>
            <th>Plate</th>
            <th>Reporter</th>
            <th>Owner</th>
            <th>Vehicle</th>
            <th>Offense</th>
            <th>Location</th>
            <th>Photos</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($res && $row = mysqli_fetch_assoc($res)):
            $photos = json_decode($row['photo_paths'] ?? '[]', true) ?: [];
            $mapUrl = 'https://www.google.com/maps?q=' . urlencode($row['latitude'] . ',' . $row['longitude']);
        ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" aria-label="Select report <?= (int)$row['id'] ?>"></td>
            <td class="meta">#<?= (int)$row['id'] ?></td>
            <td class="meta"><?= htmlspecialchars(date('d M Y, H:i', strtotime($row['created_at']))) ?></td>
            <td><span class="plate"><?= htmlspecialchars($row['plate_number']) ?></span></td>
            <td>
              <div class="owner">
                <span class="name"><?= htmlspecialchars($row['reporter_name']) ?></span>
                <span class="id"><?= htmlspecialchars($row['reporter_role']) ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($row['owner_name'] ?: '—') ?></td>
            <td class="meta"><?= htmlspecialchars($row['vehicle_type'] ?: '—') ?></td>
            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($row['offense_details']) ?>"><?= htmlspecialchars($row['offense_details']) ?></td>
            <td><a href="<?= $mapUrl ?>" target="_blank" rel="noopener"><i data-lucide="map-pin" style="width:14px;height:14px;vertical-align:-2px;"></i> Map</a></td>
            <td class="meta"><?= count($photos) ?> <i data-lucide="camera" style="width:14px;height:14px;vertical-align:-2px;"></i></td>
            <td>
              <a href="/admin/report_view.php?id=<?= (int)$row['id'] ?>" class="btn btn-quiet" title="View"><i data-lucide="eye"></i></a>
              <a href="/admin/delete_report.php?id=<?= (int)$row['id'] ?>" class="btn btn-quiet text-danger" title="Delete" onclick="return confirm('Padam laporan #<?= (int)$row['id'] ?>?');"><i data-lucide="trash-2"></i></a>
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
        $count.text(checked === 0 ? 'No reports selected.' : checked + ' selected');
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
