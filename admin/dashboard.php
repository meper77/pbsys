<?php
session_start();

// ========== LOGOUT HANDLER ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}
// ========== END LOGOUT HANDLER ==========

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// LANGUAGE SYSTEM
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow'      => 'Pentadbir',
    'admins_list'  => 'Senarai admin',
    'add_admin'    => 'Tambah admin',
    'export'       => 'Eksport',
    'email'        => 'Emel',
    'password'     => 'Kata laluan',
    'admin_name'   => 'Nama',
    'action'       => 'Tindakan',
    'no'           => 'No.',
    'edit'         => 'Kemaskini',
    'delete'       => 'Padam',
    'no_records'   => 'Tiada rekod admin',
    'delete_confirm' => 'Padam admin ini?',
] : [
    'eyebrow'      => 'Administration',
    'admins_list'  => 'Admins',
    'add_admin'    => 'Add admin',
    'export'       => 'Export',
    'email'        => 'Email',
    'password'     => 'Password',
    'admin_name'   => 'Name',
    'action'       => 'Action',
    'no'           => 'No.',
    'edit'         => 'Edit',
    'delete'       => 'Delete',
    'no_records'   => 'No admin records yet',
    'delete_confirm' => 'Delete this admin?',
];

// Detect PK column
$pk_column = 'adminid';
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM admin");
if ($check_columns) {
    while ($col = mysqli_fetch_assoc($check_columns)) {
        if ($col['Key'] == 'PRI') { $pk_column = $col['Field']; break; }
    }
}

$admins = [];
$result = mysqli_query($con, "SELECT * FROM `admin`");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) { $admins[] = $row; }
    if ($pk_column) {
        $sorted = mysqli_query($con, "SELECT * FROM `admin` ORDER BY `$pk_column` ASC");
        if ($sorted) {
            $admins = [];
            while ($row = mysqli_fetch_assoc($sorted)) { $admins[] = $row; }
        }
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
<?php $nv_active = 'admin'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['admins_list']) ?></h1>
    </div>
    <div class="actions">
      <button class="btn btn-ghost" id="export-btn"><i data-lucide="download"></i> <?= htmlspecialchars($t['export']) ?></button>
      <a class="btn btn-primary" href="/admin/add_admin.php"><i data-lucide="plus"></i> <?= htmlspecialchars($t['add_admin']) ?></a>
    </div>
  </div>

  <?php if (count($admins) > 0): ?>
  <form class="card nv-stack" onsubmit="return false;">
    <div class="field">
      <label class="field-label" for="adminsSearch">Cari pentadbir</label>
      <input class="input mono" id="adminsSearch" type="text" placeholder="Email, name…" autofocus>
    </div>
  </form>
  <?php endif; ?>

  <div class="card flat mt-6">
    <?php if (count($admins) > 0): ?>
    <table class="table" id="adminTable">
      <thead>
        <tr>
          <th style="width:60px;"><?= htmlspecialchars($t['no']) ?></th>
          <th><?= htmlspecialchars($t['email']) ?></th>
          <th style="width:140px;"><?= htmlspecialchars($t['password']) ?></th>
          <th><?= htmlspecialchars($t['admin_name']) ?></th>
          <th style="width:220px;"><?= htmlspecialchars($t['action']) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $counter = 1;
        foreach ($admins as $row):
            $id_value = '';
            foreach ($row as $key => $value) {
                if (strpos(strtolower($key), 'id') !== false) { $id_value = $value; break; }
            }
            if (empty($id_value) && count($row) > 0) { $id_value = reset($row); }

            $email = ''; $name = '';
            foreach ($row as $key => $value) {
                $lk = strtolower($key);
                if (strpos($lk, 'email') !== false) { $email = $value; }
                elseif (strpos($lk, 'name') !== false) { $name = $value; }
            }
        ?>
        <tr>
          <td class="meta"><?= $counter++ ?></td>
          <td><strong><?= htmlspecialchars($email) ?></strong></td>
          <td class="meta">••••••••</td>
          <td><?= htmlspecialchars($name) ?></td>
          <td>
            <a href="/admin/update_admin.php?id=<?= htmlspecialchars($id_value) ?>" class="btn btn-quiet"><i data-lucide="pencil"></i> <?= htmlspecialchars($t['edit']) ?></a>
            <a href="/admin/delete_admin.php?id=<?= htmlspecialchars($id_value) ?>" class="btn btn-quiet text-danger" onclick="return confirm('<?= addslashes($t['delete_confirm']) ?>')"><i data-lucide="trash-2"></i> <?= htmlspecialchars($t['delete']) ?></a>
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
      var table = $('#adminTable');
      if (table.length) {
          var dt = table.DataTable({ "pageLength": 10, "order": [[0, "asc"]], "autoWidth": false, "dom": "rtip" });
          $('#adminsSearch').on('input', function () { dt.search(this.value).draw(); });
      }
      $('#export-btn').on('click', function(){
          var t = $('#adminTable');
          if (!t.length) return;
          var clone = t.clone();
          clone.removeClass('dataTable').find('.dataTables_empty').remove();
          var wb = XLSX.utils.table_to_book(clone[0], {sheet: "Admins"});
          XLSX.writeFile(wb, "admins-<?= date('Y-m-d') ?>.xlsx");
      });
  });
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
