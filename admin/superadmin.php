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

// Check if user is admin
if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// Check if admin is superadmin (first 10 admins are considered superadmin)
$admin_email = $_SESSION['email_Admin'];
$admin_query = mysqli_query($con, "SELECT userid, name FROM admin WHERE email = '$admin_email' AND userid <= 10");
$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    header('Location: /admin/dashboard.php');
    exit();
}

// Language system
if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Superadmin',
    'title'   => 'Anjung superadmin',
    'total_vehicles' => 'Jumlah kenderaan',
    'staff_vehicles' => 'Kenderaan staf',
    'student_vehicles' => 'Kenderaan pelajar',
    'visitor_vehicles' => 'Kenderaan pelawat',
    'contractor_vehicles' => 'Kenderaan kontraktor',
    'admin_management' => 'Pengurusan admin',
    'add_new_admin' => 'Tambah admin',
    'add_new_user' => 'Tambah pengguna',
    'email' => 'Emel',
    'name'  => 'Nama',
    'password' => 'Kata laluan',
    'add' => 'Simpan',
    'list_admins' => 'Senarai admin',
    'list_users'  => 'Senarai pengguna',
    'admin_email' => 'Emel admin',
    'admin_name'  => 'Nama admin',
    'last_login'  => 'Log masuk terakhir',
    'user_email'  => 'Emel pengguna',
    'user_name'   => 'Nama pengguna',
    'success_message' => 'Berjaya disimpan.',
    'error_message' => 'Berlaku ralat.',
] : [
    'eyebrow' => 'Superadmin',
    'title'   => 'Superadmin dashboard',
    'total_vehicles' => 'Total vehicles',
    'staff_vehicles' => 'Staff vehicles',
    'student_vehicles' => 'Student vehicles',
    'visitor_vehicles' => 'Visitor vehicles',
    'contractor_vehicles' => 'Contractor vehicles',
    'admin_management' => 'Admin management',
    'add_new_admin' => 'Add admin',
    'add_new_user'  => 'Add user',
    'email' => 'Email',
    'name'  => 'Name',
    'password' => 'Password',
    'add' => 'Save',
    'list_admins' => 'Admins',
    'list_users'  => 'Users',
    'admin_email' => 'Email',
    'admin_name'  => 'Name',
    'last_login'  => 'Last login',
    'user_email'  => 'Email',
    'user_name'   => 'Name',
    'success_message' => 'Saved.',
    'error_message' => 'An error occurred.',
];

// Vehicle statistics
$staff_query = mysqli_query($con, "SELECT COUNT(*) as count FROM staffcar");
$staff_count = $staff_query ? (int)mysqli_fetch_assoc($staff_query)['count'] : 0;

$student_query = mysqli_query($con, "SELECT COUNT(*) as count FROM studentcar");
$student_count = $student_query ? (int)mysqli_fetch_assoc($student_query)['count'] : 0;

$visitor_count = 0;
$visitor_query = @mysqli_query($con, "SELECT COUNT(*) as count FROM visitorcar");
if ($visitor_query) { $visitor_count = (int)mysqli_fetch_assoc($visitor_query)['count']; }

$contractor_count = 0;
$contractor_query = @mysqli_query($con, "SELECT COUNT(*) as count FROM contractorcar");
if ($contractor_query) { $contractor_count = (int)mysqli_fetch_assoc($contractor_query)['count']; }

$total_count = $staff_count + $student_count + $visitor_count + $contractor_count;

$admins_query = mysqli_query($con, "SELECT userid, email, name, last_login FROM admin ORDER BY userid DESC LIMIT 10");
$admins = [];
while ($admins_query && $row = mysqli_fetch_assoc($admins_query)) { $admins[] = $row; }

