<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// The native WebView app tags its UA with NEOVTRACKAPP. Mark the root element so
// the app gets the mobile card-stack table layout (assets/css/responsive.css +
// assets/js/nv-card-table.js); the browser web keeps its normal tables.
$nvIsApp = strpos((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 'NEOVTRACKAPP') !== false;
?><!DOCTYPE html>
<html lang="en"<?= $nvIsApp ? ' class="nv-app"' : '' ?>>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Light-only UI: keep the current light theme even when the browser/OS is in
         dark mode (also stops auto-darkening of native form controls + scrollbars). -->
    <meta name="color-scheme" content="light" />
    <title>NEO V-TRACK</title>
    <link rel="icon" type="image/png" href="/assets/images/neo-vtrack-logo.png">

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://unpkg.com/lucide@latest" defer></script>

    <!-- Minimal JS we still rely on (DataTables in admin/, jQuery is loaded only where needed) -->
    <script>window.NV_DT_BM = { info: "Memaparkan _START_–_END_ daripada _TOTAL_", infoEmpty: "Tiada rekod", infoFiltered: "(ditapis daripada _MAX_)", zeroRecords: "Tiada rekod sepadan", emptyTable: "Tiada data", paginate: { first: "Pertama", last: "Akhir", next: "Seterusnya", previous: "Sebelumnya" } };</script>
</head>
