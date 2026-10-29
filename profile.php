<?php
/**
 * User Profile & Password Change
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$userId = (int)$user['id'];
$pdo = get_db();

// Fetch latest user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userRecord = $stmt->fetch();

$error = '';
$activeTab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        // 1. Update Profile Details
        if ($formType === 'profile') {
            $name = trim($_POST['name'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (empty($name)) {
                $error = 'Your name cannot be empty.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, department = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $department, $phone, $userId]);

                $_SESSION['user_name'] = $name;
                $_SESSION['user_department'] = $department;

                set_flash('success', 'Profile information updated successfully.');
                header('Location: profile.php');
                exit;
            }
        }

        // 2. Change Password
        if ($formType === 'password') {
            $activeTab = 'password';
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                $error = 'Please enter both current and new passwords.';
            } elseif (!password_verify($currentPassword, $userRecord['password'])) {
                $error = 'Incorrect current password.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hash, $userId]);

                set_flash('success', 'Password updated successfully!');
                header('Location: profile.php');
                exit;
            }
        }
    }
}

$page_title = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="reply-avatar" style="width: 56px; height: 56px; font-size: 1.5rem; <?php echo $userRecord['role'] === 'admin' ? 'background: #ef4444;' : ($userRecord['role'] === 'it_staff' ? 'background: #6366f1;' : ''); ?>">
                        <?php echo strtoupper(substr($userRecord['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1"><?php echo e($userRecord['name']); ?></h4>
                        <div class="d-flex align-items-center gap-2">
                            <?php echo render_role_badge($userRecord['role']); ?>
                            <span class="text-muted small">&bull; Member since <?php echo date('M Y', strtotime($userRecord['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                <div><?php echo e($error); ?></div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link <?php echo ($activeTab === 'profile') ? 'active' : ''; ?>" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                            <i class="bi bi-person-lines-fill me-1"></i> Profile Information
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?php echo ($activeTab === 'password') ? 'active' : ''; ?>" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-pane" type="button" role="tab">
                            <i class="bi bi-key-fill me-1"></i> Security &amp; Password
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="profileTabsContent">
                    <!-- Tab 1: Profile Details -->
                    <div class="tab-pane fade <?php echo ($activeTab === 'profile') ? 'show active' : ''; ?>" id="info-pane" role="tabpanel">
                        <form method="POST" action="profile.php" data-prevent-double-submit="true">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="form_type" value="profile">

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo e($userRecord['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control bg-light" id="email" value="<?php echo e($userRecord['email']); ?>" readonly disabled>
                                <div class="form-text">Email address is your login identifier and cannot be changed directly.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department / Team</label>
                                    <input type="text" class="form-control" id="department" name="department" value="<?php echo e($userRecord['department']); ?>" placeholder="e.g. Sales, Marketing, IT">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($userRecord['phone']); ?>" placeholder="e.g. +1 555-0100">
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2 me-1"></i> Save Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Change Password -->
                    <div class="tab-pane fade <?php echo ($activeTab === 'password') ? 'show active' : ''; ?>" id="security-pane" role="tabpanel">
                        <form method="POST" action="profile.php" data-prevent-double-submit="true">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="form_type" value="password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Minimum 6 characters" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-shield-lock me-1"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
