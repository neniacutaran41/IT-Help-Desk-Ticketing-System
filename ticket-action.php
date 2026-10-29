<?php
/**
 * Ticket Actions Handler (Status Updates, Staff Assignment, Replies, and Deletion)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = current_user();
$role = current_user_role();
$userId = (int)$user['id'];
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tickets.php');
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'tickets.php'));
    exit;
}

$action = trim($_POST['action'] ?? '');
$ticket_id = (int)($_POST['ticket_id'] ?? 0);

if (!$ticket_id) {
    set_flash('danger', 'Ticket ID is missing.');
    header('Location: tickets.php');
    exit;
}

// Fetch ticket to verify access
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    set_flash('danger', 'Ticket not found.');
    header('Location: tickets.php');
    exit;
}

// Check authorization: Employee can only interact with their own tickets
if ($role === 'employee' && (int)$ticket['user_id'] !== $userId) {
    set_flash('danger', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

// ----------------------------------------------------
// 1. UPDATE STATUS
// ----------------------------------------------------
if ($action === 'update_status') {
    $newStatus = trim($_POST['status'] ?? '');
    $validStatuses = ['open', 'assigned', 'in_progress', 'resolved', 'closed'];

    if (!in_array($newStatus, $validStatuses, true)) {
        set_flash('danger', 'Invalid status selected.');
        header("Location: ticket-detail.php?id={$ticket_id}");
        exit;
    }

    // Role restrictions: Employees can only set 'resolved' or 'closed' to confirm fix
    if ($role === 'employee' && !in_array($newStatus, ['resolved', 'closed', 'open'], true)) {
        set_flash('danger', 'Employees can only reopen, resolve, or close their tickets.');
        header("Location: ticket-detail.php?id={$ticket_id}");
        exit;
    }

    $oldStatus = $ticket['status'];
    if ($newStatus !== $oldStatus) {
        $resolvedAt = ($newStatus === 'resolved') ? date('Y-m-d H:i:s') : $ticket['resolved_at'];
        $closedAt = ($newStatus === 'closed') ? date('Y-m-d H:i:s') : $ticket['closed_at'];

        $updateStmt = $pdo->prepare("
            UPDATE tickets 
            SET status = ?, resolved_at = ?, closed_at = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $resolvedAt, $closedAt, $ticket_id]);

        $statusComment = trim($_POST['status_comment'] ?? '');
        log_ticket_history(
            $pdo,
            $ticket_id,
            $userId,
            'status_changed',
            $oldStatus,
            $newStatus,
            $statusComment ?: ("Status changed to " . ucwords(str_replace('_', ' ', $newStatus)))
        );

        set_flash('success', "Ticket status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . ".");
    }

    header("Location: ticket-detail.php?id={$ticket_id}");
    exit;
}

// ----------------------------------------------------
// 2. ASSIGN TICKET (Admin & IT Staff)
// ----------------------------------------------------
if ($action === 'assign_ticket') {
    // Only Admin or IT Staff can assign/reassign tickets
    if ($role === 'employee') {
        set_flash('danger', 'You do not have permission to assign tickets.');
        header("Location: ticket-detail.php?id={$ticket_id}");
        exit;
    }

    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

    // Get old staff name
    $oldStaffName = 'Unassigned';
    if ($ticket['assigned_to']) {
        $s = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $s->execute([$ticket['assigned_to']]);
        $oldStaffName = $s->fetchColumn() ?: 'Staff #' . $ticket['assigned_to'];
    }

    $newStaffName = 'Unassigned';
    if ($assigned_to) {
        $s = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role IN ('admin', 'it_staff')");
        $s->execute([$assigned_to]);
        $newStaffName = $s->fetchColumn();
        if (!$newStaffName) {
            set_flash('danger', 'Selected staff member does not exist or is not authorized.');
            header("Location: ticket-detail.php?id={$ticket_id}");
            exit;
        }
    }

    // Determine new status if ticket was 'open' and is now assigned
    $newStatus = $ticket['status'];
    if ($assigned_to && $ticket['status'] === 'open') {
        $newStatus = 'assigned';
    } elseif (!$assigned_to && $ticket['status'] === 'assigned') {
        $newStatus = 'open';
    }

    $updateStmt = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$assigned_to, $newStatus, $ticket_id]);

    log_ticket_history(
        $pdo,
        $ticket_id,
        $userId,
        'assigned',
        $oldStaffName,
        $newStaffName,
        "Assigned by " . $user['name']
    );

    set_flash('success', "Ticket assignment updated to {$newStaffName}.");
    header("Location: ticket-detail.php?id={$ticket_id}");
    exit;
}

// ----------------------------------------------------
// 3. ADD REPLY / INTERNAL NOTE
// ----------------------------------------------------
if ($action === 'add_reply') {
    $message = trim($_POST['message'] ?? '');
    $is_internal = (!empty($_POST['is_internal']) && $role !== 'employee') ? 1 : 0;

    if (empty($message)) {
        set_flash('danger', 'Please enter a message or note before submitting.');
        header("Location: ticket-detail.php?id={$ticket_id}");
        exit;
    }

    // Handle attachment
    $uploadResult = null;
    $uploadError = '';
    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handle_file_upload($_FILES['attachment'], $uploadError);
        if ($uploadError) {
            set_flash('danger', $uploadError);
            header("Location: ticket-detail.php?id={$ticket_id}");
            exit;
        }
    }

    $storedAttachment = $uploadResult['stored_name'] ?? null;
    $origAttachment = $uploadResult['original_name'] ?? null;

    $replyStmt = $pdo->prepare("
        INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal, attachment, attachment_original_name, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $replyStmt->execute([$ticket_id, $userId, $message, $is_internal, $storedAttachment, $origAttachment]);

    // Update ticket's updated_at timestamp
    $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

    $logType = $is_internal ? 'internal_note' : 'reply_added';
    $logNote = $is_internal ? 'Internal staff note added' : 'Public reply posted';
    log_ticket_history($pdo, $ticket_id, $userId, $logType, null, null, $logNote);

    set_flash('success', $is_internal ? 'Internal note added.' : 'Reply posted successfully.');
    header("Location: ticket-detail.php?id={$ticket_id}#replies");
    exit;
}

// ----------------------------------------------------
// 4. DELETE TICKET (Admin Only)
// ----------------------------------------------------
if ($action === 'delete_ticket') {
    require_role('admin');

    // Remove file if exists
    if (!empty($ticket['attachment'])) {
        $filePath = UPLOAD_DIR . $ticket['attachment'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Replies attachments cleanup
    $repliesStmt = $pdo->prepare("SELECT attachment FROM ticket_replies WHERE ticket_id = ? AND attachment IS NOT NULL");
    $repliesStmt->execute([$ticket_id]);
    while ($row = $repliesStmt->fetch()) {
        $f = UPLOAD_DIR . $row['attachment'];
        if (file_exists($f)) {
            @unlink($f);
        }
    }

    // Delete ticket (Foreign keys will cascade replies and history)
    $del = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $del->execute([$ticket_id]);

    set_flash('success', "Ticket #{$ticket['ticket_number']} has been permanently deleted.");
    header('Location: tickets.php');
    exit;
}

// Fallback
header("Location: ticket-detail.php?id={$ticket_id}");
exit;
