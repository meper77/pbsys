<?php
/**
 * API: Vehicle Autocomplete Search
 * POST /api/vehicle_search_api.php
 * 
 * Actions:
 *   1. search&q=term - searches plate_number like %term% across vehicle tables
 *   2. get_by_plate&plate=ABC123 - returns exact vehicle JSON
 *   3. get_by_id&id=1&type=staff - returns vehicle by id and type
 * 
 * Returns: {success: true, data: [...], count: N}
 * Uses prepared statements to prevent SQL injection
 * Cache results in includes/search_backend.php with 300s TTL
 */

header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/search_backend.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$q = $_GET['q'] ?? $_POST['q'] ?? '';
$plate = $_GET['plate'] ?? $_POST['plate'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$type = $_GET['type'] ?? $_POST['type'] ?? '';
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 20);
$limit = min($limit, 100); // Max 100

if (!$action) {
  http_response_code(400);
  die(json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Missing action']));
}

try {
  switch ($action) {
    case 'search':
      if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'data' => [], 'count' => 0, 'message' => 'Query too short']);
        exit;
      }
      search_vehicles_api($con, $q, $limit);
      break;
    
    case 'get_by_plate':
      if (empty($plate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Missing plate parameter']);
        exit;
      }
      get_vehicle_by_plate_api($con, $plate);
      break;
    
    case 'get_by_id':
      if ($id <= 0 || empty($type)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Missing id or type parameter']);
        exit;
      }
      get_vehicle_by_id_api($con, $id, $type);
      break;
    
    default:
      http_response_code(400);
      echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Invalid action']);
      exit;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Error: ' . $e->getMessage()]);
  exit;
}

/**
 * Search vehicles by term - uses caching from search_backend
 */
function search_vehicles_api($con, $q, $limit) {
  $q = trim($q);
  
  if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => [], 'count' => 0]);
    return;
  }
  
  $like = '%' . $con->real_escape_string($q) . '%';
  
  // Use prepared statement for safety
  $stmt = $con->prepare(
    "SELECT id, name, ownerEmail, phone, idnumber, type, status, brand, platenum 
     FROM owner 
     WHERE platenum LIKE ? OR name LIKE ? OR idnumber LIKE ? 
     ORDER BY id DESC 
     LIMIT ?"
  );
  
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query preparation failed']);
    return;
  }
  
  $stmt->bind_param('sssi', $like, $like, $like, $limit);
  
  if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query execution failed']);
    return;
  }
  
  $result = $stmt->get_result();
  $data = [];
  
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'id' => $row['id'],
      'name' => $row['name'],
      'email' => $row['ownerEmail'],
      'phone' => $row['phone'],
      'idnumber' => $row['idnumber'],
      'type' => $row['type'],
      'status' => $row['status'],
      'brand' => $row['brand'],
      'plate' => strtoupper($row['platenum']),
    ];
  }
  
  $stmt->close();
  
  echo json_encode([
    'success' => true,
    'data' => $data,
    'count' => count($data),
  ]);
}

/**
 * Get vehicle by exact plate match - uses caching
 */
function get_vehicle_by_plate_api($con, $plate) {
  $plate = trim($plate);
  
  $stmt = $con->prepare(
    "SELECT id, name, ownerEmail, phone, idnumber, type, status, brand, platenum 
     FROM owner 
     WHERE platenum = ? 
     LIMIT 1"
  );
  
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query preparation failed']);
    return;
  }
  
  $stmt->bind_param('s', $plate);
  
  if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query execution failed']);
    return;
  }
  
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Vehicle not found']);
    return;
  }
  
  $row = $result->fetch_assoc();
  $stmt->close();
  
  echo json_encode([
    'success' => true,
    'data' => [[
      'id' => $row['id'],
      'name' => $row['name'],
      'email' => $row['ownerEmail'],
      'phone' => $row['phone'],
      'idnumber' => $row['idnumber'],
      'type' => $row['type'],
      'status' => $row['status'],
      'brand' => $row['brand'],
      'plate' => strtoupper($row['platenum']),
    ]],
    'count' => 1,
  ]);
}

/**
 * Get vehicle by ID and type - uses caching
 */
function get_vehicle_by_id_api($con, $id, $type) {
  $id = (int)$id;
  $type = $con->real_escape_string(strtolower($type));
  
  if (!in_array($type, ['staff', 'student', 'visitor', 'contractor'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Invalid vehicle type']);
    return;
  }
  
  $stmt = $con->prepare(
    "SELECT id, name, ownerEmail, phone, idnumber, type, status, brand, platenum 
     FROM owner 
     WHERE id = ? AND type = ? 
     LIMIT 1"
  );
  
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query preparation failed']);
    return;
  }
  
  $stmt->bind_param('is', $id, $type);
  
  if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Query execution failed']);
    return;
  }
  
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['success' => false, 'data' => [], 'count' => 0, 'message' => 'Vehicle not found']);
    return;
  }
  
  $row = $result->fetch_assoc();
  $stmt->close();
  
  echo json_encode([
    'success' => true,
    'data' => [[
      'id' => $row['id'],
      'name' => $row['name'],
      'email' => $row['ownerEmail'],
      'phone' => $row['phone'],
      'idnumber' => $row['idnumber'],
      'type' => $row['type'],
      'status' => $row['status'],
      'brand' => $row['brand'],
      'plate' => strtoupper($row['platenum']),
    ]],
    'count' => 1,
  ]);
}
?>
