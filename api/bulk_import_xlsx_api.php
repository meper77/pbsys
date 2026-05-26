<?php
/**
 * API: Bulk Import Vehicles from XLSX
 * POST /admin/bulk_import.php
 * 
 * Handles XLSX file uploads and imports vehicle data
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

// Check file upload
if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File upload failed']));
}

$admin_email = $_SESSION['email_Admin'];

// Verify XLSX file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['xlsx_file']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File must be XLSX']));
}

try {
  // Require PhpSpreadsheet
  require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
  
  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
  $spreadsheet = $reader->load($_FILES['xlsx_file']['tmp_name']);
  $worksheet = $spreadsheet->getActiveSheet();
  
  $result = import_vehicles_from_xlsx($con, $worksheet, $admin_email);
  
  // Return response based on request header
  if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    echo json_encode($result);
  } else {
    // Browser request - redirect with message
    $_SESSION['import_result'] = $result;
    header('Location: /admin/bulk_import.php?result=1');
  }
  
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function import_vehicles_from_xlsx($con, $worksheet, $admin_email) {
  $inserted = 0;
  $skipped = 0;
  $errors = [];
  $seen_plates = [];
  
  // Row 1 is header, start from row 2
  foreach ($worksheet->getRowIterator(2) as $row) {
    $cells = $row->getCellIterator();
    $cells->setIterateOnlyExistingCells(false);
    
    $data = [];
    $col_index = 0;
    foreach ($cells as $cell) {
      $data[$col_index++] = $cell->getValue();
      if ($col_index >= 5) break; // Only need 5 columns
    }
    
    // Skip empty rows
    if (empty($data[0])) continue;
    
    try {
      // Parse row: Plate Number, Owner Name, Owner Phone, Brand, Category
      $plate_number = trim($data[0] ?? '');
      $owner_name = trim($data[1] ?? '');
      $owner_phone = trim($data[2] ?? '');
      $brand = trim($data[3] ?? '');
      $category = strtolower(trim($data[4] ?? ''));
      
      // Validate required fields
      if (empty($plate_number) || empty($owner_name) || empty($owner_phone) || empty($category)) {
        throw new Exception('Missing required fields');
      }
      
      // Validate category
      if (!in_array($category, ['visitor', 'staff', 'student', 'contractor'])) {
        throw new Exception('Invalid category. Must be: visitor, staff, student, contractor');
      }
      
      // Validate phone format (basic)
      if (!preg_match('/^\d{10,15}$/', preg_replace('/[\s\-\+\(\)]/', '', $owner_phone))) {
        throw new Exception('Invalid phone number format');
      }
      
      // Check for duplicates within file
      if (in_array($plate_number, $seen_plates)) {
        throw new Exception('Duplicate plate number in file');
      }
      $seen_plates[] = $plate_number;
      
      // Check if vehicle already exists in any category table
      $tables = ['visitorcar', 'staffcar', 'studentcar', 'contractorcar'];
      foreach ($tables as $tbl) {
        $check = $con->query("SELECT 1 FROM `$tbl` WHERE platenum = '{$con->real_escape_string($plate_number)}' LIMIT 1");
        if ($check && $check->num_rows > 0) {
          throw new Exception('Plate already exists in system');
        }
      }
      
      // Insert into appropriate table
      $table_name = $category . 'car';
      $id_col = match($category) {
        'visitor' => 'visitorid',
        'staff' => 'staffid',
        'student' => 'studentid',
        'contractor' => 'contractorid'
      };
      
      $insert_sql = "INSERT INTO `$table_name` (name, phone, model, platenum, created_at) 
                     VALUES ('{$con->real_escape_string($owner_name)}', 
                             '{$con->real_escape_string($owner_phone)}', 
                             '{$con->real_escape_string($brand)}', 
                             '{$con->real_escape_string($plate_number)}',
                             NOW())";
      
      if (!$con->query($insert_sql)) {
        throw new Exception($con->error);
      }
      
      $vehicle_id = $con->insert_id;
      
      // Create vehicle_status entry
      $status_sql = "INSERT INTO vehicle_status (vehicle_id, vehicle_type, status, created_at) 
                     VALUES ($vehicle_id, '$category', 'active', NOW())";
      $con->query($status_sql);
      
      // Log action
      $log_sql = "INSERT INTO admin_action_logs (admin_email, action, description, created_at) 
                  VALUES ('{$con->real_escape_string($admin_email)}', 
                          'import_vehicle', 
                          'Imported vehicle: $plate_number ($category)', 
                          NOW())";
      $con->query($log_sql);
      
      $inserted++;
      
    } catch (Exception $e) {
      $skipped++;
      $errors[] = "Row " . ($row->getRowIndex()) . ": " . $e->getMessage();
    }
  }
  
  return [
    'success' => true,
    'imported' => $inserted,
    'skipped' => $skipped,
    'errors' => array_slice($errors, 0, 10) // Return first 10 errors
  ];
}
?>
