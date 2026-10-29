<?php
/**
 * User Registration Page (Employee Self-Service)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$name = '';
$email = '';
$department = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields (Name, Email, and Password).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Password confirmation does not match.';
        } else {
            try {
                $pdo = get_db();
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email address already exists.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, department, phone, status, created_at)
                        VALUES (?, ?, ?, 'employee', ?, ?, 'active', NOW())
                    ");
                    $stmt->execute([$name, $email, $hashed, $department, $phone]);

                    $newUserId = (int)$pdo->lastInsertId();

                    // Automatically sign in the new employee
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'employee';
                    $_SESSION['user_department'] = $department;

                    set_flash('success', 'Your account has been registered successfully! Welcome to DeskHero.');
                    header('Location: dashboard.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Create an Account';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-2">
                        <i class="bi bi-person-plus-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold">Create Employee Account</h3>
                    <p class="text-muted small">Sign up to submit and track your IT support requests</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?php echo e($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e($name); ?>" placeholder="e.g. John Doe" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Work Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" placeholder="e.g. john.doe@company.com" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department / Team</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?php echo e($department); ?>" placeholder="e.g. Marketing, Sales, HR">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone / Extension</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($phone); ?>" placeholder="e.g. +1 555-0123">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-1"></i> Register Account
                        </button>
                    </div>

                    <div class="text-center text-muted small">
                        Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
