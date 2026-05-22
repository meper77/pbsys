<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /auth/role_selection.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

if (!isset($_SESSION['language'])) { $_SESSION['language'] = 'bm'; }
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] == 'en') ? 'en' : 'bm';
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $_GET['id']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pentadbir',
    'title' => 'Kemaskini admin',
    'email' => 'Emel', 'password' => 'Kata laluan', 'name' => 'Nama',
    'email_placeholder' => 'Isi emel',
    'password_placeholder' => 'Isi kata laluan baharu',
    'name_placeholder' => 'Nama penuh',
    'save' => 'Simpan', 'back' => 'Kembali',
    'update_success' => 'Admin berjaya dikemaskini.',
    'update_failed' => 'Gagal mengemaskini',
    'email_exists' => 'Emel sudah wujud.',
] : [
    'eyebrow' => 'Administration',
    'title' => 'Update admin',
    'email' => 'Email', 'password' => 'Password', 'name' => 'Name',
    'email_placeholder' => 'Enter email',
    'password_placeholder' => 'Enter new password',
    'name_placeholder' => 'Full name',
    'save' => 'Save', 'back' => 'Back',
    'update_success' => 'Admin updated.',
    'update_failed' => 'Update failed',
    'email_exists' => 'Email already exists.',
];

// Inspect admin columns
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM admin");
$columns = [];
if ($check_columns) {
    while ($col = mysqli_fetch_assoc($check_columns)) { $columns[] = $col['Field']; }
}

$id_column = 'adminid';
if (in_array('adminid', $columns)) { $id_column = 'adminid'; }
elseif (in_array('id', $columns))      { $id_column = 'id'; }
elseif (in_array('userid', $columns))  { $id_column = 'userid'; }
elseif (in_array('admin_id', $columns)){ $id_column = 'admin_id'; }

if (isset($_POST['submit'])) {
    $id = intval($_GET['id']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $name = mysqli_real_escape_string($con, $_POST['name']);

    $update_fields = [];
    if (in_array('email', $columns))            $update_fields['email'] = $email;
    elseif (in_array('email_Admin', $columns))  $update_fields['email_Admin'] = $email;

    if (in_array('password', $columns))             $update_fields['password'] = $password;
    elseif (in_array('password_Admin', $columns))   $update_fields['password_Admin'] = $password;

    if (in_array('name', $columns))             $update_fields['name'] = $name;
    elseif (in_array('name_Admin', $columns))   $update_fields['name_Admin'] = $name;

    $proceed = true;
    $check_query = mysqli_query($con, "SELECT email FROM admin WHERE $id_column = $id");
    if ($check_query && mysqli_num_rows($check_query) > 0) {
        $current = mysqli_fetch_assoc($check_query);
        $current_email = $current['email'] ?? $current['email_Admin'] ?? '';
        if ($email !== $current_email) {
            $email_column = in_array('email', $columns) ? 'email' : 'email_Admin';
            $dup = mysqli_query($con, "SELECT $id_column FROM admin WHERE $email_column = '$email'");
            if (mysqli_num_rows($dup) > 0) {
                $proceed = false;
                echo "<script>alert('" . addslashes($t['email_exists']) . "');</script>";
            }
        }
    }

    if ($proceed) {
        $set_clause = [];
        foreach ($update_fields as $field => $value) { $set_clause[] = "$field = '$value'"; }
        $sql = "UPDATE `admin` SET " . implode(', ', $set_clause) . " WHERE $id_column = $id";
        if (mysqli_query($con, $sql)) {
            echo "<script>alert('" . addslashes($t['update_success']) . "'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        } else {
            $err = $t['update_failed'] . ': ' . mysqli_error($con);
            echo "<script>alert('" . addslashes($err) . "');</script>";
        }
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$email = $password = $name = '';

if ($id > 0) {
    $select_fields = [];
    if (in_array('email', $columns))                $select_fields[] = 'email';
    elseif (in_array('email_Admin', $columns))      $select_fields[] = 'email_Admin as email';
    if (in_array('password', $columns))             $select_fields[] = 'password';
    elseif (in_array('password_Admin', $columns))   $select_fields[] = 'password_Admin as password';
    if (in_array('name', $columns))                 $select_fields[] = 'name';
    elseif (in_array('name_Admin', $columns))       $select_fields[] = 'name_Admin as name';

    $select_sql = "SELECT " . implode(', ', $select_fields) . " FROM `admin` WHERE $id_column = $id";
    $result = mysqli_query($con, $select_sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'] ?? '';
        $password = $row['password'] ?? '';
        $name = $row['name'] ?? '';
    } else {
        echo "<script>alert('Admin not found.'); window.location.href='/admin/dashboard.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid admin ID.'); window.location.href='/admin/dashboard.php';</script>";
    exit();
}

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
    <div class="actions">
      <a class="btn btn-ghost" href="/admin/dashboard.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <div class="card">
    <form method="POST" class="nv-stack">
      <div class="field">
        <label class="field-label" for="email"><?= htmlspecialchars($t['email']) ?></label>
        <input class="input" type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="password"><?= htmlspecialchars($t['password']) ?></label>
        <input class="input" type="text" id="password" name="password" value="<?= htmlspecialchars($password) ?>" placeholder="<?= htmlspecialchars($t['password_placeholder']) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="name"><?= htmlspecialchars($t['name']) ?></label>
        <input class="input" type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" required>
      </div>
      <div class="nv-row end">
        <a href="/admin/dashboard.php" class="btn btn-ghost"><i data-lucide="x"></i> <?= htmlspecialchars($t['back']) ?></a>
        <button type="submit" name="submit" class="btn btn-primary"><i data-lucide="save"></i> <?= htmlspecialchars($t['save']) ?></button>
      </div>
    </form>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
