<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';

// Admin only
requireAdmin();

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
    'all_vehicles' => 'Semua kenderaan',
    'back' => 'Kembali',
    'staff' => 'Staf', 
    'student' => 'Pelajar', 
    'visitor' => 'Pelawat', 
    'contractor' => 'Kontraktor',
    'no' => 'Bil.', 
    'name' => 'Nama', 
    'phone' => 'No. Telefon',
    'id_number' => 'No. Pengenalan', 
    'model' => 'Model', 
    'plate_number' => 'No. Plat',
    'category' => 'Kategori',
    'status' => 'Status',
    'active' => 'Aktif', 
    'inactive' => 'Tidak aktif',
    'created' => 'Dibuat',
    'action' => 'Tindakan',
    'no_records' => 'Tiada rekod', 
    'company_name' => 'Syarikat',
    'total_records' => 'Jumlah rekod',
    'bulk_delete' => 'Padam terpilih',
    'filter' => 'Penapis',
    'selected_count' => 'terpilih',
] : [
    'eyebrow' => 'Administration',
    'page_title' => 'Vehicle list',
    'all_vehicles' => 'All vehicles',
    'back' => 'Back',
    'staff' => 'Staff', 
    'student' => 'Student', 
    'visitor' => 'Visitor', 
    'contractor' => 'Contractor',
    'no' => 'No.', 
    'name' => 'Name', 
    'phone' => 'Phone',
    'id_number' => 'ID number', 
    'model' => 'Model', 
    'plate_number' => 'Plate',
    'category' => 'Category',
    'status' => 'Status',
    'active' => 'Active', 
    'inactive' => 'Inactive',
    'created' => 'Created',
    'action' => 'Action',
    'no_records' => 'No records', 
    'company_name' => 'Company',
    'total_records' => 'Total records',
    'bulk_delete' => 'Delete selected',
    'filter' => 'Filter',
    'selected_count' => 'selected',
];

// Get filter type (all, staff, student, visitor, contractor)
$type = strtolower($_GET['type'] ?? 'all');
if ($type !== 'all' && !in_array($type, ['staff', 'student', 'visitor', 'contractor'])) {
    $type = 'all';
}

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $vehicle_ids = $_POST['vehicle_ids'] ?? [];
    $vehicle_types = $_POST['vehicle_types'] ?? [];
    
    if (!empty($vehicle_ids) && is_array($vehicle_ids) && $action === 'delete') {
        for ($i = 0; $i < count($vehicle_ids); $i++) {
            $vid = intval($vehicle_ids[$i]);
            $vtype = $vehicle_types[$i] ?? '';
            
            if (in_array($vtype, ['staff', 'student', 'visitor', 'contractor'])) {
                $table = $vtype . 'car';
                $id_col = match($vtype) {
                    'visitor' => 'visitorid',
                    'staff' => 'staffid',
                    'student' => 'studentid',
                    'contractor' => 'contractorid'
                };
                @mysqli_query($con, "DELETE FROM `$table` WHERE $id_col = $vid");
                @mysqli_query($con, "DELETE FROM vehicle_status WHERE vehicle_id = $vid AND vehicle_type = '$vtype'");
            }
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?type=' . $type);
    exit();
}

// Build query to get all vehicles across categories
$vehicles = [];
$tables_to_query = ($type === 'all') ? ['staff', 'student', 'visitor', 'contractor'] : [$type];

foreach ($tables_to_query as $cat) {
    $table = $cat . 'car';
    $id_col = match($cat) {
        'visitor' => 'visitorid',
        'staff' => 'staffid',
        'student' => 'studentid',
        'contractor' => 'contractorid'
    };
    
    if ($cat === 'contractor') {
        $q = "SELECT $id_col as id, name, phone, ic_passport as id_number, company_name, model, platenum, created_at, '$cat' as category FROM `$table` ORDER BY created_at DESC";
    } else {
        $id_field = match($cat) {
            'visitor' => 'ic_passport',
            'staff' => 'staffno',
            'student' => 'matric',
        };
        $q = "SELECT $id_col as id, name, phone, $id_field as id_number, model, platenum, created_at, '$cat' as category FROM `$table` ORDER BY created_at DESC";
    }
    
    $result = @mysqli_query($con, $q);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vehicles[] = $row;
        }
    }
}

