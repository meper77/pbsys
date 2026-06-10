<?php
/**
 * Schema runner + SMTP self-test, guarded by the migrate_key secret.
 *
 *   /api/migrate.php?key=SECRET                      -> apply pending DDL + seed
 *   /api/migrate.php?key=SECRET&selftest=mail&to=X   -> probe SMTP port/encryption combos
 *
 * The DDL itself lives in includes/schema_guard.php (also auto-run on an admin's
 * first sign-in). This endpoint stays useful for explicit/repeatable migrations.
 */
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/secrets_loader.php';

$key = $_GET['key'] ?? '';
$expected = (string) nv_secret('migrate_key', '');
// Refuse the public placeholder key (i.e. when the real secrets.php is absent).
if ($expected === '' || $expected === 'CHANGE_ME_migrate_key' || !hash_equals($expected, (string) $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

/* ---------- SMTP self-test ---------- */
if (($_GET['selftest'] ?? '') === 'mail') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/mailer.php';
    $to = trim($_GET['to'] ?? '');
    if ($to === '') {
        echo json_encode(['ok' => false, 'error' => 'pass &to=email']);
        exit;
    }
    $combos = [
        ['port' => 25,  'secure' => ''],
        ['port' => 25,  'secure' => 'tls'],
        ['port' => 587, 'secure' => 'tls'],
        ['port' => 465, 'secure' => 'ssl'],
        ['port' => 25,  'secure' => 'ssl'],
    ];
    $out = [];
    foreach ($combos as $c) {
        $err = null;
        $label = $c['port'] . '/' . ($c['secure'] ?: 'none');
        $ok = nv_send_mail($to, 'NEO V-TRACK SMTP test (' . $label . ')',
            '<p>NEO V-TRACK SMTP self-test succeeded via <strong>' . $label . '</strong>.</p>', $err, $c);
        $out[] = ['transport' => $label, 'ok' => $ok, 'error' => $err];
        if ($ok) break;
    }
    echo json_encode(['ok' => true, 'mail' => $out], JSON_PRETTY_PRINT);
    exit;
}

/* ---------- schema ---------- */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/schema_guard.php';
$results = nv_ensure_schema($con);
echo json_encode(['ok' => true, 'results' => $results], JSON_PRETTY_PRINT);
