<?php
/**
 * API: User Search for Autocomplete
 * POST /api/user_search_api.php
 */

header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';
nv_api_require_admin();   // exposes the user directory (email, phone, IC) — admins only

$action = $_GET['action'] ?? $_POST['action'] ?? 'search';
$response = ['success' => false, 'data' => []];

try {
    switch ($action) {
        case 'list_all':
            // Get all users (for initial autocomplete load)
            $result = $con->query("
                SELECT userid, name, email, phone, idnumber
                FROM user
                ORDER BY name ASC
                LIMIT 500
            ");
            
            if ($result) {
                $response = [
                    'success' => true,
                    'data' => $result->fetch_all(MYSQLI_ASSOC),
                    'count' => $result->num_rows
                ];
            } else {
                throw new Exception('Database query failed');
            }
            break;

        case 'search':
            $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
            
            if (strlen($query) < 2) {
                throw new Exception('Search query too short');
            }

            $search_term = '%' . $con->real_escape_string($query) . '%';
            $result = $con->query("
                SELECT userid, name, email, phone, idnumber
                FROM user
                WHERE name LIKE '$search_term' 
                   OR email LIKE '$search_term'
                   OR phone LIKE '$search_term'
                   OR idnumber LIKE '$search_term'
                ORDER BY 
                    CASE 
                        WHEN name LIKE '" . $con->real_escape_string($query) . "%' THEN 1
                        ELSE 2
                    END,
                    name ASC
                LIMIT 50
            ");

            if ($result) {
                $response = [
                    'success' => true,
                    'data' => $result->fetch_all(MYSQLI_ASSOC),
                    'count' => $result->num_rows
                ];
            } else {
                throw new Exception('Search failed');
            }
            break;

        case 'get_by_id':
            $user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
            
            if (!$user_id) {
                throw new Exception('User ID required');
            }

            $result = $con->query("
                SELECT userid, name, email, phone, idnumber
                FROM user
                WHERE userid = $user_id
            ");

            if ($result && $result->num_rows > 0) {
                $response = [
                    'success' => true,
                    'data' => $result->fetch_assoc()
                ];
            } else {
                throw new Exception('User not found');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>
