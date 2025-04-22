<?php
// Common header file to be included in all pages
// Ensures consistent header structure across the site

// Collect page title if provided, otherwise use default
$pageTitle = $pageTitle ?? 'Backlink Manager';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($_SESSION['csrf_token'])): ?>
        <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
    <?php endif; ?>
    <title><?= $pageTitle ?> - Backlink Manager</title>

    <!-- Common CSS -->
    <?php if (strpos($pageTitle, 'Dashboard') !== false || strpos($pageTitle, 'Proxy Manager') !== false): ?>
        <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>

    <!-- Additional CSS specific to the page can be included after this file -->
</head>

<body class="<?= isset($bodyClass) ? $bodyClass : 'bg-light' ?>">
    <div class="<?= strpos($pageTitle, 'Login') !== false || strpos($pageTitle, 'Register') !== false ? 'container py-5' : 'page' ?>">
        <?php if (strpos($pageTitle, 'Login') === false && strpos($pageTitle, 'Register') === false): ?>
            <?php include_once __DIR__ . '/navbar.php'; ?>
        <?php endif; ?>