$users_query = mysqli_query($con, "SELECT userid, email, name, last_login FROM user ORDER BY userid DESC LIMIT 10");
$users = [];
while ($users_query && $row = mysqli_fetch_assoc($users_query)) { $users[] = $row; }

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
<body>
<div class="nv-shell">
<?php $nv_active = 'admin'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['title']) ?></h1>
    </div>
  </div>

  <div class="kpi-grid">
    <a href="/admin/vehicle_list.php?type=staff" style="text-decoration:none;color:inherit;">
      <div class="kpi dark"><div class="lbl"><?= htmlspecialchars($t['total_vehicles']) ?></div><div class="val"><?= $total_count ?></div></div>
    </a>
    <a href="/admin/vehicle_list.php?type=staff" style="text-decoration:none;color:inherit;">
      <div class="kpi"><div class="lbl"><?= htmlspecialchars($t['staff_vehicles']) ?></div><div class="val"><?= $staff_count ?></div></div>
    </a>
    <a href="/admin/vehicle_list.php?type=student" style="text-decoration:none;color:inherit;">
      <div class="kpi"><div class="lbl"><?= htmlspecialchars($t['student_vehicles']) ?></div><div class="val"><?= $student_count ?></div></div>
    </a>
    <a href="/admin/vehicle_list.php?type=visitor" style="text-decoration:none;color:inherit;">
      <div class="kpi signal"><div class="lbl"><?= htmlspecialchars($t['visitor_vehicles']) ?></div><div class="val"><?= $visitor_count ?></div></div>
    </a>
  </div>

  <div class="nv-grid cols-2 mt-6">
    <a href="/admin/vehicle_list.php?type=contractor" style="text-decoration:none;color:inherit;">
      <div class="kpi"><div class="lbl"><?= htmlspecialchars($t['contractor_vehicles']) ?></div><div class="val"><?= $contractor_count ?></div></div>
    </a>
  </div>

  <div class="card mt-6">
    <div class="nv-row between mb-4">
      <h3><?= htmlspecialchars($t['admin_management']) ?></h3>
    </div>

    <div class="nv-grid cols-2">
      <div>
        <span class="eyebrow"><?= htmlspecialchars($t['add_new_admin']) ?></span>
        <form id="addAdminForm" method="POST" action="/api/admin_management_api.php" class="nv-stack mt-2">
          <input type="hidden" name="action" value="add_admin">
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['email']) ?></label><input class="input" type="email" name="email" required></div>
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['name']) ?></label><input class="input" type="text" name="name" required></div>
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['password']) ?></label><input class="input" type="password" name="password" required></div>
          <div class="nv-row end"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?= htmlspecialchars($t['add']) ?></button></div>
        </form>
      </div>
      <div>
        <span class="eyebrow"><?= htmlspecialchars($t['add_new_user']) ?></span>
        <form id="addUserForm" method="POST" action="/api/admin_management_api.php" class="nv-stack mt-2">
          <input type="hidden" name="action" value="add_user">
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['email']) ?></label><input class="input" type="email" name="email" required></div>
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['name']) ?></label><input class="input" type="text" name="name" required></div>
          <div class="field"><label class="field-label"><?= htmlspecialchars($t['password']) ?></label><input class="input" type="password" name="password" required></div>
          <div class="nv-row end"><button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <?= htmlspecialchars($t['add']) ?></button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="nv-grid cols-2 mt-6">
    <div class="card">
      <span class="eyebrow"><?= htmlspecialchars($t['list_admins']) ?></span>
      <table class="table mt-2">
        <thead><tr><th><?= htmlspecialchars($t['admin_email']) ?></th><th><?= htmlspecialchars($t['admin_name']) ?></th><th><?= htmlspecialchars($t['last_login']) ?></th></tr></thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
            <tr><td><?= htmlspecialchars($a['email']) ?></td><td><?= htmlspecialchars($a['name']) ?></td><td class="meta"><?= htmlspecialchars($a['last_login'] ?? '—') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card">
      <span class="eyebrow"><?= htmlspecialchars($t['list_users']) ?></span>
      <table class="table mt-2">
        <thead><tr><th><?= htmlspecialchars($t['user_email']) ?></th><th><?= htmlspecialchars($t['user_name']) ?></th><th><?= htmlspecialchars($t['last_login']) ?></th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['name']) ?></td><td class="meta"><?= htmlspecialchars($u['last_login'] ?? '—') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  (function(){
    var ok = '<?= addslashes($t['success_message']) ?>';
    var bad = '<?= addslashes($t['error_message']) ?>';
    function bind(id){
      var f = document.getElementById(id);
      if (!f) return;
      f.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(f);
        fetch('/api/admin_management_api.php', { method:'POST', body: fd })
          .then(r => r.json())
          .then(d => { if (d.success) { alert(ok); f.reset(); location.reload(); } else { alert(d.message || bad); } })
          .catch(err => alert(bad + ': ' + err));
      });
    }
    bind('addAdminForm'); bind('addUserForm');
  })();
  </script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
