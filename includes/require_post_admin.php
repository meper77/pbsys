<?php
/**
 * Guard for destructive actions: requires an admin session AND POST.
 * Including this file rejects GET (so crawlers / prefetch / CSRF links cannot
 * trigger deletes) and gives the page a connected $con.
 *
 * Usage at the very top of a delete endpoint:
 *   require $_SERVER['DOCUMENT_ROOT'].'/includes/require_post_admin.php';
 *   $ids = nv_post_ids();
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (empty($_SESSION['email_Admin'])) {
    http_response_code(403);
    header('Location: /auth/login_admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed — this action requires POST.');
}

/** Sanitized, de-duplicated positive-int ids from POST `id` or `ids[]`. */
function nv_post_ids(): array {
    $ids = [];
    if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
        foreach ($_POST['ids'] as $v) { $n = (int)$v; if ($n > 0) { $ids[] = $n; } }
    } elseif (isset($_POST['id'])) {
        $n = (int)$_POST['id']; if ($n > 0) { $ids[] = $n; }
    }
    return array_values(array_unique($ids));
}
