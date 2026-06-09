<?php
/**
 * NEO V-TRACK — passwordless sign in / sign up (UiTM email + one-time code).
 * Replaces the old email/password login. Role (admin/user) is derived from the
 * admin allowlist. "Remember this device" skips OTP on trusted devices.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/i18n.php';      // session + ?lang
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/otp_auth.php';

function nv_home_for(string $role): string
{
    return $role === 'admin' ? '/index.php' : '/admin/index_user.php';
}

// Already signed in? Go home.
if (nv_is_logged_in()) {
    header('Location: ' . nv_home_for(nv_is_admin() ? 'admin' : 'user'));
    exit;
}

$step  = 'email';
$error = '';
$info  = '';
$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? '';

// Trusted-device auto-login (only a plain GET, no pending OTP).
if ($method === 'GET' && empty($_SESSION['otp_email']) && !empty($_COOKIE[NV_DEVICE_COOKIE])) {
    $dev = nv_check_trusted_device($con);
    if ($dev && nv_valid_uitm_email($dev)) {
        $role = nv_role_for_email($con, $dev);
        nv_ensure_account($con, $dev, $role);
        nv_establish_session($con, $dev, $role);
        header('Location: ' . nv_home_for($role));
        exit;
    }
}

if ($method === 'POST') {
    if ($action === 'change_email') {
        unset($_SESSION['otp_email']);
    } elseif ($action === 'send_code' || $action === 'resend') {
        $email = nv_norm_email($_POST['email'] ?? ($_SESSION['otp_email'] ?? ''));
        if (!nv_valid_uitm_email($email)) {
            $error = t('auth.bad_domain');
        } else {
            $err = null;
            if (nv_create_and_send_otp($con, $email, $err)) {
                $_SESSION['otp_email'] = $email;
                $step = 'code';
                $info = t('auth.code_sent', ['email' => $email]);
            } elseif ($err === 'rate') {
                // A recent code is still valid — let them enter it.
                $_SESSION['otp_email'] = $email;
                $step = 'code';
                $info = t('auth.rate_limited');
            } else {
                $error = t('auth.send_failed');
            }
        }
    } elseif ($action === 'verify') {
        $email = nv_norm_email($_SESSION['otp_email'] ?? '');
        $code  = $_POST['code'] ?? '';
        if ($email === '') {
            $step = 'email';
        } else {
            $reason = null;
            if (nv_verify_otp($con, $email, $code, $reason)) {
                $role = nv_role_for_email($con, $email);
                nv_ensure_account($con, $email, $role);
                nv_establish_session($con, $email, $role);
                if (!empty($_POST['remember'])) {
                    nv_remember_device($con, $email);
                }
                unset($_SESSION['otp_email']);
                header('Location: ' . nv_home_for($role));
                exit;
            }
            $step  = 'code';
            $error = $reason === 'too_many' ? t('auth.too_many') : t('auth.bad_code');
        }
    }
}

// Default to the code step if an OTP is pending and we weren't told to change email.
if ($step === 'email' && !empty($_SESSION['otp_email']) && $action !== 'change_email') {
    $step = 'code';
}
$pendingEmail = $_SESSION['otp_email'] ?? '';
$lang = nv_lang();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<body>
<div class="auth-hero">
  <form class="auth-card" method="post" action="/auth/login.php">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
      <div class="divider"></div>
      <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
      <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub"><?= htmlspecialchars(t('brand.sub')) ?></span></div>
    </div>
    <div class="auth-head">
      <span class="eyebrow"><?= htmlspecialchars(t('brand.sub')) ?></span>
      <h1><?= htmlspecialchars(t('auth.signin')) ?></h1>
      <p><?= htmlspecialchars(t('auth.subhead')) ?></p>
    </div>

    <?php if ($error): ?>
      <div class="flash bad"><i data-lucide="alert-circle"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>
    <?php if ($info): ?>
      <div class="flash ok"><i data-lucide="mail-check"></i><span><?= htmlspecialchars($info) ?></span></div>
    <?php endif; ?>

    <?php if ($step === 'code'): ?>
      <!-- STEP 2: enter code -->
      <input type="hidden" name="action" value="verify">
      <div class="field">
        <label class="field-label"><?= htmlspecialchars(t('auth.email_label')) ?></label>
        <input class="input" type="email" value="<?= htmlspecialchars($pendingEmail) ?>" readonly>
      </div>
      <div class="field">
        <label class="field-label" for="code"><?= htmlspecialchars(t('auth.code_label')) ?></label>
        <input class="input mono" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
               autocomplete="one-time-code" required placeholder="<?= htmlspecialchars(t('auth.code_ph')) ?>" autofocus
               style="letter-spacing:8px;font-size:20px;text-align:center;">
      </div>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:4px 0 10px;cursor:pointer;">
        <input type="checkbox" name="remember" value="1"> <?= htmlspecialchars(t('auth.remember')) ?>
      </label>
      <button class="btn btn-primary btn-full-width" type="submit">
        <?= htmlspecialchars(t('auth.verify')) ?> <i data-lucide="arrow-right"></i>
      </button>
      <div class="auth-foot">
        <button class="linklike" type="submit" name="action" value="resend" formnovalidate><?= htmlspecialchars(t('auth.resend')) ?></button>
        <button class="linklike" type="submit" name="action" value="change_email" formnovalidate><?= htmlspecialchars(t('auth.change_email')) ?></button>
      </div>
    <?php else: ?>
      <!-- STEP 1: enter UiTM email -->
      <input type="hidden" name="action" value="send_code">
      <div class="field">
        <label class="field-label" for="email"><?= htmlspecialchars(t('auth.email_label')) ?></label>
        <input class="input" id="email" name="email" type="email" required
               placeholder="<?= htmlspecialchars(t('auth.email_ph')) ?>" autofocus
               value="<?= htmlspecialchars($pendingEmail) ?>">
      </div>
      <button class="btn btn-primary btn-full-width" type="submit">
        <?= htmlspecialchars(t('auth.send_code')) ?> <i data-lucide="mail"></i>
      </button>
      <div class="auth-foot">
        <span style="font-size:12px;color:var(--fg-3);"><?= htmlspecialchars(t('auth.no_password')) ?></span>
      </div>
    <?php endif; ?>

    <div class="auth-foot">
      <span class="text-mono lang-selector">
        <a href="?lang=bm" class="<?= $lang === 'bm' ? 'lang-active' : 'lang-inactive' ?>">BM</a>
        ·
        <a href="?lang=en" class="<?= $lang === 'en' ? 'lang-active' : 'lang-inactive' ?>">EN</a>
      </span>
    </div>
  </form>
</div>
<style>
  .linklike{background:none;border:0;color:var(--accent,#6b21a8);cursor:pointer;font:inherit;padding:0;text-decoration:underline;}
</style>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
