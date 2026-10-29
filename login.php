<?php
/**
 * User Login Page
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            try {
                $pdo = get_db();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact your IT administrator.';
                    } else {
                        // Successful login: regenerate session ID for security
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_department'] = $user['department'];

                        set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                        header('Location: dashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Invalid email address or password.';
                }
            } catch (Exception $e) {
                $error = 'System error: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Log In';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold">Sign In to DeskHero</h3>
                    <p class="text-muted small">Enter your credentials to access the IT Help Desk portal</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?php echo e($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" placeholder="name@company.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="password" class="form-label">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </div>

                    <div class="text-center text-muted small">
                        Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold">Register here</a>
                    </div>
                </form>

                <!-- Quick Demo Login Presets -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <small class="fw-bold text-uppercase text-muted" style="letter-spacing: 0.05em;">Quick Demo Accounts</small>
                        <span class="badge bg-secondary-subtle text-secondary small">1-Click Fill</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm demo-btn" 
                                data-demo-email="admin@helpdesk.com" 
                                data-demo-pass="admin123">
                            <i class="bi bi-shield-check me-1"></i>Admin
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm demo-btn" 
                                data-demo-email="support@helpdesk.com" 
                                data-demo-pass="staff123">
                            <i class="bi bi-headset me-1"></i>IT Staff
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm demo-btn" 
                                data-demo-email="employee@helpdesk.com" 
                                data-demo-pass="user123">
                            <i class="bi bi-person me-1"></i>Employee
                        </button>
                    </div>
                    <div class="text-muted small mt-2 fst-italic">
                        Passwords: <code>admin123</code>, <code>staff123</code>, <code>user123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
