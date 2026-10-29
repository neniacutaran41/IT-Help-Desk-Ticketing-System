<?php
/**
 * Main Application Dashboard
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$role = current_user_role();
$userId = (int)$user['id'];

$pdo = get_db();

// 1. Fetch counts based on role
$stats = get_ticket_counts($pdo, $userId, $role);

// 2. Fetch IT staff assigned count if staff
$myAssignedCount = 0;
if ($role === 'it_staff') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to = ? AND status NOT IN ('resolved', 'closed')");
    $stmt->execute([$userId]);
    $myAssignedCount = (int)$stmt->fetchColumn();
}

// 3. Fetch Recent Tickets
if ($role === 'employee') {
    $sqlRecent = "
        SELECT t.*, c.name AS category_name, u.name AS staff_name
        FROM tickets t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT 6
    ";
    $stmt = $pdo->prepare($sqlRecent);
    $stmt->execute([$userId]);
    $recentTickets = $stmt->fetchAll();
} else {
    // Admin & IT Staff see latest system tickets
    $sqlRecent = "
        SELECT t.*, c.name AS category_name, req.name AS requester_name, req.email AS requester_email, staff.name AS staff_name
        FROM tickets t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users req ON t.user_id = req.id
        LEFT JOIN users staff ON t.assigned_to = staff.id
        ORDER BY t.created_at DESC
        LIMIT 8
    ";
    $stmt = $pdo->query($sqlRecent);
    $recentTickets = $stmt->fetchAll();
}

// 4. For IT Staff, fetch unassigned tickets count
$unassignedCount = 0;
if ($role === 'it_staff' || $role === 'admin') {
    $unassignedCount = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE assigned_to IS NULL AND status = 'open'")->fetchColumn();
}

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">
            Welcome back, <?php echo e($user['name']); ?>!
        </h2>
        <p class="text-muted mb-0">
            <?php if ($role === 'admin'): ?>
                <i class="bi bi-shield-check text-danger me-1"></i> System Administrator Overview
            <?php elseif ($role === 'it_staff'): ?>
                <i class="bi bi-headset text-primary me-1"></i> IT Support Specialist Workspace
            <?php else: ?>
                <i class="bi bi-person-workspace text-success me-1"></i> Employee Help Desk Portal
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="ticket-create.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Submit New Ticket
        </a>
        <a href="tickets.php" class="btn btn-outline-secondary">
            <i class="bi bi-search me-1"></i> Browse Tickets
        </a>
    </div>
</div>

<!-- Key Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Total Tickets -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-total h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Total</span>
                <span class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-ticket-detailed"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['total']; ?></h3>
            <small class="text-muted mt-1"><?php echo $role === 'employee' ? 'My requests' : 'All tickets'; ?></small>
        </div>
    </div>

    <!-- Open Tickets -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-open h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Open</span>
                <span class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-envelope-open"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['open']; ?></h3>
            <small class="text-muted mt-1">Pending review</small>
        </div>
    </div>

    <!-- Assigned Tickets -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-assigned h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Assigned</span>
                <span class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-person-check"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['assigned']; ?></h3>
            <small class="text-muted mt-1">Under staff</small>
        </div>
    </div>

    <!-- In Progress -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-progress h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">In Progress</span>
                <span class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-gear-wide-connected"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['in_progress']; ?></h3>
            <small class="text-muted mt-1">Active troubleshooting</small>
        </div>
    </div>

    <!-- Resolved Tickets -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-resolved h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Resolved</span>
                <span class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check2-circle"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['resolved']; ?></h3>
            <small class="text-muted mt-1">Fix confirmed</small>
        </div>
    </div>

    <!-- Closed Tickets -->
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card stat-closed h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Closed</span>
                <span class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-archive"></i>
                </span>
            </div>
            <h3 class="fw-bold mb-0"><?php echo $stats['closed']; ?></h3>
            <small class="text-muted mt-1">Archived</small>
        </div>
    </div>
</div>

<!-- Role Specific Callout Cards -->
<?php if ($role === 'it_staff'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-primary bg-primary bg-opacity-10 p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-primary mb-1"><i class="bi bi-person-fill-gear me-1"></i> Tickets Assigned to You</h6>
                    <p class="small text-muted mb-0">You currently have <strong><?php echo $myAssignedCount; ?></strong> active ticket(s) requiring attention.</p>
                </div>
                <a href="tickets.php?assigned=my" class="btn btn-sm btn-primary">View Assigned</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning bg-warning bg-opacity-10 p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-inbox-fill me-1"></i> Unassigned Pool</h6>
                    <p class="small text-muted mb-0">There are <strong><?php echo $unassignedCount; ?></strong> unassigned ticket(s) waiting in the queue.</p>
                </div>
                <a href="tickets.php?assigned=unassigned" class="btn btn-sm btn-warning">Claim Tickets</a>
            </div>
        </div>
    </div>
<?php elseif ($role === 'admin'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-white p-3 border-danger-subtle d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Urgent Pending</span>
                    <h4 class="fw-bold text-danger mb-0 mt-1"><?php echo $stats['urgent_pending']; ?></h4>
                </div>
                <a href="tickets.php?priority=urgent&status=open" class="btn btn-sm btn-outline-danger">Review Urgent</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Unassigned Tickets</span>
                    <h4 class="fw-bold text-warning mb-0 mt-1"><?php echo $unassignedCount; ?></h4>
                </div>
                <a href="tickets.php?assigned=unassigned" class="btn btn-sm btn-outline-warning">Assign Staff</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-white p-3 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Admin Management</span>
                    <div class="d-flex gap-2 mt-2">
                        <a href="users.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-people"></i> Users</a>
                        <a href="categories.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags"></i> Categories</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Tickets Table -->
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-1 text-primary"></i> 
            <?php echo $role === 'employee' ? 'My Recent Tickets' : 'Latest Submitted Tickets'; ?>
        </h5>
        <a href="tickets.php" class="btn btn-sm btn-outline-primary">
            View All Tickets <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentTickets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No tickets found</h5>
                <p class="text-muted small">You don't have any tickets submitted yet.</p>
                <a href="ticket-create.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Submit First Ticket
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Ticket</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <?php if ($role !== 'employee'): ?>
                                <th>Requester</th>
                            <?php endif; ?>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="ticket-detail.php?id=<?php echo $ticket['id']; ?>" class="fw-bold text-decoration-none text-dark">
                                        #<?php echo e($ticket['ticket_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 260px;" title="<?php echo e($ticket['title']); ?>">
                                        <a href="ticket-detail.php?id=<?php echo $ticket['id']; ?>" class="text-decoration-none text-body">
                                            <?php echo e($ticket['title']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo e($ticket['category_name'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <?php if ($role !== 'employee'): ?>
                                    <td>
                                        <div class="small fw-semibold"><?php echo e($ticket['requester_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-muted" style="font-size: 0.78rem;"><?php echo e($ticket['requester_email'] ?? ''); ?></div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if (!empty($ticket['staff_name'])): ?>
                                        <span class="small fw-semibold text-primary">
                                            <i class="bi bi-person-check me-1"></i><?php echo e($ticket['staff_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo render_priority_badge($ticket['priority']); ?></td>
                                <td><?php echo render_status_badge($ticket['status']); ?></td>
                                <td class="small text-muted" title="<?php echo e($ticket['created_at']); ?>">
                                    <?php echo time_ago($ticket['created_at']); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="ticket-detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        View <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
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
