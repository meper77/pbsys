<?php
/**
 * API: Vehicle Autocomplete Search
 * GET/POST /api/vehicle_search_api.php
 * 
 * Actions:
 *   - search&q=term&type=staff (search vehicles by plate/brand/owner)
 *   - get_by_plate&plate=ABC123&type=staff (get single vehicle)
 *   - get_by_id&id=5&type=staff (get by ID)
 *   - update_cache (admin only - rebuild search cache)
 */

header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$q = $_GET['q'] ?? $_POST['q'] ?? '';
$vehicle_type = $_GET['type'] ?? $_POST['type'] ?? '';
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 20);
$limit = min($limit, 100); // Max 100

if (!$action) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Missing action']));
}

try {
  switch ($action) {
    case 'search':
      search_vehicles($con, $q, $vehicle_type, $limit);
      break;
    
    case 'get_by_plate':
      get_vehicle_by_plate($con, $_GET['plate'] ?? $_POST['plate'] ?? '', $vehicle_type);
      break;
    
    case 'get_by_id':
      get_vehicle_by_id($con, (int)($_GET['id'] ?? $_POST['id'] ?? 0), $vehicle_type);
      break;
    
    case 'update_cache':
      if (!isset($_SESSION['email_Admin'])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Admin only']));
      }
      update_search_cache($con);
      break;
    
    default:
      http_response_code(400);
      die(json_encode(['success' => false, 'message' => 'Invalid action']));
  }
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function search_vehicles($con, $q, $vehicle_type, $limit) {
  if (strlen($q) < 2) {
    die(json_encode(['success' => true, 'data' => []]));
  }
  
  $q = $con->real_escape_string($q);
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  // Search across cache table
  $where = "status = 'active'";
  if ($vehicle_type) {
    $where .= " AND vehicle_type = '$vehicle_type'";
  }
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE $where AND (
      plate_number LIKE '%$q%' OR 
      brand LIKE '%$q%' OR 
      owner_name LIKE '%$q%' OR 
      phone LIKE '%$q%'
    )
    LIMIT $limit
  ";
  
  $result = $con->query($query);
  
  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'label' => $row['plate_number'] . ' - ' . $row['brand'] . ' (' . $row['owner_name'] . ')',
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone']
    ];
  }
  
  echo json_encode(['success' => true, 'data' => $data]);
}

function get_vehicle_by_plate($con, $plate, $vehicle_type) {
  $plate = $con->real_escape_string($plate);
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE plate_number = '$plate' AND vehicle_type = '$vehicle_type'
  ";
  
  $result = $con->query($query);
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Vehicle not found']));
  }
  
  $row = $result->fetch_assoc();
  
  // Get assigned users via M:M
  $users = get_vehicle_users($con, $row['vehicle_id'], $row['vehicle_type']);
  
  echo json_encode([
    'success' => true,
    'data' => [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone'],
      'users' => $users
    ]
  ]);
}

function get_vehicle_by_id($con, $id, $vehicle_type) {
  $id = (int)$id;
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE vehicle_id = $id AND vehicle_type = '$vehicle_type'
  ";
  
  $result = $con->query($query);
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Vehicle not found']));
  }
  
  $row = $result->fetch_assoc();
  
  // Get assigned users
  $users = get_vehicle_users($con, $row['vehicle_id'], $row['vehicle_type']);
  
  echo json_encode([
    'success' => true,
    'data' => [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone'],
      'users' => $users
    ]
  ]);
}

function update_search_cache($con) {
  // Clear existing cache
  $con->query("TRUNCATE TABLE vehicle_search_cache");
  
  $types = ['visitor', 'staff', 'student', 'contractor'];
  $total = 0;
  
  foreach ($types as $type) {
    $table = $type . 'car';
    $id_col = $type === 'visitor' ? 'visitorid' : ($type === 'staff' ? 'staffid' : ($type === 'student' ? 'studentid' : 'contractorid'));
    $phone_col = $type === 'visitor' ? 'phone' : ($type === 'contractor' ? 'phone' : '');
    $staff_col = $type === 'staff' ? 'staffnumber' : '';
    $matric_col = $type === 'student' ? 'matricnumber' : '';
    
    // Get status for each vehicle
    $query = "
      SELECT 
        v.$id_col as vehicle_id,
        '$type' as vehicle_type,
        v.plate_number,
        v.brand,
        v.color,
        COALESCE(u.name, 'Unknown') as owner_name,
        COALESCE(u.phone, '') as phone,
        COALESCE(vs.status, 'active') as status
      FROM $table v
      LEFT JOIN user u ON v.userid = u.userid
      LEFT JOIN vehicle_status vs ON vs.vehicle_id = v.$id_col AND vs.vehicle_type = '$type'
    ";
    
    $result = $con->query($query);
    
    while ($row = $result->fetch_assoc()) {
      $ins_query = "
        INSERT INTO vehicle_search_cache 
        (vehicle_id, vehicle_type, plate_number, brand, color, phone, staff_number, matric_number, owner_name, status)
        VALUES (
          {$row['vehicle_id']},
          '{$row['vehicle_type']}',
          '{$con->real_escape_string($row['plate_number'])}',
          '{$con->real_escape_string($row['brand'])}',
          '{$con->real_escape_string($row['color'])}',
          '{$con->real_escape_string($row['phone'])}',
          '',
          '',
          '{$con->real_escape_string($row['owner_name'])}',
          '{$row['status']}'
        )
      ";
      
      if ($con->query($ins_query)) {
        $total++;
      }
    }
  }
  
  echo json_encode(['success' => true, 'message' => "Cache updated: $total vehicles"]);
}
?>
