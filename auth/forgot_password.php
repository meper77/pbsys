<?php
session_start();

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

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
    'page_title' => 'Reset kata laluan',
    'eyebrow' => 'Polis Bantuan · UiTM',
    'heading_1' => 'Reset kata laluan anda',
    'subhead_1' => 'Masukkan emel berdaftar anda untuk meneruskan.',
    'heading_2' => 'Tetapkan kata laluan baharu',
    'subhead_2' => 'Pilih kata laluan baharu untuk akaun anda.',
    'heading_3' => 'Kata laluan telah dikemas kini',
    'subhead_3' => 'Anda akan diarahkan ke halaman log masuk sebentar lagi.',
    'email_label' => 'Emel',
    'email_placeholder' => 'nama@uitm.edu.my',
    'password_label' => 'Kata laluan baharu',
    'confirm_label' => 'Sahkan kata laluan',
    'password_hint' => 'Sekurang-kurangnya 6 aksara.',
    'show_password' => 'Tunjukkan kata laluan',
    'hide_password' => 'Sembunyikan kata laluan',
    'submit' => 'Teruskan',
    'reset' => 'Kemas kini kata laluan',
    'back_to_login' => 'Kembali ke log masuk',
    'email_not_found' => 'Emel tidak wujud dalam sistem.',
    'password_mismatch' => 'Kata laluan tidak sepadan.',
    'password_short' => 'Kata laluan mesti sekurang-kurangnya 6 aksara.',
    'reset_error' => 'Ralat mengemas kini kata laluan. Sila cuba lagi.',
    'redirecting_in' => 'Mengarahkan dalam',
    'seconds' => 'saat',
    'brand_sub' => 'Reset'
];

$text['en'] = [
    'page_title' => 'Reset password',
    'eyebrow' => 'Auxiliary Police · UiTM',
    'heading_1' => 'Reset your password',
    'subhead_1' => 'Enter your registered email to continue.',
    'heading_2' => 'Set a new password',
    'subhead_2' => 'Choose a new password for your account.',
    'heading_3' => 'Password updated',
    'subhead_3' => 'You will be redirected to the sign-in page shortly.',
    'email_label' => 'Email',
    'email_placeholder' => 'name@uitm.edu.my',
    'password_label' => 'New password',
    'confirm_label' => 'Confirm password',
    'password_hint' => 'At least 6 characters.',
    'show_password' => 'Show password',
    'hide_password' => 'Hide password',
    'submit' => 'Continue',
    'reset' => 'Update password',
    'back_to_login' => 'Back to sign in',
    'email_not_found' => 'Email does not exist in the system.',
    'password_mismatch' => 'Passwords do not match.',
    'password_short' => 'Password must be at least 6 characters.',
    'reset_error' => 'Error updating password. Please try again.',
    'redirecting_in' => 'Redirecting in',
    'seconds' => 'seconds',
    'brand_sub' => 'Reset'
];

$t = $text[$lang];

$message = "";
$message_type = ""; // success or bad
$step = 1;
$user_email = "";

if (isset($_POST['step1'])) {
    $email = mysqli_real_escape_string($con, $_POST['email']);

    $check_query = "SELECT * FROM user WHERE email = '$email'";
    $result = mysqli_query($con, $check_query);

    if (mysqli_num_rows($result) > 0) {
        $step = 2;
        $user_email = $email;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_type'] = 'user';
    } else {
        $message = $t['email_not_found'];
        $message_type = "bad";
        $step = 1;
    }
}

if (isset($_POST['step2'])) {
    $email = $_SESSION['reset_email'];
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);

    if (strlen($password) < 6) {
        $message = $t['password_short'];
        $message_type = "bad";
        $step = 2;
        $user_email = $email;
    } elseif ($password !== $confirm_password) {
        $message = $t['password_mismatch'];
        $message_type = "bad";
        $step = 2;
        $user_email = $email;
    } else {
        $update_query = "UPDATE user SET password = '$password' WHERE email = '$email'";

        if (mysqli_query($con, $update_query)) {
            $message_type = "ok";
            $step = 3;

            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_type']);
        } else {
            $message = $t['reset_error'] . ": " . mysqli_error($con);
            $message_type = "bad";
            $step = 2;
            $user_email = $email;
        }
    }
}

$login_link = "/auth/login.php" . (isset($_SESSION['language']) ? "?lang=" . $_SESSION['language'] : "");

