<?php
/**
 * User Management: View & Filter Users (Administrator Only)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_role('admin');

$pdo = get_db();

// Handle quick status toggle (active <-> inactive)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $targetId = (int)$_POST['toggle_status_id'];
        $currentUser = current_user();

        // Prevent self-deactivation
        if ($targetId === (int)$currentUser['id']) {
            set_flash('danger', 'You cannot deactivate your own administrator account.');
        } else {
            $stmt = $pdo->prepare("SELECT status, name FROM users WHERE id = ?");
            $stmt->execute([$targetId]);
            $targetUser = $stmt->fetch();

            if ($targetUser) {
                $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';
                $update = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $update->execute([$newStatus, $targetId]);

                set_flash('success', "User '{$targetUser['name']}' status changed to " . ucfirst($newStatus) . ".");
            }
        }
    }
    header('Location: users.php');
    exit;
}

// Search & Role filters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');

$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(name LIKE ? OR email LIKE ? OR department LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($roleFilter !== 'all' && in_array($roleFilter, ['admin', 'it_staff', 'employee'], true)) {
    $conditions[] = "role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'inactive'], true)) {
    $conditions[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "
    SELECT u.*, 
        (SELECT COUNT(*) FROM tickets WHERE user_id = u.id) AS submitted_tickets_count,
        (SELECT COUNT(*) FROM tickets WHERE assigned_to = u.id) AS assigned_tickets_count
    FROM users u
    {$whereClause}
    ORDER BY u.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'User Management';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>User Management</h2>
        <p class="text-muted mb-0">Manage system administrators, IT support staff, and employees</p>
    </div>
    <div>
        <a href="user-form.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Add New User
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="users.php" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small text-muted">Search User</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Search name, email, department...">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label for="role" class="form-label small text-muted">Role</label>
                <select class="form-select form-select-sm" id="role" name="role">
                    <option value="all">All Roles</option>
                    <option value="admin" <?php echo ($roleFilter === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                    <option value="it_staff" <?php echo ($roleFilter === 'it_staff') ? 'selected' : ''; ?>>IT Support Staff</option>
                    <option value="employee" <?php echo ($roleFilter === 'employee') ? 'selected' : ''; ?>>Employee</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="status" class="form-label small text-muted">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="all">All Statuses</option>
                    <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($statusFilter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="users.php" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No users found</h5>
                <p class="text-muted small">Try modifying your filter parameters or add a new user.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">User</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Workload / Activity</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="reply-avatar" style="width: 34px; height: 34px; font-size: 0.85rem; <?php echo $u['role'] === 'admin' ? 'background: #ef4444;' : ($u['role'] === 'it_staff' ? 'background: #6366f1;' : ''); ?>">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo e($u['name']); ?></div>
                                            <div class="text-muted small"><?php echo e($u['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo render_role_badge($u['role']); ?></td>
                                <td>
                                    <span class="small"><?php echo e($u['department'] ?: '—'); ?></span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo e($u['phone'] ?: '—'); ?></span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if ($u['role'] === 'employee'): ?>
                                            <span><strong><?php echo $u['submitted_tickets_count']; ?></strong> tickets filed</span>
                                        <?php else: ?>
                                            <span><strong><?php echo $u['assigned_tickets_count']; ?></strong> tickets assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="bi bi-dash-circle me-1"></i>Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <a href="user-form.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit User">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <form method="POST" action="users.php" class="d-inline" onsubmit="return confirm('Change status of <?php echo e($u['name']); ?> to <?php echo $u['status'] === 'active' ? 'Inactive' : 'Active'; ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="toggle_status_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $u['status'] === 'active' ? 'Deactivate User' : 'Activate User'; ?>">
                                                    <i class="bi <?php echo $u['status'] === 'active' ? 'bi-person-slash' : 'bi-person-check'; ?>"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
