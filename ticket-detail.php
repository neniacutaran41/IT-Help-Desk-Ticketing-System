<?php
/**
 * Ticket Details, Replies, Status Tracking, and Assignment
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$role = current_user_role();
$userId = (int)$user['id'];
$pdo = get_db();

$ticketId = (int)($_GET['id'] ?? 0);
if (!$ticketId) {
    set_flash('danger', 'Ticket ID is required.');
    header('Location: tickets.php');
    exit;
}

// Fetch Ticket details with requester, category, and assigned staff
$stmt = $pdo->prepare("
    SELECT 
        t.*, 
        c.name AS category_name, 
        req.name AS requester_name, 
        req.email AS requester_email, 
        req.department AS requester_dept,
        req.phone AS requester_phone,
        staff.name AS staff_name,
        staff.email AS staff_email
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users req ON t.user_id = req.id
    LEFT JOIN users staff ON t.assigned_to = staff.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash('danger', 'Ticket not found.');
    header('Location: tickets.php');
    exit;
}

// Access check: Employees can only view their own tickets
if ($role === 'employee' && (int)$ticket['user_id'] !== $userId) {
    set_flash('danger', 'Access denied: You do not have permission to view this ticket.');
    header('Location: dashboard.php');
    exit;
}

// Fetch IT Staff list for assignment dropdown (Admin & Staff)
$staffList = [];
if ($role !== 'employee') {
    $staffList = $pdo->query("SELECT id, name, email FROM users WHERE role IN ('admin', 'it_staff') AND status = 'active' ORDER BY name ASC")->fetchAll();
}

// Fetch Replies
// Note: If user is employee, exclude internal notes
$replyCondition = ($role === 'employee') ? "AND r.is_internal = 0" : "";
$replyStmt = $pdo->prepare("
    SELECT r.*, u.name AS author_name, u.role AS author_role
    FROM ticket_replies r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ? {$replyCondition}
    ORDER BY r.created_at ASC
");
$replyStmt->execute([$ticketId]);
$replies = $replyStmt->fetchAll();

// Fetch Activity History (Admin & IT Staff see full; Employees see status/assign events)
$historyStmt = $pdo->prepare("
    SELECT h.*, u.name AS actor_name
    FROM ticket_history h
    LEFT JOIN users u ON h.user_id = u.id
    WHERE h.ticket_id = ? " . ($role === 'employee' ? "AND h.action != 'internal_note'" : "") . "
    ORDER BY h.created_at DESC
");
$historyStmt->execute([$ticketId]);
$historyEvents = $historyStmt->fetchAll();

$page_title = "#" . $ticket['ticket_number'] . " - " . $ticket['title'];
include __DIR__ . '/includes/header.php';
?>

<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tickets.php" class="text-decoration-none">Tickets</a></li>
            <li class="breadcrumb-item active" aria-current="page">#<?php echo e($ticket['ticket_number']); ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="fs-4 fw-bold text-dark">#<?php echo e($ticket['ticket_number']); ?>:</span>
                <span class="fs-4 fw-bold"><?php echo e($ticket['title']); ?></span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php echo render_status_badge($ticket['status']); ?>
                <?php echo render_priority_badge($ticket['priority']); ?>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-tag me-1"></i><?php echo e($ticket['category_name'] ?? 'General Support'); ?>
                </span>
                <span class="text-muted small ms-2">
                    <i class="bi bi-clock me-1"></i>Submitted <?php echo time_ago($ticket['created_at']); ?>
                </span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="tickets.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (has_role('admin')): ?>
                <form method="POST" action="ticket-action.php" onsubmit="return confirm('Are you sure you want to permanently delete this ticket and all its replies?');" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash3 me-1"></i> Delete
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Main Left Column: Ticket Description & Discussion -->
    <div class="col-lg-8">
        <!-- Original Ticket Problem Description Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="reply-avatar">
                        <?php echo strtoupper(substr($ticket['requester_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <div class="fw-bold text-dark"><?php echo e($ticket['requester_name'] ?? 'Unknown Requester'); ?></div>
                        <div class="text-muted small">
                            <?php echo e($ticket['requester_dept'] ?? 'General Staff'); ?> &bull; <?php echo e($ticket['requester_email'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                <div class="text-muted small">
                    <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="ticket-description mb-3" style="white-space: pre-line; line-height: 1.6;">
                    <?php echo e($ticket['description']); ?>
                </div>

                <?php if (!empty($ticket['attachment'])): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="small fw-bold text-muted mb-2 text-uppercase">Attached File:</div>
                        <div class="d-inline-flex align-items-center p-2 rounded border bg-light">
                            <i class="bi bi-paperclip fs-5 text-primary me-2"></i>
                            <span class="fw-semibold text-truncate me-3" style="max-width: 250px;">
                                <?php echo e($ticket['attachment_original_name'] ?: $ticket['attachment']); ?>
                            </span>
                            <a href="uploads/<?php echo urlencode($ticket['attachment']); ?>" target="_blank" download class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Discussion / Replies Thread -->
        <div id="replies" class="mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-chat-left-text text-primary"></i>
                Responses &amp; Activity
                <span class="badge bg-secondary-subtle text-secondary small"><?php echo count($replies); ?></span>
            </h5>

            <?php if (empty($replies)): ?>
                <div class="card border-0 bg-white p-4 text-center text-muted mb-4">
                    <i class="bi bi-chat-square-dots fs-3 mb-2"></i>
                    No responses or notes have been posted on this ticket yet.
                </div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <?php
                    $isInternal = (bool)$reply['is_internal'];
                    $authorRole = $reply['author_role'];
                    $avatarClass = '';
                    if ($authorRole === 'admin') $avatarClass = 'avatar-admin';
                    elseif ($authorRole === 'it_staff') $avatarClass = 'avatar-it';
                    ?>
                    <div class="reply-card p-3 mb-3 <?php echo $isInternal ? 'is-internal' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="reply-avatar <?php echo $avatarClass; ?>" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                    <?php echo strtoupper(substr($reply['author_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark me-2"><?php echo e($reply['author_name'] ?? 'User'); ?></span>
                                    <?php echo render_role_badge($authorRole); ?>
                                    <?php if ($isInternal): ?>
                                        <span class="badge bg-warning text-dark border border-warning-subtle ms-1">
                                            <i class="bi bi-lock-fill me-1"></i>Internal IT Note (Staff Only)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="text-muted small" title="<?php echo e($reply['created_at']); ?>">
                                <?php echo time_ago($reply['created_at']); ?>
                            </span>
                        </div>

                        <div class="ps-md-5 pt-1" style="white-space: pre-line; line-height: 1.55;">
                            <?php echo e($reply['message']); ?>

                            <?php if (!empty($reply['attachment'])): ?>
                                <div class="mt-3 pt-2">
                                    <div class="d-inline-flex align-items-center p-2 rounded border bg-light">
                                        <i class="bi bi-paperclip fs-5 text-primary me-2"></i>
                                        <span class="small fw-semibold text-truncate me-3" style="max-width: 250px;">
                                            <?php echo e($reply['attachment_original_name'] ?: $reply['attachment']); ?>
                                        </span>
                                        <a href="uploads/<?php echo urlencode($reply['attachment']); ?>" target="_blank" download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Post Reply Form -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-reply-fill text-primary me-1"></i> Post a Reply
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="ticket-action.php" enctype="multipart/form-data" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="add_reply">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

                    <div class="mb-3">
                        <textarea class="form-control" name="message" rows="4" placeholder="Write your message or response here..." required></textarea>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label for="replyAttachment" class="form-label small text-muted mb-1">Attach File (Optional)</label>
                            <input class="form-control form-control-sm" type="file" id="replyAttachment" name="attachment">
                        </div>

                        <?php if ($role !== 'employee'): ?>
                            <div class="col-md-6 text-md-end mt-2 mt-md-4">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                                    <label class="form-check-label fw-bold text-warning-emphasis" for="is_internal">
                                        <i class="bi bi-lock-fill"></i> Internal Note (Hidden from employee)
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send-fill me-1"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar (Ticket Details, Status Actions, Assignment, History) -->
    <div class="col-lg-4">
        <!-- 1. Status Update Control -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-arrow-repeat text-primary me-1"></i> Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="ticket-action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

                    <div class="mb-3">
                        <label for="statusSelect" class="form-label small text-muted">Ticket Status</label>
                        <select class="form-select" id="statusSelect" name="status">
                            <?php if ($role !== 'employee'): ?>
                                <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                <option value="assigned" <?php echo ($ticket['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in_progress" <?php echo ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                            <?php else: ?>
                                <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>Open (Reopen)</option>
                                <option value="resolved" <?php echo ($ticket['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved (Issue Fixed)</option>
                                <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>Closed (Completed)</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="statusComment" class="form-label small text-muted">Status Note / Reason</label>
                        <input type="text" class="form-control form-control-sm" id="statusComment" name="status_comment" placeholder="Optional remark (e.g. Solution verified)">
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Status Change
                    </button>
                </form>
            </div>
        </div>

        <!-- 2. Staff Assignment Control (Admin & IT Staff) -->
        <?php if ($role !== 'employee'): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-check text-primary me-1"></i> Assign Ticket</h6>
                    <?php if ($ticket['assigned_to'] != $userId): ?>
                        <form method="POST" action="ticket-action.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="action" value="assign_ticket">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="assigned_to" value="<?php echo $userId; ?>">
                            <button type="submit" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                                Assign to Me
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="ticket-action.php">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="assign_ticket">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">

                        <div class="mb-3">
                            <label for="assignSelect" class="form-label small text-muted">IT Support Specialist</label>
                            <select class="form-select" id="assignSelect" name="assigned_to">
                                <option value="">-- Unassigned (General Queue) --</option>
                                <?php foreach ($staffList as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo ($ticket['assigned_to'] == $staff['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($staff['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-sm btn-outline-dark w-100">
                            <i class="bi bi-person-plus me-1"></i> Update Assignment
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. Ticket Information Summary -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle text-primary me-1"></i> Ticket Information</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Requester:</span>
                        <span class="fw-semibold text-dark"><?php echo e($ticket['requester_name']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Email:</span>
                        <span class="fw-semibold"><?php echo e($ticket['requester_email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Department:</span>
                        <span class="fw-semibold"><?php echo e($ticket['requester_dept'] ?: 'N/A'); ?></span>
                    </li>
                    <?php if (!empty($ticket['requester_phone'])): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted">Phone:</span>
                            <span class="fw-semibold"><?php echo e($ticket['requester_phone']); ?></span>
                        </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Assigned To:</span>
                        <span class="fw-semibold text-primary">
                            <?php echo e($ticket['staff_name'] ?? 'Unassigned'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Created:</span>
                        <span><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted">Last Updated:</span>
                        <span><?php echo time_ago($ticket['updated_at']); ?></span>
                    </li>
                    <?php if (!empty($ticket['resolved_at'])): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted">Resolved On:</span>
                            <span class="text-success fw-semibold"><?php echo date('M j, Y g:i A', strtotime($ticket['resolved_at'])); ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($ticket['closed_at'])): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted">Closed On:</span>
                            <span class="text-secondary"><?php echo date('M j, Y g:i A', strtotime($ticket['closed_at'])); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- 4. Activity History Timeline -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Status &amp; Audit Log</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($historyEvents)): ?>
                    <p class="text-muted small mb-0">No history events logged yet.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($historyEvents as $evt): ?>
                            <?php
                            $markerClass = '';
                            if ($evt['action'] === 'status_changed') $markerClass = 'marker-status';
                            if ($evt['new_value'] === 'resolved') $markerClass = 'marker-resolved';
                            ?>
                            <div class="timeline-item">
                                <span class="timeline-marker <?php echo $markerClass; ?>"></span>
                                <div class="small fw-semibold text-dark">
                                    <?php echo e($evt['comment'] ?: ucwords(str_replace('_', ' ', $evt['action']))); ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.76rem;">
                                    <span><?php echo e($evt['actor_name'] ?? 'System'); ?></span> &bull; 
                                    <span><?php echo time_ago($evt['created_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
