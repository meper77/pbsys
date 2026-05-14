<?php
header('Content-Type: application/json');
session_start();

// Check if user is authenticated and is an admin
if (!isset($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - admin access required']);
    exit();
}

include 'connect.php';

// Get admin info
$admin_email = $_SESSION['email_Admin'];
$admin_query = mysqli_query($con, "SELECT userid, name FROM admin WHERE email = '$admin_email'");
$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit();
}

// Handle POST request to remove sticker
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'remove_sticker') {
        $table = mysqli_real_escape_string($con, $_POST['table'] ?? '');
        $record_id = (int)($_POST['record_id'] ?? 0);
        
        if (empty($table) || $record_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid table or record_id']);
            exit();
        }
        
        // Validate table name
        $valid_tables = ['staffcar', 'studentcar', 'visitorcar', 'contractorcar'];
        if (!in_array($table, $valid_tables)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid table name']);
            exit();
        }
        
        // Determine the ID column based on table
        $id_columns = [
            'staffcar' => 'staffid',
            'studentcar' => 'studentid',
            'visitorcar' => 'visitorid',
            'contractorcar' => 'contractorid'
        ];
        $id_column = $id_columns[$table];
        
        // Update sticker_status to 'removed'
        $update_query = "UPDATE $table SET sticker_status = 'removed', sticker = 'REMOVED', updated_at = NOW() 
                        WHERE $id_column = $record_id";
        
        if (mysqli_query($con, $update_query)) {
            // Log this action
            $log_query = "INSERT INTO admin_action_logs (admin_id, action, table_name, record_id, description, ip_address)
                         VALUES ({$admin['userid']}, 'remove_sticker', '$table', $record_id, 
                         'Removed sticker from $table record #$record_id', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($con, $log_query);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Sticker removed successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
        }
    } elseif ($action === 'restore_sticker') {
        $table = mysqli_real_escape_string($con, $_POST['table'] ?? '');
        $record_id = (int)($_POST['record_id'] ?? 0);
        $sticker_value = mysqli_real_escape_string($con, $_POST['sticker_value'] ?? 'ADA');
        
        if (empty($table) || $record_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid table or record_id']);
            exit();
        }
        
        // Validate table name
        $valid_tables = ['staffcar', 'studentcar', 'visitorcar', 'contractorcar'];
        if (!in_array($table, $valid_tables)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid table name']);
            exit();
        }
        
        // Determine the ID column based on table
        $id_columns = [
            'staffcar' => 'staffid',
            'studentcar' => 'studentid',
            'visitorcar' => 'visitorid',
            'contractorcar' => 'contractorid'
        ];
        $id_column = $id_columns[$table];
        
        // Update sticker_status back to 'active'
        $update_query = "UPDATE $table SET sticker_status = 'active', sticker = '$sticker_value', updated_at = NOW() 
                        WHERE $id_column = $record_id";
        
        if (mysqli_query($con, $update_query)) {
            // Log this action
            $log_query = "INSERT INTO admin_action_logs (admin_id, action, table_name, record_id, description, ip_address)
                         VALUES ({$admin['userid']}, 'restore_sticker', '$table', $record_id, 
                         'Restored sticker for $table record #$record_id', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($con, $log_query);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Sticker restored successfully'
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

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
