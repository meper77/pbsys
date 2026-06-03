<?php
/**
 * Lightweight role guards used by view pages.
 *
 * Sessions:
 *   $_SESSION['email_Admin'] => admin
 *   $_SESSION['email']       => regular user
 *
 * View permission (per upgrade spec):
 *   - admin: every page.
 *   - user : may VIEW dashboard, search and vehicle lists/details,
 *            but NOT users / admins / reports / import, and may not add/edit/delete.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function nv_is_admin() {
    return isset($_SESSION['email_Admin']) && !empty($_SESSION['email_Admin']);
}

function nv_is_user() {
    return isset($_SESSION['email']) && !empty($_SESSION['email']);
}

function nv_is_logged_in() {
    return nv_is_admin() || nv_is_user();
}

/** Allow admins OR regular users (view pages). */
function nv_require_login() {
    if (!nv_is_logged_in()) {
        header('Location: /auth/role_selection.php');
        exit();
    }
}

/** Admin-only pages and mutating actions. */
function nv_require_admin() {
    if (!nv_is_admin()) {
        if (nv_is_user()) {
            http_response_code(403);
            header('Location: /index.php?error=forbidden');
        } else {
            header('Location: /auth/login_admin.php');
        }
        exit();
    }
}
