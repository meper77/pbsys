<?php
/**
 * Passwordless (email OTP) auth core for NEO V-TRACK.
 *
 * Rules (foundation/login.md):
 *   - Only UiTM emails may sign in/up: @uitm.edu.my or @student.uitm.edu.my.
 *   - Allow-listed emails (admin_allowlist) become admins; everyone else is a user.
 *     The developer 2023818464@student.uitm.edu.my is a locked allowlist entry.
 *   - No passwords are stored. A 6-digit code is emailed for each sign-in/up.
 *   - "Remember this device" stores a hashed device token so OTP can be skipped.
 *
 * Every function tolerates the new tables/columns being absent (pre-migration) so
 * deploying this before api/migrate.php runs never fatals a page.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/secrets_loader.php';

if (!defined('NV_OTP_TTL_MIN'))      define('NV_OTP_TTL_MIN', 10);   // code lifetime
if (!defined('NV_OTP_MAX_ATTEMPTS')) define('NV_OTP_MAX_ATTEMPTS', 5);
if (!defined('NV_OTP_RESEND_SEC'))   define('NV_OTP_RESEND_SEC', 30); // min gap between sends
if (!defined('NV_DEVICE_DAYS'))      define('NV_DEVICE_DAYS', 30);
if (!defined('NV_DEVICE_COOKIE'))    define('NV_DEVICE_COOKIE', 'nv_device');

/* ----------------------------------------------------------------- helpers */

function nv_norm_email(string $email): string
{
    return strtolower(trim($email));
}

