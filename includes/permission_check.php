<?php
/**
 * Permission Check Helper Functions
 * 
 * Provides role-based access control for admin pages
 */

function require_admin() {
  if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    die('Access denied. Admin access required.');
  }
}

function is_admin() {
  return isset($_SESSION['email_Admin']);
}

function is_user() {
  return isset($_SESSION['email']) && !isset($_SESSION['email_Admin']);
}

function can_view_admin_list() {
  return is_admin();
}

function can_view_user_list() {
  return is_admin();
}

function can_view_reports() {
  return is_admin();
}

function can_view_import() {
  return is_admin();
}

function redirect_unauthorized() {
  header('Location: /dashboard.php');
  exit;
}
?>
