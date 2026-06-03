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
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pentadbir',
    'title'   => 'Tambah admin',
    'back'    => 'Kembali',
    'email'   => 'Emel',
    'password' => 'Kata laluan',
    'admin_name' => 'Nama admin',
    'save'    => 'Simpan',
    'cancel'  => 'Batal',
    'email_placeholder' => 'cth: admin@uitm.edu.my',
    'password_placeholder' => 'Sekurang-kurangnya 6 aksara',
    'name_placeholder'  => 'Nama penuh',
    'registration_success' => 'Admin berjaya didaftar.',
    'email_exists' => 'Emel sudah wujud.',
    'invalid_email' => 'Format emel tidak sah.',
    'password_short' => 'Kata laluan mesti sekurang-kurangnya 6 aksara.',
] : [
    'eyebrow' => 'Administration',
    'title'   => 'Add admin',
    'back'    => 'Back',
    'email'   => 'Email',
    'password' => 'Password',
    'admin_name' => 'Admin name',
    'save'    => 'Save',
    'cancel'  => 'Cancel',
    'email_placeholder' => 'e.g. admin@uitm.edu.my',
    'password_placeholder' => 'At least 6 characters',
    'name_placeholder'  => 'Full name',
    'registration_success' => 'Admin registered.',
    'email_exists' => 'Email already exists.',
    'invalid_email' => 'Invalid email format.',
    'password_short' => 'Password must be at least 6 characters.',
];

$flash = '';
if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($con, $_POST['email_Admin']);
    $password = mysqli_real_escape_string($con, $_POST['password_Admin']);
    $name = mysqli_real_escape_string($con, $_POST['name_Admin']);

    $errors = [];
    $check_email = mysqli_query($con, "SELECT * FROM admin WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) { $errors[] = $t['email_exists']; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = $t['invalid_email']; }
    if (strlen($password) < 6) { $errors[] = $t['password_short']; }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO `admin` (`email`, `password`, `name`) VALUES('$email','$hashed_password','$name')";
        if (mysqli_query($con, $sql)) {
            $_SESSION['success_message'] = $t['registration_success'];
            header('location:/admin/admins.php');
            exit();
        } else {
            $errors[] = mysqli_error($con);
        }
    }
    foreach ($errors as $err) {
        $flash .= '<div class="flash bad"><i data-lucide="alert-triangle"></i> ' . htmlspecialchars($err) . '</div>';
    }
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
      <a class="btn btn-ghost" href="/admin/admins.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <?= $flash ?>

  <div class="card">
    <form method="POST" class="nv-stack" id="adminForm">
      <div class="field">
        <label class="field-label" for="email_Admin"><?= htmlspecialchars($t['email']) ?></label>
        <input class="input" type="email" id="email_Admin" name="email_Admin" placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="password_Admin"><?= htmlspecialchars($t['password']) ?></label>
        <input class="input" type="password" id="password_Admin" name="password_Admin" placeholder="<?= htmlspecialchars($t['password_placeholder']) ?>" required minlength="6">
      </div>
      <div class="field">
        <label class="field-label" for="name_Admin"><?= htmlspecialchars($t['admin_name']) ?></label>
        <input class="input" type="text" id="name_Admin" name="name_Admin" placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" required>
      </div>
      <div class="nv-row end">
        <a href="/admin/admins.php" class="btn btn-ghost"><i data-lucide="x"></i> <?= htmlspecialchars($t['cancel']) ?></a>
        <button type="submit" name="submit" class="btn btn-primary"><i data-lucide="save"></i> <?= htmlspecialchars($t['save']) ?></button>
      </div>
    </form>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
