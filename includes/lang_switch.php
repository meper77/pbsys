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
// Single language: professional Malay only (English option removed).
$_SESSION['language'] = 'bm';
$lang = 'bm';
