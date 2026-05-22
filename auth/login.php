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

// Language texts
$text = [];

$text['bm'] = [
    'page_title' => 'Log masuk pengguna',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading' => 'Log masuk ke NEO V-TRACK',
    'subhead' => 'Gunakan emel berdaftar anda untuk mengakses rekod kenderaan.',
    'email_label' => 'Emel',
    'email_placeholder' => 'nama@uitm.edu.my',
    'password_label' => 'Kata laluan',
    'login_button' => 'Log masuk',
    'forgot_password' => 'Lupa kata laluan?',
    'switch_to_admin' => 'Log masuk sebagai admin',
    'new_user_question' => 'Tiada akaun?',
    'register_here' => 'Daftar',
    'invalid_credentials' => 'Emel atau kata laluan tidak sah.',
    'brand_sub' => 'Log masuk'
];

$text['en'] = [
    'page_title' => 'User sign in',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading' => 'Sign in to NEO V-TRACK',
    'subhead' => 'Use your registered email to access vehicle records.',
    'email_label' => 'Email',
    'email_placeholder' => 'name@uitm.edu.my',
    'password_label' => 'Password',
    'login_button' => 'Sign in',
    'forgot_password' => 'Forgot your password?',
    'switch_to_admin' => 'Sign in as admin',
    'new_user_question' => 'Need an account?',
    'register_here' => 'Register',
    'invalid_credentials' => 'Invalid email or password.',
    'brand_sub' => 'Sign in'
];

$t = $text[$lang];

$invalid = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    $sql = "SELECT * FROM user WHERE email='$email' AND password='$password'";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);

        $_SESSION['email'] = $email;
        $_SESSION['nama'] = $user_data['name'] ?? $email;
        $_SESSION['user_type'] = 'user';
        $_SESSION['userid'] = $user_data['userid'] ?? null;

        mysqli_query($con, "UPDATE user SET last_login = NOW() WHERE email = '$email'");

        header("Location: /admin/index_user.php");
        exit();
    } else {
        $invalid = $t['invalid_credentials'];
    }
}

// Build links with language parameter
$lang_param = isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "";
$forgot_link = "/auth/forgot_password.php" . $lang_param;
$admin_login_link = "/auth/login_admin.php" . $lang_param;
$register_link = "/auth/register.php" . $lang_param;

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="auth-hero">
  <form class="auth-card" method="post" action="">
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

    <?php if (!empty($invalid)): ?>
      <div class="flash bad"><i data-lucide="alert-circle"></i><span><?= htmlspecialchars($invalid) ?></span></div>
    <?php endif; ?>

    <div class="field">
      <label class="field-label" for="email"><?= htmlspecialchars($t['email_label']) ?></label>
      <input class="input" id="email" name="email" type="email" required placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>">
    </div>
    <div class="field">
      <label class="field-label" for="password"><?= htmlspecialchars($t['password_label']) ?></label>
      <input class="input" id="password" name="password" type="password" required>
    </div>

    <button class="btn btn-primary" type="submit" style="justify-content:center;width:100%;">
      <?= htmlspecialchars($t['login_button']) ?> <i data-lucide="arrow-right"></i>
    </button>

    <div class="auth-foot">
      <a href="<?= htmlspecialchars($forgot_link) ?>"><?= htmlspecialchars($t['forgot_password']) ?></a>
      <span><?= htmlspecialchars($t['new_user_question']) ?> <a href="<?= htmlspecialchars($register_link) ?>"><?= htmlspecialchars($t['register_here']) ?></a></span>
      <span><a href="<?= htmlspecialchars($admin_login_link) ?>"><?= htmlspecialchars($t['switch_to_admin']) ?></a></span>
      <span class="text-mono" style="font-size:11px;">
        <a href="?lang=bm" style="<?= $lang == 'bm' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">BM</a>
        ·
        <a href="?lang=en" style="<?= $lang == 'en' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">EN</a>
      </span>
    </div>
  </form>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
