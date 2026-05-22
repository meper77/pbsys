<?php
/**
 * Legacy menu include. The current chrome lives in `nv_chrome.php`; this
 * file is kept as a shim so any older page that still pulls in `menus.php`
 * gets the new header automatically.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($nv_active)) {
    $nv_active = '';
}
include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php';