// Sort by created_at descending
usort($vehicles, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$total = count($vehicles);

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = $type; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($type === 'all' ? $t['all_vehicles'] : $t[$type] . ' — ' . $t['page_title']) ?></h1>
      <div class="sub text-mono"><?= htmlspecialchars($t['total_records']) ?>: <?= $total ?></div>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/dashboard.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <?php if ($type === 'all'): ?>
  <form class="card nv-stack gap-3">
    <div class="field">
      <label class="field-label" for="typeFilter"><?= htmlspecialchars($t['filter']) ?></label>
      <select id="typeFilter" class="input" onchange="window.location = '?type=' + this.value">
        <option value="all" selected>All categories</option>
        <option value="staff"><?= htmlspecialchars($t['staff']) ?></option>
        <option value="student"><?= htmlspecialchars($t['student']) ?></option>
        <option value="visitor"><?= htmlspecialchars($t['visitor']) ?></option>
        <option value="contractor"><?= htmlspecialchars($t['contractor']) ?></option>
      </select>
    </div>
  </form>
  <?php else: ?>
  <form class="card nv-stack gap-3">
    <div class="field">
      <label class="field-label" for="typeFilter"><?= htmlspecialchars($t['filter']) ?></label>
      <select id="typeFilter" class="input" onchange="window.location = '?type=' + this.value">
        <option value="all">All categories</option>
        <option value="staff" <?= $type === 'staff' ? 'selected' : '' ?>><?= htmlspecialchars($t['staff']) ?></option>
        <option value="student" <?= $type === 'student' ? 'selected' : '' ?>><?= htmlspecialchars($t['student']) ?></option>
        <option value="visitor" <?= $type === 'visitor' ? 'selected' : '' ?>><?= htmlspecialchars($t['visitor']) ?></option>
        <option value="contractor" <?= $type === 'contractor' ? 'selected' : '' ?>><?= htmlspecialchars($t['contractor']) ?></option>
      </select>
    </div>
  </form>
  <?php endif; ?>

  <form id="bulkForm" method="POST" class="mt-6">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;">No vehicles selected.</span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected vehicles?')">
        <i data-lucide="trash-2"></i> <?= htmlspecialchars($t['bulk_delete']) ?>
      </button>
      <input type="hidden" name="action" value="delete">
    </div>

    <div class="card flat">
      <?php if ($total > 0): ?>
      <table class="table" id="vehicleTable">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
            <th><?= htmlspecialchars($t['plate_number']) ?></th>
            <th><?= htmlspecialchars($t['name']) ?></th>
            <th><?= htmlspecialchars($t['phone']) ?></th>
            <th><?= htmlspecialchars($t['id_number']) ?></th>
            <th><?= htmlspecialchars($t['model']) ?></th>
            <th style="width:80px;"><?= htmlspecialchars($t['category']) ?></th>
            <th><?= htmlspecialchars($t['created']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; foreach ($vehicles as $v): ?>
          <tr>
            <td>
              <input type="checkbox" name="vehicle_ids[]" value="<?= htmlspecialchars($v['id']) ?>" aria-label="Select vehicle <?= htmlspecialchars($v['platenum']) ?>">
              <input type="hidden" name="vehicle_types[]" value="<?= htmlspecialchars($v['category']) ?>">
            </td>
            <td class="meta"><?= $counter++ ?></td>
            <td><span class="plate"><?= htmlspecialchars($v['platenum']) ?></span></td>
            <td><?= htmlspecialchars($v['name']) ?></td>
            <td class="meta"><?= htmlspecialchars($v['phone']) ?></td>
            <td class="meta"><?= htmlspecialchars($v['id_number'] ?? '—') ?></td>
            <td><?= htmlspecialchars($v['model']) ?></td>
            <td class="meta">
              <span class="badge">
                <?= htmlspecialchars($t[$v['category']] ?? $v['category']) ?>
              </span>
            </td>
            <td class="meta"><?= htmlspecialchars(date('d M Y', strtotime($v['created_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="flash info"><i data-lucide="info"></i> <?= htmlspecialchars($t['no_records']) ?></div>
      <?php endif; ?>
    </div>
  </form>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script>
  $(function(){
      var table = $('#vehicleTable');
      if (table.length) {
          var dt = table.DataTable({ "pageLength": 50, "order": [[1, "asc"]], "autoWidth": false, "dom": "rtip" });
      }

      // Bulk delete
      var $selectAll = $('#selectAll');
      var $bulkForm = $('#bulkForm');
      var $bulkCount = $('#bulkCount');
      var $bulkDeleteBtn = $('#bulkDeleteBtn');

      function refreshSelection() {
        var checked = $('input[name="vehicle_ids[]"]:checked').length;
        $bulkDeleteBtn.prop('disabled', checked === 0);
        $bulkCount.text(checked === 0 ? 'No vehicles selected.' : checked + ' <?= $t["selected_count"] ?>');
        var total = $('input[name="vehicle_ids[]"]').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }

      $('input[name="vehicle_ids[]"]').on('change', refreshSelection);

      $selectAll.on('change', function () {
        var on = this.checked;
        $('input[name="vehicle_ids[]"]').prop('checked', on);
        refreshSelection();
      });

      if (table.length) {
        dt.on('draw', refreshSelection);
      }
      refreshSelection();
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
