<?php
// includes/header.php
// Usage: include this at the top of every page.
// Expects $pageTitle to be set beforehand.
$user = getCurrentUser();
$isAdmin = ($user['role'] === ROLE_ADMIN);
$baseUrl = BASE_URL;
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
    <meta name="description" content="College Toilet Cleanliness Check-In/Check-Out Monitoring System">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>

<!-- ===== TOP NAV ===== -->
<nav class="topnav">
    <div class="topnav-inner">
        <!-- Logo -->
        <a href="<?= $isAdmin ? $baseUrl.'/admin/index.php' : $baseUrl.'/user/dashboard.php' ?>" class="nav-brand">
            <span class="brand-icon"><i class="fas fa-toilet"></i></span>
            <span class="brand-text"><?= APP_NAME ?></span>
        </a>

        <!-- Nav Links -->
        <div class="nav-links" id="navLinks">
            <?php if ($isAdmin): ?>
                <a href="<?= $baseUrl ?>/admin/index.php"    class="nav-link <?= strpos($_SERVER['PHP_SELF'],'admin/index') !== false ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Dashboard
                </a>
                <a href="<?= $baseUrl ?>/admin/users.php"    class="nav-link <?= strpos($_SERVER['PHP_SELF'],'admin/users') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="<?= $baseUrl ?>/admin/toilets.php"  class="nav-link <?= strpos($_SERVER['PHP_SELF'],'admin/toilets') !== false ? 'active' : '' ?>">
                    <i class="fas fa-toilet"></i> Toilets
                </a>
                <a href="<?= $baseUrl ?>/admin/history.php"  class="nav-link <?= strpos($_SERVER['PHP_SELF'],'admin/history') !== false ? 'active' : '' ?>">
                    <i class="fas fa-clock-rotate-left"></i> History
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/user/dashboard.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'],'user/dashboard') !== false ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> My Toilets
                </a>
            <?php endif; ?>
        </div>

        <!-- Right side -->
        <div class="nav-right">
            <div class="nav-user-badge">
                <span class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                <div class="user-info-mini">
                    <span class="user-name-mini"><?= e($user['full_name']) ?></span>
                    <span class="user-role-badge <?= $isAdmin ? 'badge-admin' : 'badge-user' ?>"><?= $isAdmin ? 'Admin' : 'User' ?></span>
                </div>
                <div class="user-dropdown">
                    <div class="user-dropdown-inner">
                        <a href="<?= $baseUrl ?>/change_password.php"><i class="fas fa-key"></i> Change Password</a>
                        <a href="<?= $baseUrl ?>/logout.php" class="text-danger"><i class="fas fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
            <button class="nav-hamburger" id="navToggle" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- ===== MAIN CONTENT WRAPPER ===== -->
<main class="main-content">
