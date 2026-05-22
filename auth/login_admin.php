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
    'page_title' => 'Log masuk pentadbir',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading' => 'Log masuk pentadbir',
    'subhead' => 'Akses panel pengurusan kenderaan dan pengguna.',
    'email_label' => 'Emel admin',
    'email_placeholder' => 'admin@uitm.edu.my',
    'password_label' => 'Kata laluan',
    'login_button' => 'Log masuk',
    'forgot_password' => 'Lupa kata laluan?',
    'switch_to_user' => 'Log masuk sebagai pengguna',
    'invalid_credentials' => 'Emel atau kata laluan tidak sah.',
    'brand_sub' => 'Admin'
];

$text['en'] = [
    'page_title' => 'Admin sign in',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading' => 'Admin sign in',
    'subhead' => 'Access the vehicle and user management panel.',
    'email_label' => 'Admin email',
    'email_placeholder' => 'admin@uitm.edu.my',
    'password_label' => 'Password',
    'login_button' => 'Sign in',
    'forgot_password' => 'Forgot your password?',
    'switch_to_user' => 'Sign in as user',
    'invalid_credentials' => 'Invalid email or password.',
    'brand_sub' => 'Admin'
];

$t = $text[$lang];

$invalid = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
    $email = $_POST['email_Admin'];
    $password = $_POST['password_Admin'];

    $sql = "SELECT * FROM admin WHERE email='$email' AND password='$password'";
    $result = mysqli_query($con, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['email_Admin'] = $email;
        $_SESSION['user_type'] = 'admin';

        // Track session
        $session_id = session_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $admin_id_query = mysqli_query($con, "SELECT userid FROM admin WHERE email = '$email'");
        $admin_id_data = mysqli_fetch_assoc($admin_id_query);
        $admin_id = $admin_id_data['userid'] ?? NULL;

        mysqli_query($con, "UPDATE admin SET last_login = NOW() WHERE email = '$email'");

        $session_query = "INSERT INTO user_sessions (user_id, session_id, email, user_type, login_time, last_activity, ip_address, user_agent)
                          VALUES ('$admin_id', '$session_id', '$email', 'admin', NOW(), NOW(), '$ip_address', '$user_agent')
                          ON DUPLICATE KEY UPDATE last_activity = NOW()";
        mysqli_query($con, $session_query);

        header("Location: /index.php");
        exit();
    } else {
        $invalid = $t['invalid_credentials'];
    }
}

$lang_param = isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "";
$forgot_link = "/auth/forgot_password_admin.php" . $lang_param;
$user_login_link = "/auth/login.php" . $lang_param;

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="auth-hero">
  <form class="auth-card" method="post" action="">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm-logo-white.png" alt="UiTM" style="filter:invert(1) brightness(0);">
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
      <label class="field-label" for="email_Admin"><?= htmlspecialchars($t['email_label']) ?></label>
      <input class="input" id="email_Admin" name="email_Admin" type="email" required placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>">
    </div>
    <div class="field">
      <label class="field-label" for="password_Admin"><?= htmlspecialchars($t['password_label']) ?></label>
      <input class="input" id="password_Admin" name="password_Admin" type="password" required>
    </div>

    <button class="btn btn-primary" type="submit" style="justify-content:center;width:100%;">
      <?= htmlspecialchars($t['login_button']) ?> <i data-lucide="shield-check"></i>
    </button>

    <div class="auth-foot">
      <a href="<?= htmlspecialchars($forgot_link) ?>"><?= htmlspecialchars($t['forgot_password']) ?></a>
      <span><a href="<?= htmlspecialchars($user_login_link) ?>"><?= htmlspecialchars($t['switch_to_user']) ?></a></span>
      <span class="text-mono" style="font-size:11px;">
        <a href="?lang=bm" style="<?= $lang == 'bm' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">BM</a>
        ·
        <a href="?lang=en" style="<?= $lang == 'en' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">EN</a>
      </span>
    </div>
  </form>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
