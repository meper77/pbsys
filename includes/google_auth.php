<?php
/**
 * Google Sign-In (UiTM) for NEO V-TRACK.
 *
 * Verifies a Google Identity Services ID token (JWT) server-side against Google's
 * published JWKS using openssl (no external JWT library needed), then enforces:
 *   - signature (RS256), issuer, audience (= our Client ID), expiry, email_verified
 *   - UiTM domain (@uitm.edu.my / @student.uitm.edu.my) — reuses nv_valid_uitm_email
 *   - optional hosted-domain (hd) claim if `google_hd` secret is set
 *
 * Role + session reuse the shared helpers in includes/otp_auth.php.
 *
 * Config (includes/secrets.php):
 *   'google_client_id' => '....apps.googleusercontent.com'
 *   'google_hd'        => 'uitm.edu.my'   // optional, extra restriction
 *
 * NOTE: Google requires the page origin to be HTTPS. On the current HTTP host the
 * button is hidden; the developer bypass (nv_dev_bypass_token) is the interim way in
 * until the OAuth Client ID + a trusted HTTPS cert are configured on the live host.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/otp_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/secrets_loader.php';

function nv_google_client_id(): string
{
    return (string) nv_secret('google_client_id', '');
}

/** Base64URL decode. */
function nv_b64url_decode(string $s): string
{
    $pad = strlen($s) % 4;
    if ($pad) { $s .= str_repeat('=', 4 - $pad); }
    return (string) base64_decode(strtr($s, '-_', '+/'));
}

/* ---- minimal DER helpers to turn a JWK {n,e} into a PEM public key ---- */

function nv_der_len(int $n): string
{
    if ($n < 0x80) { return chr($n); }
    $bytes = '';
    while ($n > 0) { $bytes = chr($n & 0xff) . $bytes; $n >>= 8; }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function nv_der_uint(string $bytes): string
{
    $bytes = ltrim($bytes, "\x00");
    if ($bytes === '') { $bytes = "\x00"; }
    if (ord($bytes[0]) & 0x80) { $bytes = "\x00" . $bytes; } // keep it positive
    return "\x02" . nv_der_len(strlen($bytes)) . $bytes;
}

function nv_jwk_to_pem(string $n_b64, string $e_b64): string
{
    $n = nv_b64url_decode($n_b64);
    $e = nv_b64url_decode($e_b64);
    $rsa    = nv_der_uint($n) . nv_der_uint($e);
    $rsaSeq = "\x30" . nv_der_len(strlen($rsa)) . $rsa;          // SEQUENCE { n, e }
    $bitStr = "\x03" . nv_der_len(strlen($rsaSeq) + 1) . "\x00" . $rsaSeq;
    // AlgorithmIdentifier { OID 1.2.840.113549.1.1.1 (rsaEncryption), NULL }
    $algo   = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
    $spki   = $algo . $bitStr;
    $spkiSeq = "\x30" . nv_der_len(strlen($spki)) . $spki;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spkiSeq), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

/** Fetch Google's JWKS (cached ~6h on disk; falls back to stale cache on network failure). */
function nv_google_jwks(): ?array
{
    $cacheFile = sys_get_temp_dir() . '/nv_google_jwks.json';
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < 6 * 3600)) {
        $j = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($j) && !empty($j['keys'])) { return $j; }
    }
    $data = nv_http_get('https://www.googleapis.com/oauth2/v3/certs');
    if ($data) {
        $j = json_decode($data, true);
        if (is_array($j) && !empty($j['keys'])) { @file_put_contents($cacheFile, $data); return $j; }
    }
    if (is_file($cacheFile)) { // stale fallback
        $j = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($j) && !empty($j['keys'])) { return $j; }
    }
    return null;
}

function nv_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'NEO-VTRACK',
        ]);
        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($out !== false && $code >= 200 && $code < 300) ? $out : null;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 8], 'https' => ['timeout' => 8]]);
    $out = @file_get_contents($url, false, $ctx);
    return $out !== false ? $out : null;
}

/**
 * Verify a Google ID token. On success fills $claims and returns true.
 * $error: not_configured | malformed | alg | jwks | kid | signature | iss | aud | expired.
 */
function nv_google_verify_id_token(string $idToken, ?array &$claims = null, ?string &$error = null): bool
{
    $claims = null;
    $cid = nv_google_client_id();
    if ($cid === '') { $error = 'not_configured'; return false; }

    $parts = explode('.', $idToken);
    if (count($parts) !== 3) { $error = 'malformed'; return false; }
    [$h64, $p64, $s64] = $parts;

    $header  = json_decode(nv_b64url_decode($h64), true);
    $payload = json_decode(nv_b64url_decode($p64), true);
    $sig     = nv_b64url_decode($s64);
    if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? '') !== 'RS256') {
        $error = 'alg'; return false;
    }

    $jwks = nv_google_jwks();
    if (!$jwks) { $error = 'jwks'; return false; }

    $pem = null;
    foreach ($jwks['keys'] as $k) {
        if (($k['kid'] ?? '') === ($header['kid'] ?? '') && isset($k['n'], $k['e'])) {
            $pem = nv_jwk_to_pem($k['n'], $k['e']);
            break;
        }
    }
    if (!$pem) { $error = 'kid'; return false; }

    if (openssl_verify($h64 . '.' . $p64, $sig, $pem, OPENSSL_ALGO_SHA256) !== 1) {
        $error = 'signature'; return false;
    }
    if (!in_array($payload['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) {
        $error = 'iss'; return false;
    }
    if (($payload['aud'] ?? '') !== $cid)        { $error = 'aud'; return false; }
    if ((int) ($payload['exp'] ?? 0) < time())   { $error = 'expired'; return false; }

    $claims = $payload;
    return true;
}

/**
 * Verify + sign in via Google. Returns the email on success (session established), else false.
 * $error: any verify error, plus unverified | domain | bad_domain.
 */
function nv_google_login($con, string $idToken, ?string &$error = null)
{
    if (!nv_google_verify_id_token($idToken, $claims, $error)) { return false; }

    $email = nv_norm_email($claims['email'] ?? '');
    if (empty($claims['email_verified']) || $email === '') { $error = 'unverified'; return false; }

    // Hosted-domain guard: accept the UiTM Workspace family — uitm.edu.my (staff)
    // and student.uitm.edu.my (students) — plus any configured google_hd / sub-domain.
    $claimHd = strtolower((string) ($claims['hd'] ?? ''));
    $hd      = strtolower((string) nv_secret('google_hd', ''));
    $hdOk = ($claimHd === 'uitm.edu.my' || str_ends_with($claimHd, '.uitm.edu.my'));
    if (!$hdOk && $hd !== '') { $hdOk = ($claimHd === $hd || str_ends_with($claimHd, '.' . $hd)); }
    if (!$hdOk) { $error = 'domain'; return false; }

    if (!nv_valid_uitm_email($email)) { $error = 'bad_domain'; return false; }

    // Allowlist gates access: only listed staff may sign in (as admin or user).
    $role = nv_allowlist_role($con, $email);
    if ($role === null) { $error = 'not_allowed'; return false; }

    nv_ensure_account($con, $email, $role);
    nv_establish_session($con, $email, $role);
    return $email;
}
