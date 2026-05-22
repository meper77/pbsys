<?php
/**
 * Admin: Manage User-Vehicle Assignments
 * URL: /admin/user_vehicle_assignments.php
 * Route: /manage_user_vehicle_assignments.php (legacy)
 */

session_start();

// Check admin access
if (empty($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

// Get all users
$users_result = $con->query("SELECT userid, name, email FROM user ORDER BY name");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$result_message = null;
$result_type = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    
    if ($action === 'assign') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $role = $_POST['role'] ?? 'owner';
        $admin_id = (int)($_SESSION['admin_id'] ?? 0);
        
        if ($user_id && $vehicle_id && $vehicle_type) {
            if (assign_user_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type, $role, $admin_id)) {
                $result_message = "User assigned to vehicle successfully";
                $result_type = "success";
            } else {
                $result_message = "Failed to assign user to vehicle";
                $result_type = "error";
            }
        }
    } elseif ($action === 'remove') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        
        if ($user_id && $vehicle_id && $vehicle_type) {
            if (remove_user_from_vehicle($con, $user_id, $vehicle_id, $vehicle_type)) {
                $result_message = "User removed from vehicle";
                $result_type = "success";
            } else {
                $result_message = "Failed to remove user from vehicle";
                $result_type = "error";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User-Vehicle Assignments</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .assignment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .assignment-table th, .assignment-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .assignment-table th { background-color: #f5f5f5; font-weight: bold; }
        .remove-btn { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
        .remove-btn:hover { background-color: #c82333; }
        .form-section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .submit-btn { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .submit-btn:hover { background-color: #0056b3; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 3px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage User-Vehicle Assignments</h1>
        
        <?php if ($result_message): ?>
            <div class="alert alert-<?php echo $result_type; ?>">
                <?php echo htmlspecialchars($result_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Assign User Form -->
        <div class="form-section">
            <h2>Assign User to Vehicle</h2>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                
                <div class="form-group">
                    <label for="user_id">User:</label>
                    <select name="user_id" id="user_id" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['userid']; ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type:</label>
                    <select name="vehicle_type" id="vehicle_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="visitor">Visitor</option>
                        <option value="staff">Staff</option>
                        <option value="student">Student</option>
                        <option value="contractor">Contractor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_id">Vehicle ID:</label>
                    <input type="number" name="vehicle_id" id="vehicle_id" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role" id="role">
                        <option value="owner">Owner</option>
                        <option value="co-owner">Co-owner</option>
                        <option value="driver">Driver</option>
                        <option value="passenger">Passenger</option>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">Assign User</button>
            </form>
        </div>
        
        <!-- Current Assignments -->
        <div class="form-section">
            <h2>Current Assignments</h2>
            <form method="GET">
                <div class="form-group">
                    <label for="filter_user">Filter by User:</label>
                    <select name="user_id" id="filter_user">
                        <option value="">-- All Users --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['userid']; ?>">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Filter</button>
            </form>
            
            <?php
            // Get assignments to display
            $filter_user_id = (int)($_GET['user_id'] ?? 0);
            
            if ($filter_user_id > 0) {
                $vehicles = get_user_vehicles($con, $filter_user_id);
            } else {
                // Get all assignments
                $result = $con->query("
                    SELECT uv.*, u.name as user_name, u.email, a.name as admin_name
                    FROM user_vehicle uv
                    JOIN user u ON uv.user_id = u.userid
                    LEFT JOIN admin a ON uv.assigned_by = a.userid
                    ORDER BY uv.assigned_at DESC
                    LIMIT 100
                ");
                $vehicles = $result->fetch_all(MYSQLI_ASSOC);
            }
            ?>
            
            <?php if ($vehicles): ?>
                <table class="assignment-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Vehicle Type</th>
                            <th>Vehicle ID</th>
                            <th>Role</th>
                            <th>Assigned At</th>
                            <th>Assigned By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $uv): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($uv['user_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($uv['vehicle_type']); ?></td>
                                <td><?php echo $uv['vehicle_id']; ?></td>
                                <td><?php echo htmlspecialchars($uv['role']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($uv['assigned_at'])); ?></td>
                                <td><?php echo htmlspecialchars($uv['admin_name'] ?? 'System'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="user_id" value="<?php echo $uv['user_id']; ?>">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $uv['vehicle_id']; ?>">
                                        <input type="hidden" name="vehicle_type" value="<?php echo $uv['vehicle_type']; ?>">
                                        <button type="submit" class="remove-btn" onclick="return confirm('Remove this assignment?');">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No assignments found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
</body>
</html>
