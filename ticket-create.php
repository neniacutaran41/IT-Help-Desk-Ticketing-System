<?php
/**
 * Submit New Ticket Form
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$role = current_user_role();
$pdo = get_db();

// Fetch active categories
$stmt = $pdo->query("SELECT id, name, description FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch IT Staff list if Admin (for instant assignment)
$itStaffList = [];
if (has_role('admin')) {
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role IN ('admin', 'it_staff') AND status = 'active' ORDER BY name ASC");
    $itStaffList = $stmt->fetchAll();
}

$error = '';
$title = '';
$category_id = '';
$priority = 'medium';
$description = '';
$assigned_to = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $priority = strtolower(trim($_POST['priority'] ?? 'medium'));
        $description = trim($_POST['description'] ?? '');
        
        if (has_role('admin') && !empty($_POST['assigned_to'])) {
            $assigned_to = (int)$_POST['assigned_to'];
        }

        // Validate
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $validPriorities, true)) {
            $priority = 'medium';
        }

        if (empty($title)) {
            $error = 'Please provide a clear ticket title / subject.';
        } elseif (empty($description)) {
            $error = 'Please describe the technical issue in detail.';
        } else {
            // Handle optional file attachment
            $uploadResult = null;
            $uploadError = '';
            if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = handle_file_upload($_FILES['attachment'], $uploadError);
                if ($uploadError) {
                    $error = $uploadError;
                }
            }

            if (empty($error)) {
                try {
                    $ticketNumber = generate_ticket_number($pdo);
                    $initialStatus = $assigned_to ? 'assigned' : 'open';

                    $stmt = $pdo->prepare("
                        INSERT INTO tickets (ticket_number, user_id, category_id, assigned_to, title, description, priority, status, attachment, attachment_original_name, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $storedAttachment = $uploadResult['stored_name'] ?? null;
                    $origAttachment = $uploadResult['original_name'] ?? null;

                    $stmt->execute([
                        $ticketNumber,
                        (int)$user['id'],
                        $category_id,
                        $assigned_to,
                        $title,
                        $description,
                        $priority,
                        $initialStatus,
                        $storedAttachment,
                        $origAttachment
                    ]);

                    $ticketId = (int)$pdo->lastInsertId();

                    // Log history
                    log_ticket_history(
                        $pdo,
                        $ticketId,
                        (int)$user['id'],
                        'ticket_created',
                        null,
                        $initialStatus,
                        'Ticket created with priority: ' . ucfirst($priority)
                    );

                    if ($assigned_to) {
                        // Log assignment history
                        $staffNameStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                        $staffNameStmt->execute([$assigned_to]);
                        $assignedName = $staffNameStmt->fetchColumn() ?: 'Staff #' . $assigned_to;

                        log_ticket_history(
                            $pdo,
                            $ticketId,
                            (int)$user['id'],
                            'assigned',
                            'Unassigned',
                            $assignedName,
                            'Assigned during ticket creation'
                        );
                    }

                    set_flash('success', "Ticket {$ticketNumber} has been successfully submitted!");
                    header("Location: ticket-detail.php?id={$ticketId}");
                    exit;

                } catch (Exception $e) {
                    $error = 'Failed to submit ticket: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Submit New Ticket';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="tickets.php" class="text-decoration-none">Tickets</a></li>
                <li class="breadcrumb-item active" aria-current="page">Submit Ticket</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>Submit IT Support Request</h4>
                    <p class="text-muted small mb-0">Describe your issue or technical request and our IT team will assist you.</p>
                </div>
                <a href="tickets.php" class="btn btn-outline-secondary btn-sm">
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

                <form method="POST" action="ticket-create.php" enctype="multipart/form-data" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Subject / Issue Summary <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo e($title); ?>" placeholder="e.g. Cannot access Outlook email or VPN connection keeps failing" required autofocus>
                        <div class="form-text">A concise summary of the problem you are experiencing.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low" <?php echo ($priority === 'low') ? 'selected' : ''; ?>>Low - General question / Non-urgent inquiry</option>
                                <option value="medium" <?php echo ($priority === 'medium') ? 'selected' : ''; ?>>Medium - Normal daily work affected but workaround available</option>
                                <option value="high" <?php echo ($priority === 'high') ? 'selected' : ''; ?>>High - Significant impact on job performance</option>
                                <option value="urgent" <?php echo ($priority === 'urgent') ? 'selected' : ''; ?>>Urgent - Complete work blockage / Critical system down</option>
                            </select>
                        </div>
                    </div>

                    <?php if (has_role('admin') && !empty($itStaffList)): ?>
                        <div class="mb-3 p-3 bg-light rounded border">
                            <label for="assigned_to" class="form-label fw-bold text-primary">
                                <i class="bi bi-person-check-fill me-1"></i> Direct Assign to Support Staff (Admin Option)
                            </label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">-- Leave Unassigned (Open Queue) --</option>
                                <?php foreach ($itStaffList as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>" <?php echo ($assigned_to == $staff['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($staff['name']); ?> (<?php echo e($staff['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="description" class="form-label">Detailed Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="6" placeholder="Provide step-by-step details:
- What were you trying to do?
- What error message or behavior occurred?
- Any troubleshooting steps you have already attempted?" required><?php echo e($description); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="form-label">Attachment (Optional)</label>
                        <input class="form-control" type="file" id="attachment" name="attachment">
                        <div class="form-text">
                            Allowed formats: <code>jpg, png, gif, pdf, doc, docx, txt, zip</code>. Max file size: <strong>5 MB</strong>.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="tickets.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send-fill me-1"></i> Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
