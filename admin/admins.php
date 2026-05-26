<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'].'/includes/permission_check.php';

require_admin();

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// Check admin-only permission
if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
    echo '<div class="flash error"><i data-lucide="alert-circle"></i> Unauthorized access</div>';
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
    'eyebrow' => 'Pentadbir',
    'title'   => 'Senarai pentadbir',
    'bulk_delete' => 'Padam terpilih',
    'search_placeholder' => 'Cari mengikut emel...',
    'search_label' => 'Cari pentadbir',
    'no' => 'No.', 
    'email' => 'Emel', 
    'name' => 'Nama',
    'created' => 'Dibuat',
    'action' => 'Tindakan',
    'edit' => 'Kemaskini', 
    'delete' => 'Padam',
    'no_records' => 'Tiada rekod pentadbir',
    'delete_confirm' => 'Padam pentadbir ini?',
    'selected_count' => 'terpilih',
] : [
    'eyebrow' => 'Administration',
    'title'   => 'Admins',
    'bulk_delete' => 'Delete selected',
    'search_placeholder' => 'Search by email...',
    'search_label' => 'Search admins',
    'no' => 'No.', 
    'email' => 'Email', 
    'name' => 'Name',
    'created' => 'Created',
    'action' => 'Action',
    'edit' => 'Edit', 
    'delete' => 'Delete',
    'no_records' => 'No admin records',
    'delete_confirm' => 'Delete this admin?',
    'selected_count' => 'selected',
];

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_ids = $_POST['admin_ids'] ?? [];
    
    if (!empty($admin_ids) && is_array($admin_ids) && $action === 'delete') {
        foreach ($admin_ids as $aid) {
            $aid = intval($aid);
            @mysqli_query($con, "DELETE FROM admin WHERE userid = $aid");
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$admins = [];
$result = mysqli_query($con, "SELECT userid, email, name, last_login FROM `admin` ORDER BY userid DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) { 
        $admins[] = $row; 
    }
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'admins'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
    </div>
    <div class="actions">
      <a class="btn btn-signal" href="/admin/add_admin.php"><i data-lucide="plus"></i> New admin</a>
    </div>
  </div>

  <?php if (count($admins) > 0): ?>
  <form class="card nv-stack" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="adminsSearch"><?= htmlspecialchars($t['search_label']) ?></label>
      <input class="input mono" id="adminsSearch" type="text" placeholder="<?= htmlspecialchars($t['search_placeholder']) ?>" autofocus>
    </div>
  </form>
  <?php endif; ?>

  <form id="bulkForm" method="POST" class="mt-6">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;">No admins selected.</span>
      <button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled>
        <i data-lucide="trash-2"></i> <?= htmlspecialchars($t['bulk_delete']) ?>
      </button>
      <input type="hidden" name="action" value="delete">
    </div>

    <div class="card flat">
      <?php if (count($admins) > 0): ?>
      <table class="table" id="adminTable">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
            <th><?= htmlspecialchars($t['email']) ?></th>
            <th><?= htmlspecialchars($t['name']) ?></th>
            <th><?= htmlspecialchars($t['created']) ?></th>
            <th style="width:120px;"><?= htmlspecialchars($t['action']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; foreach ($admins as $row): ?>
          <tr>
            <td><input type="checkbox" name="admin_ids[]" value="<?= htmlspecialchars($row['userid']) ?>" aria-label="Select admin <?= htmlspecialchars($row['email']) ?>"></td>
            <td class="meta"><?= $counter++ ?></td>
            <td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
            <td><?= htmlspecialchars($row['name'] ?? '—') ?></td>
            <td class="meta"><?= htmlspecialchars($row['last_login'] ? date('d M Y', strtotime($row['last_login'])) : 'Never') ?></td>
            <td>
              <a href="/admin/update_admin.php?id=<?= htmlspecialchars($row['userid']) ?>" class="btn btn-quiet" title="<?= htmlspecialchars($t['edit']) ?>"><i data-lucide="pencil"></i></a>
              <a href="/admin/delete_admin.php?id=<?= htmlspecialchars($row['userid']) ?>" class="btn btn-quiet text-danger" title="<?= htmlspecialchars($t['delete']) ?>" onclick="return confirm('<?= addslashes($t['delete_confirm']) ?>')"><i data-lucide="trash-2"></i></a>
            </td>
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
      var table = $('#adminTable');
      if (table.length) {
          var dt = table.DataTable({ "pageLength": 25, "order": [[1, "asc"]], "autoWidth": false, "dom": "rtip" });
          $('#adminsSearch').on('input', function () { dt.search(this.value).draw(); });
      }

      // Bulk delete
      var $selectAll = $('#selectAll');
      var $bulkForm = $('#bulkForm');
      var $bulkCount = $('#bulkCount');
      var $bulkDeleteBtn = $('#bulkDeleteBtn');

      function refreshSelection() {
        var checked = $('input[name="admin_ids[]"]:checked').length;
        $bulkDeleteBtn.prop('disabled', checked === 0);
        $bulkCount.text(checked === 0 ? 'No admins selected.' : checked + ' <?= $t["selected_count"] ?>');
        var total = $('input[name="admin_ids[]"]').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }

      $('input[name="admin_ids[]"]').on('change', refreshSelection);

      $selectAll.on('change', function () {
        var on = this.checked;
        $('input[name="admin_ids[]"]').prop('checked', on);
        refreshSelection();
      });

      $bulkForm.on('submit', function(e) {
        var checked = $('input[name="admin_ids[]"]:checked').length;
        if (checked === 0 || !confirm('Delete ' + checked + ' admin(s)?')) {
          e.preventDefault();
          return false;
        }
      });

      if (table.length) {
        dt.on('draw', refreshSelection);
      }
      refreshSelection();
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
