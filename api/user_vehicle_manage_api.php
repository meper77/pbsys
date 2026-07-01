<?php
/**
 * API: User-Vehicle Assignment Management
 * POST /api/user_vehicle_manage_api.php
 * 
 * Expected POST parameters:
 * - action: 'assign', 'remove', 'get_users', 'get_vehicles'
 * - user_id: User ID
 * - vehicle_id: Vehicle ID
 * - vehicle_type: 'visitor', 'staff', 'student', 'contractor'
 * - role: (optional) 'owner', 'co-owner', etc. (default: 'owner')
 */

header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';
nv_api_require_admin();   // mutates ownership links + returns user rows — admins only

$action = $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    switch ($action) {
        case 'assign':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $vehicle_type = $_POST['vehicle_type'] ?? '';
            $role = $_POST['role'] ?? 'owner';
            $admin_id = (int)($_POST['admin_id'] ?? 0);
            
            if (!$user_id || !$vehicle_id || !$vehicle_type) {
                throw new Exception('Missing required parameters');
            }
            
            if (assign_user_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type, $role, $admin_id ?: null)) {
                $response = [
                    'success' => true,
                    'message' => 'User assigned successfully',
                    'data' => [
                        'user_id' => $user_id,
                        'vehicle_id' => $vehicle_id,
                        'vehicle_type' => $vehicle_type,
                        'role' => $role
                    ]
                ];
            } else {
                throw new Exception('Failed to assign user');
            }
            break;
            
        case 'remove':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $vehicle_type = $_POST['vehicle_type'] ?? '';
            
            if (!$user_id || !$vehicle_id || !$vehicle_type) {
                throw new Exception('Missing required parameters');
            }
            
            if (remove_user_from_vehicle($con, $user_id, $vehicle_id, $vehicle_type)) {
                $response = [
                    'success' => true,
                    'message' => 'User removed from vehicle',
                    'data' => [
                        'user_id' => $user_id,
                        'vehicle_id' => $vehicle_id,
                        'vehicle_type' => $vehicle_type
                    ]
                ];
            } else {
                throw new Exception('Failed to remove user');
            }
            break;
            
        case 'get_users':
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $vehicle_type = $_POST['vehicle_type'] ?? '';
            
            if (!$vehicle_id || !$vehicle_type) {
                throw new Exception('Missing required parameters');
            }
            
            $users = get_vehicle_users($con, $vehicle_id, $vehicle_type);
            $response = [
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ];
            break;
            
        case 'get_vehicles':
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if (!$user_id) {
                throw new Exception('Missing user_id parameter');
            }
            
            $vehicles = get_user_vehicles($con, $user_id);
            $response = [
                'success' => true,
                'data' => $vehicles,
                'count' => count($vehicles)
            ];
            break;
            
        case 'check_assignment':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
            $vehicle_type = $_POST['vehicle_type'] ?? '';
            
            if (!$user_id || !$vehicle_id || !$vehicle_type) {
                throw new Exception('Missing required parameters');
            }
            
            $is_assigned = is_user_assigned_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type);
            $response = [
                'success' => true,
                'data' => [
                    'assigned' => $is_assigned,
                    'user_id' => $user_id,
                    'vehicle_id' => $vehicle_id,
                    'vehicle_type' => $vehicle_type
                ]
            ];
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
