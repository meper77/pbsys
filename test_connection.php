<?php
header('Content-Type: application/json');

// Test database connection
$host = "localhost";
$db   = "neovtrack_db";
$user = "root";
$pass = "";

error_log("Test connection script - Testing DB connection");

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        'success' => 0,
        'error' => 'Database connection failed',
        'message' => $conn->connect_error
    ]);
    error_log("DB Connection Error: " . $conn->connect_error);
    exit();
}

error_log("DB Connection successful");

// Test table structure
$result = $conn->query("DESCRIBE user");

if (!$result) {
    echo json_encode([
        'success' => 0,
        'error' => 'Query failed',
        'message' => $conn->error
    ]);
    error_log("Query Error: " . $conn->error);
    exit();
}

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo json_encode([
    'success' => 1,
    'message' => 'Connection successful',
    'columns' => $columns,
    'host' => $host,
    'database' => $db
]);

$conn->close();
?>
