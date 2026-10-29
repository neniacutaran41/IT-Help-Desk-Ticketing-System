<?php
/**
 * Manage Tickets: List, Search, and Filter
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$role = current_user_role();
$userId = (int)$user['id'];
$pdo = get_db();

// 1. Categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// 2. Staff for filter dropdown (Admin & IT Staff only)
$staffList = [];
if ($role !== 'employee') {
    $staffList = $pdo->query("SELECT id, name FROM users WHERE role IN ('admin', 'it_staff') AND status = 'active' ORDER BY name ASC")->fetchAll();
}

// 3. Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$priority = trim($_GET['priority'] ?? 'all');
$category = trim($_GET['category'] ?? 'all');
$assigned = trim($_GET['assigned'] ?? 'all');
$scope = trim($_GET['scope'] ?? ''); // 'my'
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 4. Build Query Conditions
$conditions = [];
$params = [];

// Scoping based on role or explicit param
if ($role === 'employee' || $scope === 'my') {
    $conditions[] = "t.user_id = ?";
    $params[] = $userId;
}

if ($status !== 'all' && !empty($status)) {
    $conditions[] = "t.status = ?";
    $params[] = $status;
}

if ($priority !== 'all' && !empty($priority)) {
    $conditions[] = "t.priority = ?";
    $params[] = $priority;
}

if ($category !== 'all' && !empty($category)) {
    $conditions[] = "t.category_id = ?";
    $params[] = (int)$category;
}

if ($assigned === 'unassigned') {
    $conditions[] = "t.assigned_to IS NULL";
} elseif ($assigned === 'my') {
    $conditions[] = "t.assigned_to = ?";
    $params[] = $userId;
} elseif ($assigned !== 'all' && is_numeric($assigned)) {
    $conditions[] = "t.assigned_to = ?";
    $params[] = (int)$assigned;
}

if (!empty($search)) {
    $conditions[] = "(t.ticket_number LIKE ? OR t.title LIKE ? OR t.description LIKE ? OR req.name LIKE ?)";
    $searchWildcard = "%{$search}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 5. Total matching count for pagination
$countSql = "
    SELECT COUNT(*) 
    FROM tickets t
    LEFT JOIN users req ON t.user_id = req.id
    {$whereClause}
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// 6. Fetch paginated records
$dataSql = "
    SELECT 
        t.*, 
        c.name AS category_name, 
        req.name AS requester_name, 
        req.email AS requester_email, 
        req.department AS requester_dept,
        staff.name AS staff_name
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users req ON t.user_id = req.id
    LEFT JOIN users staff ON t.assigned_to = staff.id
    {$whereClause}
    ORDER BY 
        CASE t.priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END ASC,
        t.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$tickets = $dataStmt->fetchAll();

$page_title = 'Manage Tickets';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-ticket-perforated text-primary me-2"></i>Tickets Directory</h2>
        <p class="text-muted mb-0">
            Showing <?php echo number_format($totalRecords); ?> ticket<?php echo $totalRecords === 1 ? '' : 's'; ?> matching your filters
        </p>
    </div>
    <div>
        <a href="ticket-create.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Submit New Ticket
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="tickets.php" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-md-3">
                <label for="search" class="form-label small text-muted">Search Keyword</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo e($search); ?>" placeholder="ID, Title, Requester...">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small text-muted">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="all">All Statuses</option>
                    <option value="open" <?php echo ($status === 'open') ? 'selected' : ''; ?>>Open</option>
                    <option value="assigned" <?php echo ($status === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                    <option value="in_progress" <?php echo ($status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo ($status === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo ($status === 'closed') ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="col-6 col-md-2">
                <label for="priority" class="form-label small text-muted">Priority</label>
                <select class="form-select form-select-sm" id="priority" name="priority">
                    <option value="all">All Priorities</option>
                    <option value="urgent" <?php echo ($priority === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                    <option value="high" <?php echo ($priority === 'high') ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo ($priority === 'medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo ($priority === 'low') ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-6 col-md-2">
                <label for="category" class="form-label small text-muted">Category</label>
                <select class="form-select form-select-sm" id="category" name="category">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo e($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Staff Assignment Filter (Admin/Staff only) -->
            <?php if ($role !== 'employee'): ?>
                <div class="col-6 col-md-2">
                    <label for="assigned" class="form-label small text-muted">Assigned To</label>
                    <select class="form-select form-select-sm" id="assigned" name="assigned">
                        <option value="all">Everyone</option>
                        <option value="unassigned" <?php echo ($assigned === 'unassigned') ? 'selected' : ''; ?>>Unassigned Only</option>
                        <option value="my" <?php echo ($assigned === 'my') ? 'selected' : ''; ?>>Assigned to Me</option>
                        <?php foreach ($staffList as $st): ?>
                            <option value="<?php echo $st['id']; ?>" <?php echo ($assigned == $st['id']) ? 'selected' : ''; ?>>
                                <?php echo e($st['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="col-md-<?php echo ($role !== 'employee') ? '1' : '3'; ?> d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100" title="Apply Filter">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="tickets.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-filter-circle text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No tickets match your filter criteria</h5>
                <p class="text-muted small">Try broadening your search or clear selected filters.</p>
                <a href="tickets.php" class="btn btn-outline-primary btn-sm">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 140px;">Ticket #</th>
                            <th>Subject & Summary</th>
                            <th>Category</th>
                            <?php if ($role !== 'employee'): ?>
                                <th>Requester</th>
                            <?php endif; ?>
                            <th>Assigned Staff</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="ticket-detail.php?id=<?php echo $t['id']; ?>" class="fw-bold text-decoration-none text-primary">
                                        #<?php echo e($t['ticket_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <a href="ticket-detail.php?id=<?php echo $t['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo e($t['title']); ?>
                                        </a>
                                        <?php if (!empty($t['attachment'])): ?>
                                            <i class="bi bi-paperclip text-muted ms-1" title="Has attachment"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted text-truncate" style="max-width: 320px;">
                                        <?php echo e(mb_strimwidth($t['description'], 0, 75, '...')); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?php echo e($t['category_name'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <?php if ($role !== 'employee'): ?>
                                    <td>
                                        <div class="small fw-semibold"><?php echo e($t['requester_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?php echo e($t['requester_dept'] ?? $t['requester_email'] ?? ''); ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if (!empty($t['staff_name'])): ?>
                                        <span class="small fw-semibold text-primary">
                                            <i class="bi bi-person-check me-1"></i><?php echo e($t['staff_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-exclamation-circle me-1"></i>Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo render_priority_badge($t['priority']); ?></td>
                                <td><?php echo render_status_badge($t['status']); ?></td>
                                <td class="small text-muted" title="<?php echo e($t['created_at']); ?>">
                                    <?php echo time_ago($t['created_at']); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="ticket-detail.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                    <span class="small text-muted">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            // Preserve current GET params for pagination links
                            $queryArgs = $_GET;
                            ?>
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <?php $queryArgs['page'] = $page - 1; ?>
                                <a class="page-link" href="?<?php echo http_build_query($queryArgs); ?>">Previous</a>
                            </li>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <?php $queryArgs['page'] = $i; ?>
                                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query($queryArgs); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <?php $queryArgs['page'] = $page + 1; ?>
                                <a class="page-link" href="?<?php echo http_build_query($queryArgs); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
