<?php
/**
 * NEO V-TRACK sign in — Google-only (foundation/login).
 *
 *   Primary : Google Sign-In (UiTM). Requires HTTPS + a configured google_client_id
 *             (includes/secrets.php); the button renders only when both are present.
 *   Interim : developer bypass for NV_DEV_EMAIL, gated by an HMAC token derived from
 *             app_secret — the way in until the OAuth Client ID + trusted HTTPS land.
 *             Reveal with /auth/login.php?dev=1.
 *
 * Access is allowlist-gated: only emails on admin_allowlist may sign in, as their
 * role (admin or user). No SMTP / OTP / password is used by the web login.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/i18n.php';      // session + ?lang
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/otp_auth.php';   // shared session/role/device helpers
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/google_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/schema_guard.php';

function nv_home_for(string $role): string
{
    return $role === 'admin' ? '/index.php' : '/admin/index_user.php';
}

// Already signed in? Go home.
if (nv_is_logged_in()) {
    header('Location: ' . nv_home_for(nv_is_admin() ? 'admin' : 'user'));
    exit;
}

// Trusted-device auto-login (plain GET only, and only if still allowlisted).
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_COOKIE[NV_DEVICE_COOKIE])) {
    $dev = nv_check_trusted_device($con);
    if ($dev && nv_valid_uitm_email($dev) && ($role = nv_allowlist_role($con, $dev)) !== null) {
        nv_ensure_account($con, $dev, $role);
        nv_establish_session($con, $dev, $role);
        header('Location: ' . nv_home_for($role));
        exit;
    }
}

$lang     = nv_lang();
$devMode  = isset($_GET['dev']);
$error    = '';

$L = $lang === 'bm' ? [
    'title'         => 'Log masuk ke NEO V-TRACK',
    'sub'           => 'Log masuk dengan akaun Google UiTM anda.',
    'google'        => 'Log masuk dengan Google UiTM',
    'google_soon'   => 'Log masuk Google sedang disediakan (memerlukan HTTPS). Sila cuba sebentar lagi.',
    'dev_title'     => 'Akses pembangun',
    'dev_sub'       => 'Masukkan token bypass pembangun untuk masuk.',
    'dev_token'     => 'Token pembangun',
    'dev_btn'       => 'Masuk sebagai pembangun',
    'e_dev'         => 'Token pembangun tidak sah.',
    'e_google'      => 'Log masuk Google gagal',
    'e_not_allowed' => 'Emel ini tiada dalam senarai dibenarkan. Sila hubungi pentadbir.',
] : [
    'title'         => 'Sign in to NEO V-TRACK',
    'sub'           => 'Sign in with your UiTM Google account.',
    'google'        => 'Sign in with UiTM Google',
    'google_soon'   => 'Google sign-in is being set up (it needs HTTPS). Please check back shortly.',
    'dev_title'     => 'Developer access',
    'dev_sub'       => 'Enter the developer bypass token to sign in.',
    'dev_token'     => 'Developer token',
    'dev_btn'       => 'Sign in as developer',
    'e_dev'         => 'Invalid developer token.',
    'e_google'      => 'Google sign-in failed',
    'e_not_allowed' => 'This email is not on the allowlist. Please contact an administrator.',
];

// Surface a Google-callback error (set in auth/google_callback.php).
if (!empty($_SESSION['login_error'])) {
    $code = (string) $_SESSION['login_error'];
    unset($_SESSION['login_error']);
    $error = $code === 'google:not_allowed'
        ? $L['e_not_allowed']
        : $L['e_google'] . ' (' . htmlspecialchars(substr($code, 7)) . ').';
}

// Developer bypass (interim access until Google/HTTPS is live).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'dev_bypass') {
    $devMode = true;
    $token   = (string) ($_POST['token'] ?? '');
    if ($token !== '' && hash_equals(nv_dev_bypass_token(), $token)) {
        nv_schema_autoprovision_once($con);            // ensure allowlist exists for management
        nv_ensure_account($con, NV_DEV_EMAIL, 'admin');
        nv_establish_session($con, NV_DEV_EMAIL, 'admin');
        header('Location: ' . nv_home_for('admin'));
        exit;
    }
    $error = $L['e_dev'];
}

$clientId = nv_google_client_id();
$googleOn = $clientId !== '' && nv_is_https();
$origin   = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'neovtrack.uitm.edu.my');
// GIS popup mode (default) works in real browsers and only needs the JS origin
// authorised. The native WebView app tags its UA with NEOVTRACKAPP and gets
// redirect mode instead (cross-origin postMessage is blocked in WebViews) —
// that path additionally needs the redirect URI authorised on the OAuth client.
$gsiUx    = (strpos((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 'NEOVTRACKAPP') !== false) ? 'redirect' : 'popup';

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<?php if ($googleOn): ?><script src="https://accounts.google.com/gsi/client" async></script><?php endif; ?>
<body>
<style>
  /* Dark page backdrop: a pull-to-refresh / overscroll on the login page should
     reveal the hero's dark tone, not the light app surface (--bg) which showed as
     a blank "hidden" strip — on the native app and on mobile browsers. The root
     element's background paints the overscroll/viewport area. */
  html, body { background: #0a0816; }
  /* Blender-rendered animated background filling the negative space behind the card */
  .auth-hero { position: relative; overflow: hidden;
    background-image: url('/assets/video/login-bg-poster.jpg'), linear-gradient(180deg,#0a0816 0%,#15111f 100%);
    background-size: cover; background-position: center; background-repeat: no-repeat; }
  .auth-bg-video { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; pointer-events:none; }
  /* Cinematic dark scrim: gently darkens the centre so the card reads, while the
     edge cars + rain stay bright. (Replaces the old light-theme white wash that
     fogged out the middle of the dark video.) */
  .auth-hero::after { content:""; position:absolute; inset:0; z-index:1; pointer-events:none;
    background: radial-gradient(ellipse at center, rgba(6,5,16,0.50) 0%, rgba(6,5,16,0.20) 42%, rgba(6,5,16,0) 72%); }
  .auth-card { position: relative; z-index: 2; }
  /* NOTE: we intentionally do NOT hide the video on prefers-reduced-motion.
     It's a subtle, dimmed, looping ambient background the product wants shown on
     every device; some machines have "reduce motion" enabled (often unintentionally,
     e.g. Windows performance settings) which previously blanked it to the poster.
     The poster (set as .auth-hero background-image) remains the load/failure fallback. */
</style>
<div class="auth-hero">
  <video class="auth-bg-video" autoplay loop muted playsinline aria-hidden="true" poster="/assets/video/login-bg-poster.jpg">
    <source src="/assets/video/login-bg.webm" type="video/webm">
    <source src="/assets/video/login-bg.mp4" type="video/mp4">
  </video>
  <div class="auth-card">
    <div class="auth-brand">
      <img class="uitm" src="/assets/images/uitm.png" alt="UiTM">
      <div class="divider"></div>
      <img class="neo" src="/assets/images/neo-vtrack-logo.png" alt="NEO V-TRACK">
      <div class="word"><span class="name">NEO <span class="y">V-TRACK</span></span><span class="sub"><?= htmlspecialchars(t('brand.sub')) ?></span></div>
    </div>
    <div class="auth-head">
      <span class="eyebrow"><?= htmlspecialchars(t('brand.sub')) ?></span>
      <h1><?= htmlspecialchars($L['title']) ?></h1>
      <p><?= htmlspecialchars($L['sub']) ?></p>
    </div>

    <?php if ($error): ?>
      <div class="flash bad"><i data-lucide="alert-circle"></i><span><?= $error /* pre-escaped */ ?></span></div>
    <?php endif; ?>

    <?php if ($googleOn): ?>
      <div id="g_id_onload"
           data-client_id="<?= htmlspecialchars($clientId) ?>"
           data-login_uri="<?= htmlspecialchars($origin) ?>/auth/google_callback.php"
           data-ux_mode="<?= $gsiUx ?>"
           data-auto_prompt="false"></div>
      <div class="g_id_signin" data-type="standard" data-theme="outline" data-text="signin_with"
           data-shape="pill" data-size="large" data-logo_alignment="left"
           style="display:flex;justify-content:center;margin:6px 0;"></div>
    <?php else: ?>
      <div class="flash info" style="display:flex;align-items:center;gap:8px;">
        <i data-lucide="info"></i><span><?= htmlspecialchars($L['google_soon']) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($devMode): ?>
      <form method="post" action="/auth/login.php?dev=1" style="margin-top:14px;border-top:1px solid var(--border,#e6e6ee);padding-top:14px;">
        <input type="hidden" name="action" value="dev_bypass">
        <div class="auth-head" style="margin-bottom:8px;">
          <span class="eyebrow"><?= htmlspecialchars($L['dev_title']) ?></span>
          <p style="margin:2px 0 0;"><?= htmlspecialchars($L['dev_sub']) ?></p>
        </div>
        <div class="field">
          <label class="field-label" for="token"><?= htmlspecialchars($L['dev_token']) ?></label>
          <input class="input" id="token" name="token" type="password" required autofocus autocomplete="off">
        </div>
        <button class="btn btn-primary btn-full-width" type="submit">
          <?= htmlspecialchars($L['dev_btn']) ?> <i data-lucide="arrow-right"></i>
        </button>
      </form>
    <?php endif; ?>

  </div>
</div>
<script>
(function(){
  var v = document.querySelector('.auth-bg-video'); if (!v) return;
  v.muted = true; v.playsInline = true;
  function go(){ var p = v.play(); if (p && p.catch) p.catch(function(){}); }
  go();
  if (v.readyState >= 2) go(); else v.addEventListener('loadeddata', go, { once:true });
  document.addEventListener('visibilitychange', function(){ if (!document.hidden) go(); });
  ['pointerdown','keydown','scroll','touchstart'].forEach(function(e){ window.addEventListener(e, go, { once:true, passive:true }); });
})();
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
