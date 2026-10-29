<?php
/**
 * Global Helper Functions & Utilities
 * DeskHero IT Help Desk System
 */

// Define uploads directory absolute path
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
}

/**
 * Safe HTML escaping shorthand
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash alert message in session
 *
 * @param string $type success|danger|warning|info
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Retrieve and clear all flash alert messages
 *
 * @return array
 */
function get_flash(): array {
    $flashes = [];

    // Support structured flash array
    if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
        $flashes = $_SESSION['flash'];
        unset($_SESSION['flash']);
    }

    // Support single flash keys (e.g. from logout)
    foreach (['success', 'danger', 'warning', 'info'] as $type) {
        $key = 'flash_' . $type;
        if (!empty($_SESSION[$key])) {
            $flashes[$type][] = $_SESSION[$key];
            unset($_SESSION[$key]);
        }
    }

    return $flashes;
}

/**
 * Render Bootstrap badge for ticket status
 *
 * @param string $status
 * @return string HTML
 */
function render_status_badge(string $status): string {
    $status = strtolower($status);
    switch ($status) {
        case 'open':
            return '<span class="badge badge-glow badge-glow-open"><span class="glow-dot"></span><i class="bi bi-envelope-open me-1"></i>Open</span>';
        case 'assigned':
            return '<span class="badge badge-glow badge-glow-assigned"><span class="glow-dot"></span><i class="bi bi-person-check me-1"></i>Assigned</span>';
        case 'in_progress':
            return '<span class="badge badge-glow badge-glow-progress"><span class="glow-dot"></span><i class="bi bi-gear-wide-connected me-1"></i>In Progress</span>';
        case 'resolved':
            return '<span class="badge badge-glow badge-glow-resolved"><span class="glow-dot"></span><i class="bi bi-check2-circle me-1"></i>Resolved</span>';
        case 'closed':
            return '<span class="badge badge-glow badge-glow-closed"><i class="bi bi-archive me-1"></i>Closed</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
    }
}

/**
 * Render Bootstrap badge for ticket priority
 *
 * @param string $priority
 * @return string HTML
 */
function render_priority_badge(string $priority): string {
    $priority = strtolower($priority);
    switch ($priority) {
        case 'low':
            return '<span class="badge badge-priority-glow badge-priority-low"><i class="bi bi-arrow-down me-1"></i>Low</span>';
        case 'medium':
            return '<span class="badge badge-priority-glow badge-priority-medium"><i class="bi bi-dash me-1"></i>Medium</span>';
        case 'high':
            return '<span class="badge badge-priority-glow badge-priority-high"><i class="bi bi-arrow-up me-1"></i>High</span>';
        case 'urgent':
            return '<span class="badge badge-priority-glow badge-priority-urgent pulse-glow-urgent"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>';
        default:
            return '<span class="badge bg-light text-dark border">' . e(ucfirst($priority)) . '</span>';
    }
}

/**
 * Render badge for user role
 *
 * @param string $role
 * @return string HTML
 */
function render_role_badge(string $role): string {
    $role = strtolower($role);
    switch ($role) {
        case 'admin':
            return '<span class="badge badge-role-glow badge-role-admin"><i class="bi bi-shield-check me-1"></i>Admin</span>';
        case 'it_staff':
            return '<span class="badge badge-role-glow badge-role-staff"><i class="bi bi-headset me-1"></i>IT Staff</span>';
        case 'employee':
        default:
            return '<span class="badge badge-role-glow badge-role-user"><i class="bi bi-person me-1"></i>Regular User</span>';
    }
}

/**
 * Format timestamp into human-readable relative string
 *
 * @param string|int|null $datetime
 * @return string
 */
function time_ago($datetime): string {
    if (empty($datetime)) {
        return 'Never';
    }

    $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    if (!$timestamp) {
        return 'Unknown';
    }

    $diff = time() - $timestamp;

    if ($diff < 0) {
        return 'Just now';
    }

    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 172800) {
        return 'Yesterday ' . date('g:i A', $timestamp);
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . ' days ago';
    }

    // Default formatted date
    return date('M j, Y g:i A', $timestamp);
}

/**
 * Generate a unique sequential ticket number
 * Format: TCK-YYYY-XXXX (e.g., TCK-2026-0001)
 *
 * @param PDO $pdo
 * @return string
 */
