<?php
/**
 * DeskHero System Health & Hostinger Deployment Verification Tool
 */
require_once __DIR__ . '/config/database.php';

$checks = [];

// 1. PHP Version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$checks[] = [
    'title'   => 'PHP Version (>= 7.4)',
    'status'  => $phpOk,
    'message' => "Current version: {$phpVersion}" . ($phpOk ? " (Compatible)" : " (Please switch to PHP 8.0+ in Hostinger hPanel)")
];

// 2. PDO & PDO MySQL extension
$pdoLoaded = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$checks[] = [
    'title'   => 'PDO MySQL Extension',
    'status'  => $pdoLoaded,
    'message' => $pdoLoaded ? 'PDO & pdo_mysql drivers are enabled.' : 'pdo_mysql extension is missing. Please enable it in PHP extensions.'
];

// 3. Database Connection
$dbConnected = false;
$dbMessage = '';
$pdo = null;
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $dbConnected = true;
    $dbMessage = "Successfully connected to database <strong>" . htmlspecialchars(DB_NAME) . "</strong> on <strong>" . htmlspecialchars(DB_HOST) . "</strong>";
} catch (PDOException $e) {
    $dbMessage = "Connection failed: " . htmlspecialchars($e->getMessage());
}

$checks[] = [
    'title'   => 'Database Connection',
    'status'  => $dbConnected,
    'message' => $dbMessage
];

// 4. Tables Verification
$tablesFound = [];
$requiredTables = ['users', 'categories', 'tickets', 'ticket_replies', 'ticket_history'];
$tablesOk = false;

if ($dbConnected && $pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($requiredTables, $allTables);

        if (empty($missing)) {
            $tablesOk = true;
            $tableCount = count($allTables);
            $msg = "All {$tableCount} required tables found in database.";
        } else {
            $msg = "Missing tables: " . implode(', ', $missing) . ". Please import <code>database.sql</code> via phpMyAdmin.";
        }
    } catch (Exception $e) {
        $msg = "Could not query tables: " . htmlspecialchars($e->getMessage());
    }
} else {
    $msg = "Database connection required first.";
}

$checks[] = [
    'title'   => 'Database Schema & Tables',
    'status'  => $tablesOk,
    'message' => $msg
];

// 5. Upload Directory Writable
$uploadDir = __DIR__ . '/uploads/';
$uploadExists = is_dir($uploadDir);
$uploadWritable = $uploadExists && is_writable($uploadDir);

$uploadMsg = '';
if (!$uploadExists) {
    @mkdir($uploadDir, 0755, true);
    $uploadExists = is_dir($uploadDir);
    $uploadWritable = $uploadExists && is_writable($uploadDir);
}

if ($uploadWritable) {
    $uploadMsg = "Upload directory <code>uploads/</code> exists and is writable (0755).";
} else {
    $uploadMsg = "Upload directory is not writable. Set folder permissions to 755 or 775 in Hostinger File Manager.";
}

$checks[] = [
    'title'   => 'Uploads Directory Permissions',
    'status'  => $uploadWritable,
    'message' => $uploadMsg
];

// Overall status
$allPassed = !in_array(false, array_column($checks, 'status'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskHero - System Deployment &amp; Health Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: system-ui, -apple-system, sans-serif; }
        .card { border-radius: 0.75rem; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 800px;">
        <div class="text-center mb-4">
            <h2 class="fw-bold">DeskHero Deployment Health Check</h2>
            <p class="text-muted">Hostinger Shared Hosting &amp; Local Environment Diagnostic Tool</p>
        </div>

        <div class="alert <?php echo $allPassed ? 'alert-success' : 'alert-warning'; ?> p-4 mb-4 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="bi <?php echo $allPassed ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> fs-1 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 fw-bold">
                        <?php echo $allPassed ? 'System Ready to Go!' : 'Action Required for Full Functionality'; ?>
                    </h5>
                    <p class="mb-0">
                        <?php echo $allPassed 
                            ? 'All system components, database tables, and permissions are correctly configured.' 
                            : 'Review the diagnostics below and follow the troubleshooting instructions.'; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-cpu me-1 text-primary"></i> Environment Status
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($checks as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">
                                <?php if ($item['status']): ?>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                            <div class="text-muted small mt-1"><?php echo $item['message']; ?></div>
                        </div>
                        <span class="badge <?php echo $item['status'] ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                            <?php echo $item['status'] ? 'PASSED' : 'FAILED'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-key me-1 text-primary"></i> Default Login Credentials</h5>
            <p class="small text-muted mb-3">If you imported <code>database.sql</code>, you can sign in with any of these sample accounts:</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-danger">Administrator</span></td>
                            <td><code>admin@helpdesk.com</code></td>
                            <td><code>admin123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">IT Support Staff</span></td>
                            <td><code>support@helpdesk.com</code></td>
                            <td><code>staff123</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">Employee</span></td>
                            <td><code>employee@helpdesk.com</code></td>
                            <td><code>user123</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="setup_check.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i> Re-check Status
            </a>
            <a href="login.php" class="btn btn-primary px-4">
                Proceed to Login <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</body>
</html>
