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
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . ($_GET['id'] ?? ''));
    exit();
}
$lang = $_SESSION['language'];

$t = $lang === 'bm' ? [
    'eyebrow' => 'Pengguna',
    'title' => 'Kemaskini pengguna',
    'back' => 'Kembali',
    'email' => 'Emel', 'password' => 'Kata laluan', 'name' => 'Nama',
    'email_placeholder' => 'Isi emel',
    'password_placeholder' => 'Isi kata laluan',
    'name_placeholder'  => 'Nama penuh',
    'save' => 'Simpan', 'cancel' => 'Batal',
    'update_success' => 'Pengguna berjaya dikemaskini.',
] : [
    'eyebrow' => 'Users',
    'title' => 'Update user',
    'back' => 'Back',
    'email' => 'Email', 'password' => 'Password', 'name' => 'Name',
    'email_placeholder' => 'Enter email',
    'password_placeholder' => 'Enter password',
    'name_placeholder'  => 'Full name',
    'save' => 'Save', 'cancel' => 'Cancel',
    'update_success' => 'User updated.',
];

$flash = '';
if (isset($_POST['submit'])) {
    $id = intval($_GET['id']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $name = mysqli_real_escape_string($con, $_POST['name']);

    $sql = "UPDATE `user` SET email='$email', password='$password', name='$name' WHERE userid=$id";
    if (mysqli_query($con, $sql)) {
        $_SESSION['success_message'] = $t['update_success'];
        header('location:/admin/users.php');
        exit();
    } else {
        $flash = '<div class="flash bad"><i data-lucide="alert-triangle"></i> ' . htmlspecialchars(mysqli_error($con)) . '</div>';
    }
}

$current_email = $current_password = $current_name = '';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = mysqli_query($con, "SELECT * FROM `user` WHERE userid=$id");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_email = $row['email'];
        $current_password = $row['password'];
        $current_name = $row['name'];
    } else {
        header('location:/admin/users.php');
        exit();
    }
} else {
    header('location:/admin/users.php');
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
<link rel="stylesheet" href="/assets/css/neo-vtrack-app.css">
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
      <a class="btn btn-ghost" href="/admin/users.php"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <?= $flash ?>

  <div class="card">
    <form method="POST" class="nv-stack">
      <div class="field">
        <label class="field-label" for="email"><?= htmlspecialchars($t['email']) ?></label>
        <input class="input" type="email" id="email" name="email" value="<?= htmlspecialchars($current_email) ?>" placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="password"><?= htmlspecialchars($t['password']) ?></label>
        <input class="input" type="text" id="password" name="password" value="<?= htmlspecialchars($current_password) ?>" placeholder="<?= htmlspecialchars($t['password_placeholder']) ?>" required>
      </div>
      <div class="field">
        <label class="field-label" for="name"><?= htmlspecialchars($t['name']) ?></label>
        <input class="input" type="text" id="name" name="name" value="<?= htmlspecialchars($current_name) ?>" placeholder="<?= htmlspecialchars($t['name_placeholder']) ?>" required>
      </div>
      <div class="nv-row end">
        <a href="/admin/users.php" class="btn btn-ghost"><i data-lucide="x"></i> <?= htmlspecialchars($t['cancel']) ?></a>
        <button type="submit" name="submit" class="btn btn-primary"><i data-lucide="save"></i> <?= htmlspecialchars($t['save']) ?></button>
      </div>
    </form>
  </div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
