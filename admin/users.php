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
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pengguna',
    'title'   => 'Senarai pengguna',
    'export'  => 'Eksport',
    'bulk_delete' => 'Padam terpilih',
    'bulk_status' => 'Ubah status',
    'activate' => 'Aktifkan',
    'deactivate' => 'Nyahhaktifkan',
    'no' => 'No.', 
    'email' => 'Emel', 
    'phone' => 'Telefon',
    'user_name' => 'Nama', 
    'status' => 'Status',
    'created' => 'Dibuat',
    'action' => 'Tindakan',
    'edit' => 'Kemaskini', 
    'delete' => 'Padam',
    'no_records' => 'Tiada rekod pengguna',
    'delete_confirm' => 'Padam pengguna ini?',
    'select_action' => 'Pilih tindakan',
    'selected_count' => 'terpilih',
] : [
    'eyebrow' => 'Users',
    'title'   => 'Users',
    'export'  => 'Export',
    'bulk_delete' => 'Delete selected',
    'bulk_status' => 'Change status',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'no' => 'No.', 
    'email' => 'Email', 
    'phone' => 'Phone',
    'user_name' => 'Name', 
    'status' => 'Status',
    'created' => 'Created',
    'action' => 'Action',
    'edit' => 'Edit', 
    'delete' => 'Delete',
    'no_records' => 'No user records yet',
    'delete_confirm' => 'Delete this user?',
    'select_action' => 'Select action',
    'selected_count' => 'selected',
];

// Handle bulk operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_ids = $_POST['user_ids'] ?? [];
    
    if (!empty($user_ids) && is_array($user_ids)) {
        foreach ($user_ids as $uid) {
            $uid = intval($uid);
            if ($action === 'delete') {
                @mysqli_query($con, "DELETE FROM user WHERE userid = $uid");
            } elseif ($action === 'activate') {
                @mysqli_query($con, "UPDATE user SET status = 'active' WHERE userid = $uid");
            } elseif ($action === 'deactivate') {
                @mysqli_query($con, "UPDATE user SET status = 'inactive' WHERE userid = $uid");
            }
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$users = [];
// SELECT * (not specific date columns) so schema drift between deployments
// (e.g. missing updated_at/created_at) can't make the query fail -> empty list.
$result = mysqli_query($con, "SELECT * FROM `user` ORDER BY userid DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
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
<?php $nv_active = 'users'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
    </div>
    <div class="actions">
      <button class="btn btn-ghost" id="export-btn"><i data-lucide="download"></i> <?= htmlspecialchars($t['export']) ?></button>
      <a class="btn btn-primary" href="/admin/add_user.php"><i data-lucide="plus"></i> <?= $lang === 'bm' ? 'Tambah pengguna' : 'Add user' ?></a>
    </div>
  </div>

  <?php if (count($users) > 0): ?>
  <form class="card nv-stack" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="usersSearch"><?= $lang === 'bm' ? 'Cari pengguna' : 'Search users' ?></label>
      <input class="input mono" id="usersSearch" type="text" placeholder="Email, name…" autofocus>
    </div>
  </form>
  <?php endif; ?>

  <form id="bulkForm" method="POST" class="mt-6" onsubmit="return confirm('Confirm action?');">
    <div class="nv-row between mb-4">
      <span class="text-muted" id="bulkCount" style="font-size:13px;">No users selected.</span>
      <div class="nv-row gap-2">
        <select name="action" id="bulkAction" class="input" style="flex:0;width:160px;">
          <option value="">— <?= htmlspecialchars($t['select_action']) ?> —</option>
          <option value="delete"><?= htmlspecialchars($t['bulk_delete']) ?></option>
        </select>
        <button type="submit" class="btn btn-ghost" id="bulkActionBtn" disabled>
          <i data-lucide="check"></i> <?= $lang === 'bm' ? 'Laksana' : 'Execute' ?>
        </button>
      </div>
    </div>

    <div class="card flat">
      <?php if (count($users) > 0): ?>
      <table class="table" id="userTable">
        <thead>
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
            <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
            <th><?= htmlspecialchars($t['email']) ?></th>
            <th><?= htmlspecialchars($t['phone']) ?></th>
            <th><?= htmlspecialchars($t['user_name']) ?></th>
            <th><?= htmlspecialchars($t['created']) ?></th>
            <th style="width:120px;"><?= htmlspecialchars($t['action']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; foreach ($users as $row): ?>
          <tr>
            <td><input type="checkbox" name="user_ids[]" value="<?= htmlspecialchars($row['userid']) ?>" aria-label="Select user <?= htmlspecialchars($row['email']) ?>"></td>
            <td class="meta"><?= $counter++ ?></td>
            <td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
            <td class="meta"><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['name'] ?? '—') ?></td>
            <?php $u_date = $row['updated_at'] ?? $row['created_at'] ?? $row['last_login'] ?? null; ?>
            <td class="meta"><?= $u_date ? htmlspecialchars(date('d M Y', strtotime($u_date))) : '—' ?></td>
            <td>
              <a href="/admin/update_user.php?id=<?= htmlspecialchars($row['userid']) ?>" class="btn btn-quiet" title="<?= htmlspecialchars($t['edit']) ?>"><i data-lucide="pencil"></i></a>
              <form method="POST" action="/admin/delete_user.php" style="display:inline" onsubmit="return confirm('<?= addslashes($t['delete_confirm']) ?>')">
                <input type="hidden" name="id" value="<?= htmlspecialchars($row['userid']) ?>">
                <button type="submit" class="btn btn-quiet text-danger" title="<?= htmlspecialchars($t['delete']) ?>"><i data-lucide="trash-2"></i></button>
              </form>
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>
  <script>
  $(function(){
      var table = $('#userTable');
      if (table.length) {
          var dt = table.DataTable({ "pageLength": 25, "order": [[1, "asc"]], "autoWidth": false, "dom": "rtip" });
          $('#usersSearch').on('input', function () { dt.search(this.value).draw(); });
      }
      
      $('#export-btn').on('click', function(){
          var t = $('#userTable');
          if (!t.length) return;
          var clone = t.clone();
          clone.removeClass('dataTable').find('.dataTables_empty').remove();
          clone.find('input[type="checkbox"]').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Users"});
          XLSX.writeFile(wb, "users-<?= date('Y-m-d') ?>.xlsx");
      });

      // Bulk actions
      var $selectAll = $('#selectAll');
      var $bulkForm = $('#bulkForm');
      var $bulkCount = $('#bulkCount');
      var $bulkActionBtn = $('#bulkActionBtn');
      var $bulkAction = $('#bulkAction');

      function refreshSelection() {
        var checked = $('input[name="user_ids[]"]:checked').length;
        $bulkActionBtn.prop('disabled', checked === 0 || !$bulkAction.val());
        $bulkCount.text(checked === 0 ? 'No users selected.' : checked + ' <?= $t["selected_count"] ?>');
        var total = $('input[name="user_ids[]"]').length;
        $selectAll.prop('checked', total > 0 && checked === total);
        $selectAll.prop('indeterminate', checked > 0 && checked < total);
      }

      $('input[name="user_ids[]"], #bulkAction').on('change', refreshSelection);

      $selectAll.on('change', function () {
        var on = this.checked;
        $('input[name="user_ids[]"]').prop('checked', on);
        refreshSelection();
      });

      if (table.length) {
        dt.on('draw', refreshSelection);
      }
      refreshSelection();
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
