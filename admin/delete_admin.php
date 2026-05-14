<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Check if admin is logged in
if (!isset($_SESSION['email_Admin'])) {
    header('location:/auth/login_admin.php');
    exit();
}

// Debug: Check table structure (uncomment if needed)
/*
$debug = mysqli_query($con, "SHOW COLUMNS FROM admin");
echo "<pre>Admin table columns:\n";
while ($row = mysqli_fetch_assoc($debug)) {
    print_r($row);
}
echo "</pre>";
exit();
*/

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current_admin_email = $_SESSION['email_Admin'];
    
    // First, let's find out what columns exist
    $check_columns = mysqli_query($con, "SHOW COLUMNS FROM admin");
    $columns = [];
    if ($check_columns) {
        while ($col = mysqli_fetch_assoc($check_columns)) {
            $columns[] = $col['Field'];
        }
    }
    
    // Determine email column
    $email_column = 'email';
    if (in_array('email_Admin', $columns)) {
        $email_column = 'email_Admin';
    } elseif (in_array('email', $columns)) {
        $email_column = 'email';
    } elseif (in_array('admin_email', $columns)) {
        $email_column = 'admin_email';
    }
    
    // Determine ID column
    $id_column = 'userid'; // Based on your original code
    if (in_array('userid', $columns)) {
        $id_column = 'userid';
    } elseif (in_array('adminid', $columns)) {
        $id_column = 'adminid';
    } elseif (in_array('id', $columns)) {
        $id_column = 'id';
    } elseif (in_array('admin_id', $columns)) {
        $id_column = 'admin_id';
    }
    
    // Check if there's at least one admin left (prevent deleting all admins)
    $count_query = mysqli_query($con, "SELECT COUNT(*) as total FROM admin");
    $admin_count = 0;
    if ($count_query) {
        $count_data = mysqli_fetch_assoc($count_query);
        $admin_count = $count_data['total'];
    }
    
    // Prevent deleting if it's the last admin
    if ($admin_count <= 1) {
        echo "<script>alert('Tidak boleh membuang admin terakhir!'); window.location.href='/admin/dashboard.php';</script>";
        exit();
    }
    
    // Get current admin's ID to prevent self-deletion
    $current_admin_id = null;
    $current_admin_query = mysqli_query($con, "SELECT $id_column FROM admin WHERE $email_column = '$current_admin_email'");
    
    if ($current_admin_query && mysqli_num_rows($current_admin_query) > 0) {
        $current_admin = mysqli_fetch_assoc($current_admin_query);
        $current_admin_id = $current_admin[$id_column];
        
        // Prevent self-deletion
        if ($id == $current_admin_id) {
            echo "<script>alert('Anda tidak boleh membuang akaun sendiri!'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        }
    }
    
    // Try to delete using the determined ID column
    $sql = "DELETE FROM `admin` WHERE $id_column = $id";
    $result = mysqli_query($con, $sql);
    
    if ($result) {
        if (mysqli_affected_rows($con) > 0) {
            echo "<script>alert('Admin berjaya dibuang!'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Admin tidak ditemui!'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        }
    } else {
        // If deletion failed, try alternative approach - delete by trying different columns
        $deleted = false;
        $possible_id_columns = ['userid', 'adminid', 'id', 'admin_id'];
        
        foreach ($possible_id_columns as $col) {
            if (in_array($col, $columns)) {
                $sql = "DELETE FROM `admin` WHERE $col = $id";
                $result = mysqli_query($con, $sql);
                if ($result && mysqli_affected_rows($con) > 0) {
                    $deleted = true;
                    break;
                }
            }
        }
        
        if ($deleted) {
            echo "<script>alert('Admin berjaya dibuang!'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        } else {
            $error_msg = "Gagal membuang admin: " . mysqli_error($con);
            echo "<script>alert('$error_msg'); window.location.href='/admin/dashboard.php';</script>";
            exit();
        }
    }
} else {
    echo "<script>alert('ID Admin tidak sah!'); window.location.href='/admin/dashboard.php';</script>";
    exit();
}
?>