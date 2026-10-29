<?php
/**
 * Create or Edit User (Administrator Only)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_role('admin');

$pdo = get_db();
$userId = (int)($_GET['id'] ?? 0);
$isEditing = $userId > 0;

$userRecord = null;
if ($isEditing) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userRecord = $stmt->fetch();

    if (!$userRecord) {
        set_flash('danger', 'User not found.');
        header('Location: users.php');
        exit;
    }
}

$error = '';
$name = $userRecord['name'] ?? '';
$email = $userRecord['email'] ?? '';
$role = $userRecord['role'] ?? 'employee';
$department = $userRecord['department'] ?? '';
$phone = $userRecord['phone'] ?? '';
$status = $userRecord['status'] ?? 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'employee');
        $department = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validRoles = ['admin', 'it_staff', 'employee'];
        if (!in_array($role, $validRoles, true)) $role = 'employee';

        $validStatuses = ['active', 'inactive'];
        if (!in_array($status, $validStatuses, true)) $status = 'active';

        if (empty($name) || empty($email)) {
            $error = 'Name and email address are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!$isEditing && empty($password)) {
            $error = 'Password is required when creating a new user.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!empty($password) && $password !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            // Check email uniqueness
            $checkSql = "SELECT id FROM users WHERE email = ?" . ($isEditing ? " AND id != ?" : "");
            $checkParams = $isEditing ? [$email, $userId] : [$email];
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute($checkParams);

            if ($checkStmt->fetch()) {
                $error = 'This email address is already in use by another user.';
            } else {
                try {
                    if ($isEditing) {
                        if (!empty($password)) {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, password = ?, role = ?, department = ?, phone = ?, status = ?
                                WHERE id = ?
                            ");
                            $updateStmt->execute([$name, $email, $hash, $role, $department, $phone, $status, $userId]);
                        } else {
                            $updateStmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, role = ?, department = ?, phone = ?, status = ?
                                WHERE id = ?
                            ");
                            $updateStmt->execute([$name, $email, $role, $department, $phone, $status, $userId]);
                        }

                        // If user modified their own profile, sync current session
                        if ($userId === (int)$_SESSION['user_id']) {
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_role'] = $role;
                            $_SESSION['user_department'] = $department;
                        }

                        set_flash('success', "User '{$name}' has been updated successfully.");
                        header('Location: users.php');
                        exit;

                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $insertStmt = $pdo->prepare("
                            INSERT INTO users (name, email, password, role, department, phone, status, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $insertStmt->execute([$name, $email, $hash, $role, $department, $phone, $status]);

                        set_flash('success', "New user '{$name}' has been created successfully.");
                        header('Location: users.php');
                        exit;
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = $isEditing ? 'Edit User: ' . $name : 'Add New User';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="users.php" class="text-decoration-none">Users</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $isEditing ? 'Edit User' : 'New User'; ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">
                    <i class="bi <?php echo $isEditing ? 'bi-person-gear' : 'bi-person-plus-fill'; ?> text-primary me-2"></i>
                    <?php echo $isEditing ? 'Edit User Account' : 'Create New User Account'; ?>
                </h4>
                <a href="users.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?php echo e($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="user-form.php<?php echo $isEditing ? '?id=' . $userId : ''; ?>" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($name); ?>" placeholder="e.g. Alex Rivera" required autofocus>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" placeholder="e.g. alex@helpdesk.com" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">System Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="employee" <?php echo ($role === 'employee') ? 'selected' : ''; ?>>Employee (Can submit and track own tickets)</option>
                                <option value="it_staff" <?php echo ($role === 'it_staff') ? 'selected' : ''; ?>>IT Support Staff (Can triage, claim, resolve tickets)</option>
                                <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Administrator (Full system management &amp; settings)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active (Can log in)</option>
                                <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive (Login blocked)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department" value="<?php echo e($department); ?>" placeholder="e.g. Technical Support, Sales, Marketing">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone / Extension</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($phone); ?>" placeholder="e.g. +1 555-0199">
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border mb-4">
                        <h6 class="fw-bold mb-2">
                            <i class="bi bi-shield-lock me-1"></i>
                            <?php echo $isEditing ? 'Change Password (Leave blank to keep current)' : 'Account Password'; ?>
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <label for="password" class="form-label small text-muted">Password <?php echo !$isEditing ? '<span class="text-danger">*</span>' : ''; ?></label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="<?php echo $isEditing ? 'New password (optional)' : 'Minimum 6 characters'; ?>" <?php echo !$isEditing ? 'required' : ''; ?>>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label small text-muted">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" <?php echo !$isEditing ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle me-1"></i> <?php echo $isEditing ? 'Save Changes' : 'Create User'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
