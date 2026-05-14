<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

function report_vehicle_ensure_table(mysqli $con): void {
    $sql = "CREATE TABLE IF NOT EXISTS vehicle_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        reporter_name VARCHAR(200) NOT NULL,
        reporter_email VARCHAR(200) DEFAULT NULL,
        reporter_role VARCHAR(20) NOT NULL DEFAULT 'user',
        plate_number VARCHAR(30) NOT NULL,
        owner_name VARCHAR(200) DEFAULT NULL,
        id_number VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        vehicle_type VARCHAR(100) DEFAULT NULL,
        vehicle_status VARCHAR(100) DEFAULT NULL,
        sticker VARCHAR(100) DEFAULT NULL,
        offense_details TEXT NOT NULL,
        latitude DECIMAL(10,8) NOT NULL,
        longitude DECIMAL(11,8) NOT NULL,
        photo_paths TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if (!$con->query($sql)) {
        http_response_code(500);
        echo json_encode([
            'success' => 0,
            'message' => 'Failed to initialize report storage',
        ]);
        exit;
    }
}

function report_vehicle_clean_string(?string $value): string {
    return trim((string)$value);
}

function report_vehicle_store_photos(array $files, string $plateNumber): array {
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'reports';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Failed to create upload directory');
    }

    $stored = [];
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $count = is_array($files['name']) ? count($files['name']) : 0;

    for ($i = 0; $i < $count; $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $original = (string)($files['name'][$i] ?? '');
        $tmp = (string)($files['tmp_name'][$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }

        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $ext = 'jpg';
        }

        $safePlate = preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper($plateNumber));
        $fileName = sprintf('report_%s_%s_%s.%s', $safePlate ?: 'vehicle', date('YmdHis'), uniqid(), $ext);
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Failed to store uploaded photo');
        }

        $stored[] = '/uploads/reports/' . $fileName;
    }

    return $stored;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => 0,
        'message' => 'POST method required',
    ]);
    exit;
}

report_vehicle_ensure_table($con);

$plateNumber = report_vehicle_clean_string($_POST['plate_number'] ?? '');
$offenseDetails = report_vehicle_clean_string($_POST['offense_details'] ?? '');
$ownerName = report_vehicle_clean_string($_POST['owner_name'] ?? '');
$idNumber = report_vehicle_clean_string($_POST['id_number'] ?? '');
$phone = report_vehicle_clean_string($_POST['phone'] ?? '');
$vehicleType = report_vehicle_clean_string($_POST['vehicle_type'] ?? '');
$vehicleStatus = report_vehicle_clean_string($_POST['vehicle_status'] ?? '');
$sticker = report_vehicle_clean_string($_POST['sticker'] ?? '');
$reporterName = report_vehicle_clean_string($_POST['reporter_name'] ?? '');
$reporterEmail = report_vehicle_clean_string($_POST['reporter_email'] ?? '');
$reporterRole = strtolower(report_vehicle_clean_string($_POST['reporter_role'] ?? 'user')) ?: 'user';
$reporterId = isset($_POST['reporter_id']) ? (int)$_POST['reporter_id'] : 0;
$latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

if ($plateNumber === '' || $offenseDetails === '' || $latitude === null || $longitude === null) {
    http_response_code(400);
    echo json_encode([
        'success' => 0,
        'message' => 'Plate number, offense details, and coordinates are required',
    ]);
    exit;
}

if (empty($_FILES['photos'])) {
    http_response_code(400);
    echo json_encode([
        'success' => 0,
        'message' => 'At least one photo is required',
    ]);
    exit;
}

try {
    $storedPhotos = report_vehicle_store_photos($_FILES['photos'], $plateNumber);
    if (count($storedPhotos) === 0) {
        throw new RuntimeException('No valid photos were uploaded');
    }

    $photoPaths = json_encode($storedPhotos);
    $stmt = $con->prepare("INSERT INTO vehicle_reports (
        user_id, reporter_name, reporter_email, reporter_role, plate_number,
        owner_name, id_number, phone, vehicle_type, vehicle_status, sticker,
        offense_details, latitude, longitude, photo_paths
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new RuntimeException('Failed to prepare report insert');
    }

    $stmt->bind_param(
        'issssssssssssss',
        $reporterId,
        $reporterName,
        $reporterEmail,
        $reporterRole,
        $plateNumber,
        $ownerName,
        $idNumber,
        $phone,
        $vehicleType,
        $vehicleStatus,
        $sticker,
        $offenseDetails,
        $latitude,
        $longitude,
        $photoPaths
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to save report');
    }

    echo json_encode([
        'success' => 1,
        'message' => 'Report submitted successfully',
        'report_id' => $stmt->insert_id,
        'photos' => $storedPhotos,
    ]);

    $stmt->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => $e->getMessage(),
    ]);
}

mysqli_close($con);
