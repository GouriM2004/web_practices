<?php
require_once __DIR__ . '/../../src/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Smart Goals' : 'Smart Goals' ?></title>
    <link rel="manifest" href="/Smart_Shared_Goals_Tracker/public/manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container py-4">