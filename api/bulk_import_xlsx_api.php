<?php
/**
 * API: Bulk Import Vehicles from XLSX
 * POST /api/bulk_import_xlsx_api.php
 * 
 * Expects:
 *   - file: XLSX file upload
 *   - vehicle_type: staff|visitor|student|contractor
 *   - assume_owner: email of user to assign as owner
 */

header('Content-Type: application/json');

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

// Require admin
if (!isset($_SESSION['email_Admin'])) {
  http_response_code(403);
  die(json_encode(['success' => false, 'message' => 'Admin access required']));
}

$vehicle_type = $_POST['vehicle_type'] ?? null;
$assume_owner = $_POST['assume_owner'] ?? null;

if (!$vehicle_type || !in_array($vehicle_type, ['visitor', 'staff', 'student', 'contractor'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid vehicle type']));
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File upload failed']));
}

// Verify XLSX file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File must be XLSX']));
}

try {
  // Require PhpSpreadsheet
  require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
  
  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
  $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
  $worksheet = $spreadsheet->getActiveSheet();
  
  $result = import_vehicles_from_xlsx($con, $vehicle_type, $worksheet, $assume_owner, $_SESSION['email_Admin']);
  
  echo json_encode($result);
  
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function import_vehicles_from_xlsx($con, $vehicle_type, $worksheet, $assume_owner, $admin_email) {
  $inserted = 0;
  $skipped = 0;
  $errors = [];
  
  $table = $vehicle_type . 'car';
  $id_col = $vehicle_type === 'visitor' ? 'visitorid' : 
            ($vehicle_type === 'staff' ? 'staffid' : 
             ($vehicle_type === 'student' ? 'studentid' : 'contractorid'));
  
  // Row 1 is header, start from row 2
  foreach ($worksheet->getRowIterator(2) as $row) {
    $cells = $row->getCellIterator();
    $cells->setIterateOnlyExistingCells(false);
    
    $data = [];
    $col_index = 0;
    foreach ($cells as $cell) {
      $data[$col_index++] = $cell->getValue();
    }
    
    // Skip empty rows
    if (empty($data[0])) continue;
    
    try {
      // Parse row based on vehicle type
      $vehicle = parse_vehicle_row($data, $vehicle_type);
      
      // Check uniqueness: plate + status=active
      $check_query = "
        SELECT $id_col FROM $table 
        WHERE plate_number = '{$vehicle['plate_number']}' 
        LIMIT 1
      ";
      $check = $con->query($check_query);
      
      if ($check && $check->num_rows > 0) {
        $skipped++;
        $errors[] = "Row " . ($row->getRowIndex()) . ": Plate {$vehicle['plate_number']} already exists";
        continue;
      }
      
      // Insert vehicle
      $columns = implode(',', array_keys($vehicle));
      $values = implode(',', array_map(function($v) use ($con) {
        return "'{$con->real_escape_string($v)}'";
      }, array_values($vehicle)));
      
      $insert_query = "INSERT INTO $table ($columns) VALUES ($values)";
      
      if (!$con->query($insert_query)) {
        throw new Exception($con->error);
      }
      
      $vehicle_id = $con->insert_id;
      
      // Assign owner if provided
      if ($assume_owner) {
        $owner_query = "SELECT userid FROM user WHERE email = '{$con->real_escape_string($assume_owner)}' LIMIT 1";
        $owner_result = $con->query($owner_query);
        
        if ($owner_result && $owner_result->num_rows > 0) {
          $owner = $owner_result->fetch_assoc();
          assign_user_to_vehicle($con, $owner['userid'], $vehicle_id, $vehicle_type, 'owner', 0);
        }
      }
      
      // Create status record
      $con->query("
        INSERT INTO vehicle_status (vehicle_id, vehicle_type, status)
        VALUES ($vehicle_id, '$vehicle_type', 'active')
      ");
      
      $inserted++;
      
    } catch (Exception $e) {
      $skipped++;
      $errors[] = "Row " . ($row->getRowIndex()) . ": " . $e->getMessage();
    }
  }
  
  return [
    'success' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'errors' => array_slice($errors, 0, 10) // Return first 10 errors
  ];
}

function parse_vehicle_row($data, $vehicle_type) {
  // Expected columns: plate, brand, color, year, [owner_name], [owner_phone]
  return [
    'plate_number' => $data[0] ?? '',
    'brand' => $data[1] ?? '',
    'color' => $data[2] ?? '',
    'year' => $data[3] ?? date('Y')
  ];
}
?>
