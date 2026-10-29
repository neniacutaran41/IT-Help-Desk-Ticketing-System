<?php
/**
 * Global Page Header
 * DeskHero IT Help Desk System
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - DeskHero IT Help Desk' : 'DeskHero - IT Help Desk Ticketing System'; ?></title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container py-4 flex-grow-1">

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
    <div class="alert alert-<?php echo e($alertType); ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
        <i class="bi <?php echo $iconClass; ?> flex-shrink-0 me-2 fs-5"></i>
        <div class="flex-grow-1"><?php echo e($msg); ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php 
        endforeach;
    endforeach;
endif;
?>
