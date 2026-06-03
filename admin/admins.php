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

// Get vehicle status counts
$vehicle_active = 0;
$vehicle_inactive = 0;
$result = mysqli_query($con, "SELECT status, COUNT(*) as count FROM vehicle_status GROUP BY status");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] === 'active') {
            $vehicle_active = (int)$row['count'];
        } elseif ($row['status'] === 'inactive') {
            $vehicle_inactive = (int)$row['count'];
        }
    }
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
    'vehicles'     => 'Kenderaan',
    'active'       => 'Aktif',
    'inactive'     => 'Tidak Aktif',
    'total'        => 'Jumlah',
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
    'vehicles'     => 'Vehicles',
    'active'       => 'Active',
    'inactive'     => 'Inactive',
    'total'        => 'Total',
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
<link rel="stylesheet" href="/assets/css/responsive.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<style>
  /* Dashboard gradient background */
  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
  }
  
  .nv-shell {
    background: linear-gradient(180deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  }
  
  .page-head {
    background: linear-gradient(120deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
    border-radius: clamp(4px, 1vw, 8px);
    padding: clamp(1rem, 2vw, 2rem);
    margin-bottom: clamp(1rem, 2vw, 2rem);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }
  
  .page-head h1 {
    font-size: clamp(24px, 5vw, 32px);
    color: #333;
  }
  
  .card {
    border-radius: clamp(4px, 1vw, 8px);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(102, 126, 234, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
  }
  
  .card:hover {
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
  }
  
  .table {
    border-collapse: collapse;
    background: #fff;
  }
  
  thead {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  }
  
  th {
    font-weight: 600;
    color: #333;
    font-size: clamp(12px, 1.5vw, 14px);
  }
  
  td {
    padding: clamp(0.75rem, 1.5vw, 1rem);
    font-size: clamp(12px, 1.5vw, 14px);
  }
  
  /* Responsive utilities */
  .field {
    margin-bottom: clamp(0.75rem, 1.5vw, 1.5rem);
  }
  
  .field-label {
    font-size: clamp(12px, 1.5vw, 14px);
    font-weight: 500;
    margin-bottom: clamp(0.25rem, 0.75vw, 0.5rem);
  }
  
  .input {
    width: 100%;
    max-width: 100%;
    font-size: clamp(12px, 1.5vw, 14px);
    padding: clamp(0.5rem, 1.5vw, 0.75rem);
  }
  
  .mt-6 {
    margin-top: clamp(1.5rem, 3vw, 2rem);
  }
  
  .actions {
    display: flex;
    gap: clamp(0.5rem, 1vw, 1rem);
    flex-wrap: wrap;
  }
  
  @media (max-width: 640px) {
    .page-head {
      flex-direction: column;
      gap: clamp(1rem, 2vw, 1.5rem);
    }
    
    .actions {
      width: 100%;
      justify-content: stretch;
    }
    
    .actions .btn {
      flex: 1;
    }
  }
</style>
<body>
<div class="nv-shell">
<?php $nv_active = 'admin'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <!-- Vehicle Status Widget -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: clamp(1rem, 2vw, 2rem); margin-bottom: clamp(2rem, 3vw, 3rem);">
    <div class="card" style="padding: clamp(1.5rem, 2vw, 2rem); text-align: center;">
      <div style="font-size: 32px; font-weight: bold; color: #667eea;"><?php echo $vehicle_active; ?></div>
      <div style="font-size: 14px; color: #666; margin-top: 8px;"><?php echo htmlspecialchars($t['vehicles']); ?> - <?php echo htmlspecialchars($t['active']); ?></div>
    </div>
    <div class="card" style="padding: clamp(1.5rem, 2vw, 2rem); text-align: center;">
      <div style="font-size: 32px; font-weight: bold; color: #764ba2;"><?php echo $vehicle_inactive; ?></div>
      <div style="font-size: 14px; color: #666; margin-top: 8px;"><?php echo htmlspecialchars($t['vehicles']); ?> - <?php echo htmlspecialchars($t['inactive']); ?></div>
    </div>
    <div class="card" style="padding: clamp(1.5rem, 2vw, 2rem); text-align: center;">
      <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo $vehicle_active + $vehicle_inactive; ?></div>
      <div style="font-size: 14px; color: #666; margin-top: 8px;"><?php echo htmlspecialchars($t['vehicles']); ?> - <?php echo htmlspecialchars($t['total']); ?></div>
    </div>
  </div>

  <div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: clamp(1rem, 2vw, 2rem);">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1 class="h1-compact"><?= htmlspecialchars($t['admins_list']) ?></h1>
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
