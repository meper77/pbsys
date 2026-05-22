<?php
// Bulk-import vehicles from a CSV upload. Admin-only.
//
// POST multipart/form-data
//   csv_file=<file>   (or 'file' or 'csv' — all accepted)
//
// CSV columns (header row optional, skipped if first row contains 'name' or 'platenum'):
//   name, phone, idnumber, type, status, platenum
//
// Response: {success, inserted, skipped, errors: [{row, message}, ...]}

header('Content-Type: application/json');
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (empty($_SESSION['email_Admin'])) {
    http_response_code(401);
    echo json_encode(['success' => 0, 'message' => 'Admin authentication required']);
    exit;
}

$file = $_FILES['csv_file'] ?? $_FILES['file'] ?? $_FILES['csv'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => 0,
        'message' => 'CSV file upload required (field name csv_file).',
    ]);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['success' => 0, 'message' => 'File must be .csv']);
    exit;
}

$h = fopen($file['tmp_name'], 'r');
if (!$h) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'message' => 'Could not read uploaded file']);
    exit;
}

$inserted = 0;
$skipped  = 0;
$errors   = [];
$rowNum   = 0;

$insert = $con->prepare("INSERT INTO `owner` (name, phone, idnumber, type, status, platenum) VALUES (?,?,?,?,?,?)");
$check  = $con->prepare("SELECT id FROM `owner` WHERE platenum = ? LIMIT 1");

while (($row = fgetcsv($h, 2000, ',')) !== false) {
    $rowNum++;

    // Auto-skip header row
    if ($rowNum === 1) {
        $joined = strtolower(implode(',', $row));
        if (strpos($joined, 'platenum') !== false || strpos($joined, 'name') !== false) {
            continue;
        }
    }

    if (count($row) < 6) {
        $skipped++;
        $errors[] = ['row' => $rowNum, 'message' => 'Too few columns (need at least 6)'];
        continue;
    }

    [$name, $phone, $idnumber, $type, $status, $platenum] = array_map('trim', array_slice($row, 0, 6));

    if ($name === '' || $platenum === '' || $status === '') {
        $skipped++;
        $errors[] = ['row' => $rowNum, 'message' => 'Missing required field (name / platenum / status)'];
        continue;
    }

    $check->bind_param('s', $platenum);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        $skipped++;
        $errors[] = ['row' => $rowNum, 'message' => "Duplicate plate $platenum"];
        continue;
    }

    $insert->bind_param('ssssss', $name, $phone, $idnumber, $type, $status, $platenum);
    if ($insert->execute()) {
        $inserted++;
    } else {
        $skipped++;
        $errors[] = ['row' => $rowNum, 'message' => $insert->error];
    }
}

fclose($h);

echo json_encode([
    'success'  => 1,
    'inserted' => $inserted,
    'skipped'  => $skipped,
    'errors'   => $errors,
]);
