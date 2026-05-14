<?php
header('Content-Type: application/json');
session_start();

// Check if user is authenticated and is a superadmin
if (!isset($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Get admin info
$admin_email = $_SESSION['email_Admin'];
$admin_query = mysqli_query($con, "SELECT userid, name FROM admin WHERE email = '$admin_email'");
$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit();
}

// Check if admin is superadmin (role field or by admin_id - assuming original admins are superadmin)
$is_superadmin = $admin['userid'] <= 10; // First 10 admins are considered superadmins

if (!$is_superadmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only superadmins can add new admins']);
    exit();
}

// Handle POST request to add new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_admin') {
        $email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
        $name = mysqli_real_escape_string($con, $_POST['name'] ?? '');
        $password = mysqli_real_escape_string($con, $_POST['password'] ?? '');
        
        if (empty($email) || empty($name) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Check if email already exists
        $check_query = mysqli_query($con, "SELECT userid FROM admin WHERE email = '$email'");
        if (mysqli_num_rows($check_query) > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        
        // Insert new admin
        $insert_query = "INSERT INTO admin (email, name, password, role, status) 
                        VALUES ('$email', '$name', '$password', 'admin', 'active')";
        
        if (mysqli_query($con, $insert_query)) {
            $new_admin_id = mysqli_insert_id($con);
            
            // Log this action
            $log_query = "INSERT INTO admin_action_logs (admin_id, action, description, ip_address)
                         VALUES ({$admin['userid']}, 'add_admin', 'Added new admin: $name ($email)', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($con, $log_query);
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'Admin added successfully',
                'admin_id' => $new_admin_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
        }
    } elseif ($action === 'add_user') {
        $email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
        $name = mysqli_real_escape_string($con, $_POST['name'] ?? '');
        $password = mysqli_real_escape_string($con, $_POST['password'] ?? '');
        
        if (empty($email) || empty($name) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Check if email already exists
        $check_query = mysqli_query($con, "SELECT userid FROM user WHERE email = '$email'");
        if (mysqli_num_rows($check_query) > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        
        // Insert new user
        $insert_query = "INSERT INTO user (email, name, password) 
                        VALUES ('$email', '$name', '$password')";
        
        if (mysqli_query($con, $insert_query)) {
            $new_user_id = mysqli_insert_id($con);
            
            // Log this action
            $log_query = "INSERT INTO admin_action_logs (admin_id, action, description, ip_address)
                         VALUES ({$admin['userid']}, 'add_user', 'Added new user: $name ($email)', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($con, $log_query);
            
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'User added successfully',
                'user_id' => $new_user_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

// Handle GET request to list admins
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'list_admins') {
        $query = "SELECT userid, email, name, role, status, last_login, created_at FROM admin ORDER BY created_at DESC";
        $result = mysqli_query($con, $query);
        
        $admins = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $admins[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'admins' => $admins
        ]);
    } elseif ($action === 'list_users') {
        $query = "SELECT userid, email, name, last_login, created_at FROM user ORDER BY created_at DESC";
        $result = mysqli_query($con, $query);
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