include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php';
?>
<body>
<div class="auth-hero">
  <div class="auth-card">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
      <div class="divider"></div>
      <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
      <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub"><?= htmlspecialchars($t['brand_sub']) ?></span></div>
    </div>

    <?php if ($step == 1): ?>
      <div class="auth-head">
        <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?> · Step 1 / 2</span>
        <h1><?= htmlspecialchars($t['heading_1']) ?></h1>
        <p><?= htmlspecialchars($t['subhead_1']) ?></p>
      </div>

      <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>"><i data-lucide="alert-circle"></i><span><?= htmlspecialchars($message) ?></span></div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="step1" value="1">
        <div class="field">
          <label class="field-label" for="email"><?= htmlspecialchars($t['email_label']) ?></label>
          <input class="input" id="email" name="email" type="email" required
                 placeholder="<?= htmlspecialchars($t['email_placeholder']) ?>"
                 value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div class="flex gap-2 mt-4" style="flex-direction:column;">
          <button class="btn btn-primary" type="submit" style="justify-content:center;width:100%;">
            <?= htmlspecialchars($t['submit']) ?> <i data-lucide="arrow-right"></i>
          </button>
          <a class="btn btn-ghost" href="<?= htmlspecialchars($login_link) ?>" style="justify-content:center;width:100%;">
            <i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back_to_login']) ?>
          </a>
        </div>
      </form>

    <?php elseif ($step == 2): ?>
      <div class="auth-head">
        <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?> · Step 2 / 2</span>
        <h1><?= htmlspecialchars($t['heading_2']) ?></h1>
        <p><?= htmlspecialchars($t['subhead_2']) ?></p>
      </div>

      <?php if ($message): ?>
        <div class="flash <?= htmlspecialchars($message_type) ?>"><i data-lucide="alert-circle"></i><span><?= htmlspecialchars($message) ?></span></div>
      <?php endif; ?>

      <form method="post" action="" id="step2Form">
        <input type="hidden" name="step2" value="1">

        <div class="field">
          <label class="field-label"><?= htmlspecialchars($t['email_label']) ?></label>
          <div class="input" style="background:var(--surface-alt);color:var(--fg-3);"><?= htmlspecialchars($user_email) ?></div>
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

        <div class="flex gap-2 mt-4" style="flex-direction:column;">
          <button class="btn btn-primary" type="submit" style="justify-content:center;width:100%;">
            <?= htmlspecialchars($t['reset']) ?> <i data-lucide="save"></i>
          </button>
          <a class="btn btn-ghost" href="<?= htmlspecialchars($login_link) ?>" style="justify-content:center;width:100%;">
            <i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back_to_login']) ?>
          </a>
        </div>
      </form>

    <?php else: // step 3 ?>
      <div class="auth-head">
        <span class="eyebrow"><?= htmlspecialchars($t['eyebrow']) ?></span>
        <h1><?= htmlspecialchars($t['heading_3']) ?></h1>
        <p><?= htmlspecialchars($t['subhead_3']) ?></p>
      </div>

      <div class="flash ok"><i data-lucide="check-circle"></i><span><?= htmlspecialchars($t['heading_3']) ?>.</span></div>

      <div class="text-center text-muted text-mono" style="font-size:12px;">
        <?= htmlspecialchars($t['redirecting_in']) ?>
        <span id="redirectCountdown" style="color:var(--brand-purple);font-weight:700;">5</span>
        <?= htmlspecialchars($t['seconds']) ?>
      </div>

      <a class="btn btn-primary" href="<?= htmlspecialchars($login_link) ?>" style="justify-content:center;width:100%;">
        <i data-lucide="arrow-left"></i> <?= htmlspecialchars($t['back_to_login']) ?>
      </a>

      <script>
        (function(){
          var n = 5;
          var el = document.getElementById('redirectCountdown');
          var iv = setInterval(function(){
            n--; if (el) el.textContent = n;
            if (n <= 0) { clearInterval(iv); window.location.href = <?= json_encode($login_link) ?>; }
          }, 1000);
        })();
      </script>
    <?php endif; ?>

    <div class="auth-foot">
      <span class="text-mono" style="font-size:11px;">
        <a href="?lang=bm" style="<?= $lang == 'bm' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">BM</a>
        ·
        <a href="?lang=en" style="<?= $lang == 'en' ? 'font-weight:700;' : 'color:var(--fg-3);' ?>">EN</a>
      </span>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var f = document.getElementById('step2Form');
  if (!f) return;
  f.addEventListener('submit', function(e) {
    var p = document.getElementById('password').value;
    var c = document.getElementById('confirm_password').value;
    if (p.length < 6) { e.preventDefault(); alert(<?= json_encode($t['password_short']) ?>); return false; }
    if (p !== c) { e.preventDefault(); alert(<?= json_encode($t['password_mismatch']) ?>); return false; }
  });
});
</script>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
