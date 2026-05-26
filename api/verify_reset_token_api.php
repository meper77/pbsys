<?php
/**
 * Verify Password Reset Token
 * POST endpoint for validating reset tokens (optional enhancement)
 * Returns: JSON {valid: bool, message: string}
 */

header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

$token = $_POST['token'] ?? $_GET['token'] ?? null;
$response = ['valid' => false, 'message' => 'Invalid or expired token'];

if (!empty($token)) {
    $stmt = $conn->prepare("
        SELECT email FROM password_reset_tokens 
        WHERE token = ? AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response = [
            'valid' => true,
            'message' => 'Token is valid'
        ];
    }
    $stmt->close();
}

echo json_encode($response);
?>