/** UiTM staff or student address only. */
function nv_valid_uitm_email(string $email): bool
{
    $email = nv_norm_email($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return (bool) preg_match('/@(student\.)?uitm\.edu\.my$/', $email);
}

function nv_is_student_email(string $email): bool
{
    return (bool) preg_match('/@student\.uitm\.edu\.my$/', nv_norm_email($email));
}

/** True if a table exists in the current schema (cached). */
function nv_table_exists($con, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    $t = $con->real_escape_string($table);
    $r = @$con->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1");
    return $cache[$table] = ($r && $r->num_rows > 0);
}

function nv_email_is_allowlisted($con, string $email): bool
{
    $email = nv_norm_email($email);
    if (!nv_table_exists($con, 'admin_allowlist')) {
        return false;
    }
    $stmt = $con->prepare("SELECT 1 FROM admin_allowlist WHERE LOWER(email) = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function nv_role_for_email($con, string $email): string
{
    return nv_email_is_allowlisted($con, $email) ? 'admin' : 'user';
}

/* ----------------------------------------------------------------- OTP */

function nv_otp_hash(string $email, string $code): string
{
    $pepper = (string) nv_secret('otp_pepper', 'nv-fallback-pepper');
    return hash_hmac('sha256', nv_norm_email($email) . '|' . $code, $pepper);
}

/**
 * Generate + email a fresh OTP. Returns true on success.
 * $error is one of: 'rate' (too soon), 'send' (mail failed), 'db'.
 */
function nv_create_and_send_otp($con, string $email, ?string &$error = null): bool
{
    $email = nv_norm_email($email);
    if (!nv_table_exists($con, 'login_otp')) {
        $error = 'db';
        return false;
    }

    // Rate limit: block if a code was issued very recently.
    $stmt = $con->prepare("SELECT created_at FROM login_otp WHERE email = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            if (strtotime($row['created_at']) > time() - NV_OTP_RESEND_SEC) {
                $stmt->close();
                $error = 'rate';
                return false;
            }
        }
        $stmt->close();
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = nv_otp_hash($email, $code);
    $ip   = $_SERVER['REMOTE_ADDR'] ?? null;

    // Invalidate prior unconsumed codes, then store the new one.
    if ($d = $con->prepare("DELETE FROM login_otp WHERE email = ? AND consumed_at IS NULL")) {
        $d->bind_param('s', $email);
        $d->execute();
        $d->close();
    }
    $ins = $con->prepare("INSERT INTO login_otp (email, code_hash, purpose, expires_at, ip_address)
                          VALUES (?, ?, 'login', DATE_ADD(NOW(), INTERVAL " . (int) NV_OTP_TTL_MIN . " MINUTE), ?)");
    if (!$ins) {
        $error = 'db';
        return false;
    }
    $ins->bind_param('sss', $email, $hash, $ip);
    if (!$ins->execute()) {
        $ins->close();
        $error = 'db';
        return false;
    }
    $ins->close();

    // Email the code.
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/mailer.php';
    $subject = 'NEO V-TRACK sign-in code: ' . $code;
    $body = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#222;">'
        . '<h2 style="margin:0 0 8px;">NEO V-TRACK</h2>'
        . '<p>Your one-time sign-in code is:</p>'
        . '<p style="font-size:30px;font-weight:700;letter-spacing:6px;margin:12px 0;">' . $code . '</p>'
        . '<p>This code expires in ' . (int) NV_OTP_TTL_MIN . ' minutes. If you did not request it, you can ignore this email.</p>'
        . '<hr style="border:none;border-top:1px solid #eee;margin:16px 0;">'
        . '<p style="font-size:12px;color:#888;">Polis Bantuan · UiTM Cawangan Johor (Segamat)</p></div>';

    $mailErr = null;
    if (!nv_send_mail($email, $subject, $body, $mailErr)) {
        $error = 'send';
        return false;
    }
    return true;
}

/**
 * Verify a submitted code. Returns true on success.
 * $reason is one of: 'expired', 'too_many', 'bad'.
 */
function nv_verify_otp($con, string $email, string $code, ?string &$reason = null): bool
{
    $email = nv_norm_email($email);
    $code  = preg_replace('/\D/', '', $code);
    if (!nv_table_exists($con, 'login_otp')) {
        $reason = 'expired';
        return false;
    }

    $stmt = $con->prepare("SELECT id, code_hash, attempts FROM login_otp
                           WHERE email = ? AND consumed_at IS NULL AND expires_at > NOW()
                           ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        $reason = 'expired';
        return false;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $reason = 'expired';
        return false;
    }
    if ((int) $row['attempts'] >= NV_OTP_MAX_ATTEMPTS) {
        $reason = 'too_many';
        return false;
    }

    $id = (int) $row['id'];
    $con->query("UPDATE login_otp SET attempts = attempts + 1 WHERE id = $id");

    if (!hash_equals($row['code_hash'], nv_otp_hash($email, $code))) {
        $reason = 'bad';
        return false;
    }

    $con->query("UPDATE login_otp SET consumed_at = NOW() WHERE id = $id");
    return true;
}

/* ----------------------------------------------------------------- account + session */

/** Make sure a row exists for this email in the right table; returns the row id. */
function nv_ensure_account($con, string $email, string $role): int
{
    $email = nv_norm_email($email);
    $table = $role === 'admin' ? 'admin' : 'user';
    $defaultName = ucwords(str_replace(['.', '_'], ' ', strstr($email, '@', true) ?: $email));

    $stmt = $con->prepare("SELECT userid FROM `$table` WHERE LOWER(email) = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int) $row['userid'];
    }

    // Create. password column may be NOT NULL on un-migrated schemas -> supply ''.
    $ins = $con->prepare("INSERT INTO `$table` (email, name, password) VALUES (?, ?, '')");
    if ($ins) {
        $ins->bind_param('ss', $email, $defaultName);
        $ins->execute();
        $id = $ins->insert_id;
        $ins->close();
        return (int) $id;
    }
    return 0;
}

function nv_establish_session($con, string $email, string $role): void
{
    $email = nv_norm_email($email);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);

    // Clear any opposite-role key so role can't bleed across logins.
    unset($_SESSION['email'], $_SESSION['email_Admin']);

    if ($role === 'admin') {
        $_SESSION['email_Admin'] = $email;
        $_SESSION['user_type']   = 'admin';
        @$con->query("UPDATE `admin` SET last_login = NOW() WHERE email = '" . $con->real_escape_string($email) . "'");
    } else {
        $_SESSION['email']     = $email;
        $_SESSION['user_type'] = 'user';
        @$con->query("UPDATE `user` SET last_login = NOW() WHERE email = '" . $con->real_escape_string($email) . "'");
    }
}

/* ----------------------------------------------------------------- trusted device */

function nv_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

function nv_device_token_hash(string $token): string
{
    $secret = (string) nv_secret('app_secret', 'nv-fallback-secret');
    return hash_hmac('sha256', $token, $secret);
}

/** Persist a trusted-device token for $email and set the cookie. */
function nv_remember_device($con, string $email): void
{
    if (!nv_table_exists($con, 'trusted_devices')) {
        return;
    }
    $email = nv_norm_email($email);
    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $hash  = nv_device_token_hash($token);
    $ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt = $con->prepare("INSERT INTO trusted_devices (email, token_hash, user_agent, last_used_at, expires_at)
                           VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL " . (int) NV_DEVICE_DAYS . " DAY))");
    if ($stmt) {
        $stmt->bind_param('sss', $email, $hash, $ua);
        $stmt->execute();
        $stmt->close();
    }

    setcookie(NV_DEVICE_COOKIE, $token, [
        'expires'  => time() + NV_DEVICE_DAYS * 86400,
        'path'     => '/',
        'secure'   => nv_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** If a valid trusted-device cookie is present, return its email; else null. */
function nv_check_trusted_device($con): ?string
{
    if (empty($_COOKIE[NV_DEVICE_COOKIE]) || !nv_table_exists($con, 'trusted_devices')) {
        return null;
    }
    $hash = nv_device_token_hash($_COOKIE[NV_DEVICE_COOKIE]);
    $stmt = $con->prepare("SELECT id, email FROM trusted_devices WHERE token_hash = ? AND expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    $con->query("UPDATE trusted_devices SET last_used_at = NOW() WHERE id = " . (int) $row['id']);
    return $row['email'];
}

function nv_forget_device($con): void
{
    if (!empty($_COOKIE[NV_DEVICE_COOKIE]) && nv_table_exists($con, 'trusted_devices')) {
        $hash = nv_device_token_hash($_COOKIE[NV_DEVICE_COOKIE]);
        if ($stmt = $con->prepare("DELETE FROM trusted_devices WHERE token_hash = ?")) {
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->close();
        }
    }
    setcookie(NV_DEVICE_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
}
