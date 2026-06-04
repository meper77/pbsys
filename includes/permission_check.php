<?php
/**
 * Permission Check Middleware
 * Provides role-based access control for pbsys
 */

/**
 * Check if current session is admin
 */
function isAdmin() {
    return isset($_SESSION['email_Admin']) && !empty($_SESSION['email_Admin']);
}

/**
 * Check if current session is regular user (not admin)
 */
function isUser() {
    return isset($_SESSION['email']) && !empty($_SESSION['email']) && !isAdmin();
}

/**
 * Check if user is logged in at all
 */
function isLoggedIn() {
    return isset($_SESSION['email']) || isset($_SESSION['email_Admin']);
}

/**
 * Get current user role
 * @return string 'admin', 'user', or null
 */
function getCurrentUserRole() {
    if (isAdmin()) {
        return 'admin';
    } elseif (isUser()) {
        return 'user';
    }
    return null;
}

/**
 * Get current user email
 * @return string|null
 */
function getUserEmail() {
    if (isAdmin()) {
        return $_SESSION['email_Admin'] ?? null;
    } elseif (isUser()) {
        return $_SESSION['email'] ?? null;
    }
    return null;
}

/**
 * Require admin access - dies with 403 if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        logUnauthorizedAccess('requireAdmin check failed');
        header('Location: /auth/login.php?error=unauthorized');
        exit('Access denied. Admin role required.');
    }
}

/**
 * Require user login - dies with 403 if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(403);
        header('Location: /auth/login.php?error=login_required');
        exit('Login required.');
    }
}

/**
 * Check if user owns a vehicle (via M:M table)
 * @param int $user_id
 * @param int $vehicle_id
 * @param string $vehicle_type
 * @return bool
 */
function userOwnsVehicle($user_id, $vehicle_id, $vehicle_type) {
    global $con;
    if (!$con) return false;

    $stmt = $con->prepare("
        SELECT id FROM user_vehicle
        WHERE user_id = ? AND vehicle_id = ? AND vehicle_type = ?
    ");
    if (!$stmt) return false;
    $stmt->bind_param("iis", $user_id, $vehicle_id, $vehicle_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $owns = $result->num_rows > 0;
    $stmt->close();
    
    return $owns;
}

/**
 * Log unauthorized access attempts
 * @param string $reason
 */
function logUnauthorizedAccess($reason = '') {
    global $con;
    if (!$con) return;

    $user_email = getUserEmail() ?? 'anonymous';
    $page = $_SERVER['REQUEST_URI'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // admin_action_logs may not exist on every deployment; fail quietly.
    $stmt = $con->prepare("
        INSERT INTO admin_action_logs
        (admin_email, action, details, page, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) return;

    $action = 'unauthorized_access';
    $details = $reason;

    $stmt->bind_param("sssss", $user_email, $action, $details, $page, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Guard admin page - use at top of admin/* pages
 */
function guardAdminPage() {
    requireAdmin();
}

/**
 * Guard user page - use at top of user-only pages
 */
function guardUserPage() {
    requireLogin();
}

/**
 * Log admin action to audit trail
 * @param string $action Action type (delete, create, update, download, etc.)
 * @param string $details Details about the action
 * @param string $entity_type Type of entity (user, vehicle, report, etc.)
 * @param int|string $entity_id ID of the entity affected
 */
function logAdminAction($action, $details = '', $entity_type = '', $entity_id = '') {
    global $con;
    if (!$con) return;

    if (!isAdmin()) return; // Only log admin actions

    $user_email = getUserEmail() ?? 'system';
    $page = $_SERVER['REQUEST_URI'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $con->prepare("
        INSERT INTO admin_action_logs
        (admin_email, action, details, page, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) return;

    $action_detail = $action;
    if (!empty($entity_type)) {
        $action_detail .= " [{$entity_type}:" . (string)$entity_id . "]";
    }
    if (!empty($details)) {
        $action_detail .= ": {$details}";
    }
    
    $stmt->bind_param("sssss", $user_email, $action, $action_detail, $page, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Log sensitive user action
 * @param string $action Action type (login, export, search, etc.)
 * @param string $details Additional details
 */
function logUserAction($action, $details = '') {
    global $con;
    if (!$con) return;

    if (!isUser()) return; // Only log user actions

    $user_email = getUserEmail() ?? 'unknown';
    $page = $_SERVER['REQUEST_URI'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $con->prepare("
        INSERT INTO admin_action_logs
        (admin_email, action, details, page, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) return;

    $stmt->bind_param("sssss", $user_email, $action, $details, $page, $ip);
    $stmt->execute();
    $stmt->close();
}
