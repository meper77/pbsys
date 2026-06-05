<?php
/**
 * Shared language switcher. Include this EARLY (before any output) on any page
 * that shows the BM/EN switcher in nv_chrome. Handles `?lang=bm|en`, stores it
 * in the session, and redirects back to the same URL with `lang` stripped so the
 * choice persists and other query params (e.g. ?id=) are preserved.
 *
 * After include, $lang holds the active language ('bm' | 'en').
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}
if (isset($_GET['lang'])) {
    $_SESSION['language'] = ($_GET['lang'] === 'en') ? 'en' : 'bm';
    $parts = parse_url($_SERVER['REQUEST_URI'] ?? '');
    parse_str($parts['query'] ?? '', $q);
    unset($q['lang']);
    $target = ($parts['path'] ?? $_SERVER['PHP_SELF']) . (empty($q) ? '' : '?' . http_build_query($q));
    header('Location: ' . $target);
    exit;
}
$lang = $_SESSION['language'];
