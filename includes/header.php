<?php
/**
 * Global Page Header
 * DeskHero IT Help Desk System - Radiant Glow Edition
 */
if (!defined('DB_HOST')) {
    require_once dirname(__DIR__) . '/config/database.php';
}
if (!function_exists('is_logged_in')) {
    require_once dirname(__DIR__) . '/includes/auth.php';
}
if (!function_exists('get_flash')) {
    require_once dirname(__DIR__) . '/includes/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - DeskHero IT Help Desk' : 'DeskHero - IT Help Desk Ticketing System'; ?></title>
    
    <!-- Instant Theme Preference Loader -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('deskhero_theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Glowing Design System CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 glow-app-body position-relative">

<!-- Radiant Ambient Background Glow Halos -->
<div class="ambient-glow-wrapper" aria-hidden="true">
    <div class="ambient-orb orb-primary"></div>
    <div class="ambient-orb orb-cyan"></div>
    <div class="ambient-orb orb-purple"></div>
</div>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container py-4 flex-grow-1 position-relative z-1">

<?php
// Render Flash Alerts
$flashMessages = get_flash();
if (!empty($flashMessages)):
    foreach ($flashMessages as $alertType => $messages):
        $iconClass = 'bi-info-circle-fill';
        if ($alertType === 'success') {
            $iconClass = 'bi-check-circle-fill';
        } elseif ($alertType === 'danger') {
            $iconClass = 'bi-exclamation-triangle-fill';
        } elseif ($alertType === 'warning') {
            $iconClass = 'bi-exclamation-circle-fill';
        }
        foreach ($messages as $msg):
?>
    <div class="alert alert-<?php echo e($alertType); ?> alert-dismissible fade show d-flex align-items-center shadow-lg border-0 glow-alert glow-alert-<?php echo e($alertType); ?>" role="alert">
        <i class="bi <?php echo $iconClass; ?> flex-shrink-0 me-3 fs-4"></i>
        <div class="flex-grow-1"><?php echo e($msg); ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php 
        endforeach;
    endforeach;
endif;
?>
