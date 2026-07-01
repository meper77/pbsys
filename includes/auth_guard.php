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

/* ------------------------------------------------------------------ per-user page access (permission control)
 * admin_allowlist.permissions (role='user') is a JSON list of the page slugs a
 * user may open. NULL/empty = unrestricted (all pages) so existing users keep
 * access; an explicit list (incl. []) limits them. Admins always see everything;
 * the dashboard is never gated. Managed by the checkboxes on admin/users.php.
 */

/** Page slugs an admin can grant/revoke per user (dashboard is always allowed). */
function nv_controlled_pages(): array {
    return ['search', 'staff', 'student', 'visitor', 'contractor', 'alumni', 'reports'];
}

/**
 * Allowed page slugs for a role='user' email, or null when unrestricted
 * (no permissions stored / column absent). Cached per request.
 */
function nv_user_allowed_pages($con, string $email): ?array {
    static $cache = [];
    $email = strtolower(trim($email));
    if ($email === '') { return null; }
    if (array_key_exists($email, $cache)) { return $cache[$email]; }

    $result = null;                                  // null = unrestricted
    if ($con && ($stmt = @$con->prepare(
        "SELECT permissions FROM admin_allowlist WHERE LOWER(email) = ? AND role = 'user' LIMIT 1"))) {
        $stmt->bind_param('s', $email);
        if (@$stmt->execute()) {
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && $row['permissions'] !== null && $row['permissions'] !== '') {
                $list = json_decode($row['permissions'], true);
                if (is_array($list)) {
                    $result = array_values(array_intersect($list, nv_controlled_pages()));
                }
            }
        }
        $stmt->close();
    }
    return $cache[$email] = $result;
}

/** True if the current session may open $slug (admins: always; users: per list). */
function nv_can_access_page($con, string $slug): bool {
    if (nv_is_admin()) { return true; }
    if (!nv_is_user()) { return false; }
    $allowed = nv_user_allowed_pages($con, $_SESSION['email'] ?? '');
    if ($allowed === null) { return true; }          // not restricted yet
    return in_array($slug, $allowed, true);
}

/** Hard-gate a controlled page; send a user without access back to the dashboard. */
function nv_guard_page($con, string $slug): void {
    if (nv_can_access_page($con, $slug)) { return; }
    http_response_code(403);
    header('Location: /admin/index_user.php?error=no_access');
    exit();
}

/**
 * Resolve an email to a person's display name from the admin or user table (cached per
 * request). Falls back to $fallback, then the email itself, when no name is on file.
 */
function nv_display_name($con, $email, string $fallback = ''): string {
    static $cache = [];
    $email = trim((string) $email);
    if ($email === '') { return $fallback; }
    $key = strtolower($email);
    if (!array_key_exists($key, $cache)) {
        $name = '';
        foreach (['admin', 'user'] as $tbl) {
            if ($stmt = mysqli_prepare($con, "SELECT name FROM `$tbl` WHERE email = ? LIMIT 1")) {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                if ($row && trim((string) ($row['name'] ?? '')) !== '') { $name = trim((string) $row['name']); break; }
            }
        }
        $cache[$key] = $name;
    }
    return $cache[$key] !== '' ? $cache[$key] : ($fallback !== '' ? $fallback : $email);
}

/* ------------------------------------------------------------------ JSON API guards
 * For /api/* endpoints that must not be reachable anonymously. Unlike the page
 * guards above (which redirect to HTML login), these emit a JSON error + status
 * code and stop. Include this file, then call at the very top of the endpoint.
 */

/** Require any signed-in role (admin OR user); 401 JSON + exit otherwise. */
function nv_api_require_login(): void {
    if (nv_is_logged_in()) { return; }
    http_response_code(401);
    if (!headers_sent()) { header('Content-Type: application/json'); }
    echo json_encode(['success' => 0, 'count' => 0, 'data' => [], 'message' => 'Authentication required']);
    exit;
}

/** Require an admin session; 403 (or 401 if anonymous) JSON + exit otherwise. */
function nv_api_require_admin(): void {
    if (nv_is_admin()) { return; }
    http_response_code(nv_is_logged_in() ? 403 : 401);
    if (!headers_sent()) { header('Content-Type: application/json'); }
    echo json_encode(['success' => 0, 'count' => 0, 'data' => [], 'message' => 'Admin access required']);
    exit;
}
