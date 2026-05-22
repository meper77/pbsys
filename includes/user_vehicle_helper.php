<?php
/**
 * User-Vehicle Many-to-Many Relationship Helper
 * Provides utilities for managing user assignments to vehicles
 */

// Get all vehicles for a specific user
function get_user_vehicles($con, $user_id) {
    $stmt = $con->prepare("
        SELECT uv.*, v.*
        FROM user_vehicle uv
        LEFT JOIN visitor v ON uv.vehicle_type = 'visitor' AND uv.vehicle_id = v.id
        LEFT JOIN staff s ON uv.vehicle_type = 'staff' AND uv.vehicle_id = s.id
        LEFT JOIN student st ON uv.vehicle_type = 'student' AND uv.vehicle_id = st.id
        LEFT JOIN contractor c ON uv.vehicle_type = 'contractor' AND uv.vehicle_id = c.id
        WHERE uv.user_id = ?
        ORDER BY uv.assigned_at DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all users assigned to a specific vehicle
function get_vehicle_users($con, $vehicle_id, $vehicle_type) {
    $stmt = $con->prepare("
        SELECT u.*, uv.role, uv.assigned_at, uv.assigned_by
        FROM user_vehicle uv
        JOIN user u ON uv.user_id = u.userid
        WHERE uv.vehicle_id = ? AND uv.vehicle_type = ?
        ORDER BY uv.assigned_at DESC
    ");
    $stmt->bind_param('is', $vehicle_id, $vehicle_type);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Assign a user to a vehicle
function assign_user_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type, $role = 'owner', $assigned_by = null) {
    $stmt = $con->prepare("
        INSERT INTO user_vehicle (user_id, vehicle_id, vehicle_type, role, assigned_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE role = VALUES(role), assigned_at = NOW()
    ");
    $stmt->bind_param('iissi', $user_id, $vehicle_id, $vehicle_type, $role, $assigned_by);
    return $stmt->execute();
}

// Remove a user from a vehicle
function remove_user_from_vehicle($con, $user_id, $vehicle_id, $vehicle_type) {
    $stmt = $con->prepare("
        DELETE FROM user_vehicle
        WHERE user_id = ? AND vehicle_id = ? AND vehicle_type = ?
    ");
    $stmt->bind_param('iis', $user_id, $vehicle_id, $vehicle_type);
    return $stmt->execute();
}

// Check if user is assigned to vehicle
function is_user_assigned_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type) {
    $stmt = $con->prepare("
        SELECT 1 FROM user_vehicle
        WHERE user_id = ? AND vehicle_id = ? AND vehicle_type = ?
    ");
    $stmt->bind_param('iis', $user_id, $vehicle_id, $vehicle_type);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Bulk assign users to a vehicle
function bulk_assign_users_to_vehicle($con, $user_ids, $vehicle_id, $vehicle_type, $assigned_by = null) {
    $success_count = 0;
    foreach ((array)$user_ids as $user_id) {
        if (assign_user_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type, 'owner', $assigned_by)) {
            $success_count++;
        }
    }
    return $success_count;
}

// Get vehicle info with user count
function get_vehicle_with_users($con, $vehicle_id, $vehicle_type) {
    $table = match($vehicle_type) {
        'visitor' => 'visitorcar',
        'staff' => 'staffcar',
        'student' => 'studentcar',
        'contractor' => 'contractorcar',
        default => 'owner'
    };
    
    $stmt = $con->prepare("
        SELECT v.*, 
               (SELECT COUNT(*) FROM user_vehicle uv 
                WHERE uv.vehicle_id = ? AND uv.vehicle_type = ?) as user_count
        FROM `$table` v
        WHERE v.id = ?
    ");
    $stmt->bind_param('isi', $vehicle_id, $vehicle_type, $vehicle_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
