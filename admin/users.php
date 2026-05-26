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

if (!can_view_user_list()) {
  redirect_unauthorized();
}

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
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
    'eyebrow' => 'Pengguna',
    'title'   => 'Senarai pengguna',
    'export'  => 'Eksport',
    'no' => 'No.', 'email' => 'Emel', 'password' => 'Kata laluan',
    'user_name' => 'Nama', 'action' => 'Tindakan',
    'edit' => 'Kemaskini', 'delete' => 'Padam',
    'no_records' => 'Tiada rekod pengguna',
    'delete_confirm' => 'Padam pengguna ini?',
] : [
    'eyebrow' => 'Users',
    'title'   => 'Users',
    'export'  => 'Export',
    'no' => 'No.', 'email' => 'Email', 'password' => 'Password',
    'user_name' => 'Name', 'action' => 'Action',
    'edit' => 'Edit', 'delete' => 'Delete',
    'no_records' => 'No user records yet',
    'delete_confirm' => 'Delete this user?',
];

$users = [];
$result = mysqli_query($con, "SELECT * FROM `user`");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) { $users[] = $row; }
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
    </div>
  </div>

  <?php if (count($users) > 0): ?>
  <form class="card nv-stack" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="usersSearch">Cari pengguna</label>
      <input class="input mono" id="usersSearch" type="text" placeholder="Email, name…" autofocus>
    </div>
  </form>
  <?php endif; ?>

  <div class="card flat mt-6">
    <?php if (count($users) > 0): ?>
    <table class="table" id="userTable">
      <thead>
        <tr>
          <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
          <th><?= htmlspecialchars($t['email']) ?></th>
          <th style="width:140px;"><?= htmlspecialchars($t['password']) ?></th>
          <th><?= htmlspecialchars($t['user_name']) ?></th>
          <th style="width:220px;"><?= htmlspecialchars($t['action']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $counter = 1; foreach ($users as $row): ?>
        <tr>
          <td class="meta"><?= $counter++ ?></td>
          <td><strong><?= htmlspecialchars($row['email']) ?></strong></td>
          <td class="meta">••••••••</td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td>
            <a href="/admin/update_user.php?id=<?= htmlspecialchars($row['userid']) ?>" class="btn btn-quiet"><i data-lucide="pencil"></i> <?= htmlspecialchars($t['edit']) ?></a>
            <a href="/admin/delete_user.php?id=<?= htmlspecialchars($row['userid']) ?>" class="btn btn-quiet text-danger" onclick="return confirm('<?= addslashes($t['delete_confirm']) ?>')"><i data-lucide="trash-2"></i> <?= htmlspecialchars($t['delete']) ?></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="flash info"><i data-lucide="info"></i> <?= htmlspecialchars($t['no_records']) ?></div>
    <?php endif; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>
  <script>
  $(function(){
      var table = $('#userTable');
      if (table.length) {
          var dt = table.DataTable({ "pageLength": 10, "order": [[0, "asc"]], "autoWidth": false, "dom": "rtip" });
          $('#usersSearch').on('input', function () { dt.search(this.value).draw(); });
      }
      $('#export-btn').on('click', function(){
          var t = $('#userTable');
          if (!t.length) return;
          var clone = t.clone();
          clone.removeClass('dataTable').find('.dataTables_empty').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Users"});
          XLSX.writeFile(wb, "users-<?= date('Y-m-d') ?>.xlsx");
      });
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
