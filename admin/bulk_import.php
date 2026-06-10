<?php
/**
 * Standalone bulk-import page retired (foundation/import): import/export now lives
 * per-category on each vehicle list page (Import / Export / Template buttons →
 * api/vehicle_import_xlsx.php + api/vehicle_export_xlsx.php). This stub keeps the
 * old URL / .htaccess alias from 404ing by sending admins to the dashboard.
 */
session_start();
if (isset($_GET['logout'])) { header('Location: /auth/logout.php'); exit; }
$lang = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /index.php' . $lang);
exit;
