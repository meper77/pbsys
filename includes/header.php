<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NEO V-TRACK</title>
    <link rel="icon" type="image/png" href="/assets/images/neo-vtrack-logo.png">

    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://unpkg.com/lucide@latest" defer></script>

    <!-- Minimal JS we still rely on (DataTables in admin/, jQuery is loaded only where needed) -->
</head>
