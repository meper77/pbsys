<?php
/**
 * API: Bulk Delete Vehicles
 * POST /api/bulk_delete_api.php
 * 
 * Parameters:
 *   - action=bulk_delete (required)
 *   - vehicle_type=staff|visitor|student|contractor (required)
 *   - ids[]=1,2,3 (required - array of vehicle IDs)
 */

header('Content-Type: application/json');

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

// Require admin session
if (!isset($_SESSION['email_Admin'])) {
  http_response_code(403);
  die(json_encode(['success' => false, 'message' => 'Admin access required']));
}

$action = $_POST['action'] ?? null;
$vehicle_type = $_POST['vehicle_type'] ?? null;
$ids = $_POST['ids'] ?? [];

if ($action !== 'bulk_delete') {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid action']));
}

if (!$vehicle_type || !in_array($vehicle_type, ['visitor', 'staff', 'student', 'contractor'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid vehicle type']));
}

if (!is_array($ids) || empty($ids)) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'No IDs provided']));
}

try {
  bulk_delete_vehicles($con, $vehicle_type, $ids, $_SESSION['email_Admin']);
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function bulk_delete_vehicles($con, $vehicle_type, $ids, $admin_email) {
  $table = $vehicle_type . 'car';
  $id_col = $vehicle_type === 'visitor' ? 'visitorid' : 
            ($vehicle_type === 'staff' ? 'staffid' : 
             ($vehicle_type === 'student' ? 'studentid' : 'contractorid'));
  
  // Sanitize IDs
  $safe_ids = array_map(function($id) {
    return (int)$id;
  }, $ids);
  
  // Begin transaction
  $con->begin_transaction();
  
  try {
    $id_list = implode(',', $safe_ids);
    
    // Delete from M:M table first (foreign key)
    $con->query("
      DELETE FROM user_vehicle 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '{$vehicle_type}'
    ");
    
    // Delete from vehicle table
    $delete_query = "DELETE FROM $table WHERE $id_col IN ($id_list)";
    $con->query($delete_query);
    
    if ($con->affected_rows === 0) {
      throw new Exception('No vehicles deleted');
    }
    
    $count = $con->affected_rows;
    
    // Delete from status table
    $con->query("
      DELETE FROM vehicle_status 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '$vehicle_type'
    ");
    
    // Delete from cache
    $con->query("
      DELETE FROM vehicle_search_cache 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '$vehicle_type'
    ");
    
    $con->commit();
    
    // Log action
    $admin_query = "SELECT adminid FROM admin WHERE email = '{$con->real_escape_string($admin_email)}' LIMIT 1";
    $admin_result = $con->query($admin_query);
    if ($admin_result && $admin_result->num_rows > 0) {
      $admin = $admin_result->fetch_assoc();
      $con->query("
        INSERT INTO admin_action_logs (admin_id, action, details, created_at)
        VALUES ({$admin['adminid']}, 'bulk_delete', 'Deleted $count $vehicle_type vehicles', NOW())
      ");
    }
    
    echo json_encode([
      'success' => true,
      'message' => "Deleted $count vehicle(s)",
      'count' => $count
    ]);
    
  } catch (Exception $e) {
    $con->rollback();
    throw $e;
  }
}
?>