function generate_ticket_number(PDO $pdo): string {
    $year = date('Y');
    $prefix = "TCK-{$year}-";

    // Find the highest sequence number for the current year
    $stmt = $pdo->prepare("
        SELECT ticket_number 
        FROM tickets 
        WHERE ticket_number LIKE ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute(["{$prefix}%"]);
    $latest = $stmt->fetchColumn();

    $nextNum = 1;
    if ($latest && preg_match('/^TCK-\d{4}-(\d+)$/', $latest, $matches)) {
        $nextNum = (int)$matches[1] + 1;
    }

    // Format with 4 digits padding
    $ticketNumber = sprintf('TCK-%s-%04d', $year, $nextNum);

    // Double check uniqueness loop just in case
    $checkStmt = $pdo->prepare("SELECT id FROM tickets WHERE ticket_number = ? LIMIT 1");
    while (true) {
        $checkStmt->execute([$ticketNumber]);
        if (!$checkStmt->fetch()) {
            break;
        }
        $nextNum++;
        $ticketNumber = sprintf('TCK-%s-%04d', $year, $nextNum);
    }

    return $ticketNumber;
}

/**
 * Fetch ticket count statistics for dashboard
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $role
 * @return array
 */
function get_ticket_counts(PDO $pdo, int $userId, string $role): array {
    $counts = [
        'total'          => 0,
        'open'           => 0,
        'assigned'       => 0,
        'in_progress'    => 0,
        'resolved'       => 0,
        'closed'         => 0,
        'urgent_pending' => 0
    ];

    $where = "";
    $params = [];

    if ($role === 'employee') {
        $where = " WHERE user_id = ?";
        $params[] = $userId;
    }

    // Overall by status
    $sql = "SELECT status, COUNT(*) as cnt FROM tickets {$where} GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $st = $row['status'];
        $cnt = (int)$row['cnt'];
        if (isset($counts[$st])) {
            $counts[$st] = $cnt;
        }
        $counts['total'] += $cnt;
    }

    // Urgent pending count
    $urgentSql = "
        SELECT COUNT(*) 
        FROM tickets 
        WHERE priority = 'urgent' 
          AND status IN ('open', 'assigned', 'in_progress')
    ";
    if ($role === 'employee') {
        $urgentSql .= " AND user_id = ?";
        $urgentStmt = $pdo->prepare($urgentSql);
        $urgentStmt->execute([$userId]);
    } else {
        $urgentStmt = $pdo->query($urgentSql);
    }
    $counts['urgent_pending'] = (int)$urgentStmt->fetchColumn();

    return $counts;
}

/**
 * Record an entry into the ticket_history audit trail
 *
 * @param PDO $pdo
 * @param int $ticketId
 * @param int $userId
 * @param string $action
 * @param string|null $oldValue
 * @param string|null $newValue
 * @param string|null $comment
 * @return bool
 */
function log_ticket_history(
    PDO $pdo,
    int $ticketId,
    int $userId,
    string $action,
    ?string $oldValue = null,
    ?string $newValue = null,
    ?string $comment = null
): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO ticket_history (ticket_id, user_id, action, old_value, new_value, comment, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $ticketId,
            $userId,
            $action,
            $oldValue,
            $newValue,
            $comment
        ]);
    } catch (Exception $e) {
        error_log("Failed to log ticket history: " . $e->getMessage());
        return false;
    }
}

/**
 * Handle secure file upload for tickets and replies
 *
 * @param array $file $_FILES['input_name']
 * @param string &$error Output error message if any
 * @return array|null ['stored_name' => ..., 'original_name' => ...]
 */
function handle_file_upload(array $file, string &$error): ?array {
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed with error code: ' . $file['error'];
        return null;
    }

    // Maximum file size: 10MB
    $maxBytes = 10 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $error = 'File size exceeds maximum allowed limit of 10MB.';
        return null;
    }

    // Allowed extensions
    $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'log', 'zip'
    ];

    $origName = basename($file['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        $error = 'Invalid file format. Allowed extensions: ' . implode(', ', $allowedExtensions);
        return null;
    }

    // Ensure uploads directory exists and is writable
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!is_writable(UPLOAD_DIR)) {
        $error = 'Uploads directory is not writable. Please check permissions.';
        return null;
    }

    // Generate unique stored filename
    $storedName = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(6)), $ext);
    $targetPath = UPLOAD_DIR . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Could not move uploaded file to destination.';
        return null;
    }

    return [
        'stored_name'   => $storedName,
        'original_name' => $origName
    ];
}
