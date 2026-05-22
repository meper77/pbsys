<?php
// Admin & user CRUD-light endpoint for the Flutter app.
// All ops require an authenticated admin session ($_SESSION['email_Admin']).
//
// GET  ?action=list_admins                  → { success, admins: [...] }
// GET  ?action=list_users                   → { success, users: [...] }
// POST action=add_admin  + email,name,password
// POST action=add_user   + email,name,password
// POST action=delete_admin + id
// POST action=delete_user  + id
// POST action=update_admin + id,name[,password]
// POST action=update_user  + id,name[,password]

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Identify the calling admin
$caller_email = $_SESSION['email_Admin'];
$callerStmt = $con->prepare("SELECT userid, name FROM `admin` WHERE email = ? LIMIT 1");
$callerStmt->bind_param('s', $caller_email);
$callerStmt->execute();
$caller = $callerStmt->get_result()->fetch_assoc();
if (!$caller) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit;
}
$caller_id = (int)$caller['userid'];
$is_superadmin = $caller_id <= 10;

function deny(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function log_action(mysqli $con, int $admin_id, string $action, string $description): void {
    @mysqli_query(
        $con,
        sprintf(
            "INSERT INTO admin_action_logs (admin_id, action, description, ip_address) VALUES (%d, '%s', '%s', '%s')",
            $admin_id,
            mysqli_real_escape_string($con, $action),
            mysqli_real_escape_string($con, $description),
            mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '')
        )
    );
}

// ---- GET ----
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'list_admins') {
        $res = mysqli_query($con, "SELECT userid, email, name, phone, profile_image, last_login, updated_at FROM `admin` ORDER BY userid ASC");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
        echo json_encode(['success' => true, 'admins' => $rows]);
        exit;
    }
    if ($action === 'list_users') {
        $res = mysqli_query($con, "SELECT userid, email, name, phone, profile_image, last_login, updated_at FROM `user` ORDER BY userid ASC");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
        echo json_encode(['success' => true, 'users' => $rows]);
        exit;
    }
    deny(400, 'Invalid action');
}

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    deny(405, 'Method not allowed');
}

$action = $_POST['action'] ?? '';
$email  = trim($_POST['email']    ?? '');
$name   = trim($_POST['name']     ?? '');
$passwd = (string)($_POST['password'] ?? '');
$id     = (int)($_POST['id'] ?? 0);

switch ($action) {
    case 'add_admin':
    case 'add_user': {
        if (!$is_superadmin) { deny(403, 'Only superadmins can add accounts'); }
        if ($email === '' || $name === '' || $passwd === '') { deny(400, 'Missing required fields'); }
        $table = ($action === 'add_admin') ? 'admin' : 'user';

        $check = $con->prepare("SELECT userid FROM `$table` WHERE email = ? LIMIT 1");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) { deny(409, 'Email already exists'); }

        $hash = password_hash($passwd, PASSWORD_DEFAULT);
        $ins  = $con->prepare("INSERT INTO `$table` (email, name, password) VALUES (?, ?, ?)");
        $ins->bind_param('sss', $email, $name, $hash);
        if (!$ins->execute()) { deny(500, 'DB error: ' . $con->error); }
        $newId = $ins->insert_id;
        log_action($con, $caller_id, $action, "Added $table #$newId ($email)");
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => ucfirst($table) . ' added', 'id' => $newId]);
        exit;
    }

    case 'update_admin':
    case 'update_user': {
        if ($id <= 0 || $name === '') { deny(400, 'id and name are required'); }
        $table = ($action === 'update_admin') ? 'admin' : 'user';

        if ($passwd !== '') {
            $hash = password_hash($passwd, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE `$table` SET name = ?, password = ? WHERE userid = ?");
            $stmt->bind_param('ssi', $name, $hash, $id);
        } else {
            $stmt = $con->prepare("UPDATE `$table` SET name = ? WHERE userid = ?");
            $stmt->bind_param('si', $name, $id);
        }
        if (!$stmt->execute()) { deny(500, 'DB error: ' . $con->error); }
        log_action($con, $caller_id, $action, "Updated $table #$id");
        echo json_encode(['success' => true, 'message' => ucfirst($table) . ' updated']);
        exit;
    }

    case 'delete_admin':
    case 'delete_user': {
        if (!$is_superadmin) { deny(403, 'Only superadmins can delete accounts'); }
        if ($id <= 0) { deny(400, 'id required'); }
        $table = ($action === 'delete_admin') ? 'admin' : 'user';

        if ($table === 'admin' && $id === $caller_id) {
            deny(400, 'You cannot delete your own admin account');
        }
        $stmt = $con->prepare("DELETE FROM `$table` WHERE userid = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) { deny(500, 'DB error: ' . $con->error); }
        log_action($con, $caller_id, $action, "Deleted $table #$id");
        echo json_encode(['success' => true, 'message' => ucfirst($table) . ' deleted']);
        exit;
    }

    default:
        deny(400, 'Invalid action');
}
