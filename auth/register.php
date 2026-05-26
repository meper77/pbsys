<?php
session_start();

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

$text = [];

$text['bm'] = [
    'page_title' => 'Daftar pengguna baharu',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading' => 'Cipta akaun pengguna',
    'subhead' => 'Daftar untuk mengakses sistem UiTM.',
    'name_label' => 'Nama penuh',
    'email_label' => 'Emel',
    'password_label' => 'Kata laluan',
    'confirm_label' => 'Sahkan kata laluan',
    'password_hint' => 'Sekurang-kurangnya 6 aksara.',
    'register_button' => 'Daftar',
    'back_to_login' => 'Kembali ke log masuk',
    'registration_success' => 'Pendaftaran berjaya. Sila log masuk dengan emel dan kata laluan anda.',
    'registration_error' => 'Ralat pendaftaran. Sila cuba lagi atau hubungi pentadbir.',
    'email_exists' => 'Emel ini sudah didaftarkan. Sila gunakan emel lain.',
    'passwords_mismatch' => 'Kata laluan tidak sepadan.',
    'password_requirements' => 'Kata laluan mesti sekurang-kurangnya 6 aksara.',
    'fill_all_fields' => 'Sila isi semua ruangan.',
    'brand_sub' => 'Daftar'
];

$text['en'] = [
    'page_title' => 'New user registration',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading' => 'Create your user account',
    'subhead' => 'Register to access UiTM vehicle pass records.',
    'name_label' => 'Full name',
    'email_label' => 'Email',
    'password_label' => 'Password',
    'confirm_label' => 'Confirm password',
    'password_hint' => 'At least 6 characters.',
    'register_button' => 'Register',
    'back_to_login' => 'Back to sign in',
    'registration_success' => 'Registration successful. Please sign in with your email and password.',
    'registration_error' => 'Registration error. Please try again or contact the administrator.',
    'email_exists' => 'Email already registered. Please use another email.',
    'passwords_mismatch' => 'Passwords do not match.',
    'password_requirements' => 'Password must be at least 6 characters.',
    'fill_all_fields' => 'Please fill in all fields.',
    'brand_sub' => 'Register'
];

$t = $text[$lang];

$message = "";
$message_type = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = $t['fill_all_fields'];
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = $t['password_requirements'];
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = $t['passwords_mismatch'];
        $message_type = 'error';
    } else {
        $check_email = mysqli_query($con, "SELECT * FROM user WHERE email='$email'");

        if (mysqli_num_rows($check_email) > 0) {
            $message = $t['email_exists'];
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO user (name, email, password) VALUES ('$name', '$email', '$password')";

            if (mysqli_query($con, $sql)) {
                $message = $t['registration_success'];
                $message_type = 'success';
            } else {
                $message = $t['registration_error'];
                $message_type = 'error';
            }
        }
    }
}

$login_link = "/auth/login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="auth-hero">
  <form class="auth-card wide" method="post" action="">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
      <div class="divider"></div>
      <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
      <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub"><?= htmlspecialchars($t['brand_sub']) ?></span></div>
    </div>
    <div class="auth-head">
      <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
      <h1><?= htmlspecialchars($t['heading']) ?></h1>
      <p><?= htmlspecialchars($t['subhead']) ?></p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="flash <?= $message_type === 'success' ? 'ok' : 'bad' ?>">
        <i data-lucide="<?= $message_type === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
      </div>
    <?php endif; ?>

    <div class="field">
      <label class="field-label" for="name"><?= htmlspecialchars($t['name_label']) ?></label>
      <input class="input" id="name" name="name" type="text" required>
    </div>

    <div class="field">
      <label class="field-label" for="email"><?= htmlspecialchars($t['email_label']) ?></label>
      <input class="input" id="email" name="email" type="email" required placeholder="name@uitm.edu.my">
    </div>

    <div class="field">
      <label class="field-label" for="password"><?= htmlspecialchars($t['password_label']) ?></label>
      <input class="input" id="password" name="password" type="password" required minlength="6">
      <span class="field-hint"><?= htmlspecialchars($t['password_hint']) ?></span>
    </div>

    <div class="field">
      <label class="field-label" for="confirm_password"><?= htmlspecialchars($t['confirm_label']) ?></label>
      <input class="input" id="confirm_password" name="confirm_password" type="password" required minlength="6">
    </div>

    <button class="btn btn-primary btn-full-width" type="submit">
      <?= htmlspecialchars($t['register_button']) ?>
    </button>

    <div class="auth-foot">
      <a href="<?= htmlspecialchars($login_link) ?>"><i data-lucide="arrow-left" class="icon-small"></i> <?= htmlspecialchars($t['back_to_login']) ?></a>
      <span class="text-mono lang-selector">
        <a href="?lang=bm" class="<?= $lang == 'bm' ? 'lang-active' : 'lang-inactive' ?>">BM</a>
        ·
        <a href="?lang=en" class="<?= $lang == 'en' ? 'lang-active' : 'lang-inactive' ?>">EN</a>
      </span>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var passwordField = document.getElementById('password');
    var confirmField = document.getElementById('confirm_password');
    if (passwordField && confirmField) {
        function check() {
            if (confirmField.value === '') {
                confirmField.style.borderColor = '';
                return;
            }
            confirmField.style.borderColor = (passwordField.value === confirmField.value)
                ? 'var(--status-ok)' : 'var(--status-bad)';
        }
        confirmField.addEventListener('input', check);
        passwordField.addEventListener('input', check);
    }
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
