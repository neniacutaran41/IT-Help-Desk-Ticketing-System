<?php
/**
 * Authentication, Session & Access Control Guard
 * DeskHero IT Help Desk System
 */

// Start session securely if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    $cookieParams = [
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

/**
 * Check if the user is currently logged in
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user details
 *
 * @return array
 */
function current_user(): array {
    if (!is_logged_in()) {
        return [];
    }

    return [
        'id'         => (int)($_SESSION['user_id'] ?? 0),
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'employee',
        'department' => $_SESSION['user_department'] ?? '',
    ];
}

/**
 * Get current user role
 *
 * @return string
 */
function current_user_role(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Check if the current user has a specific role or one of given roles
 *
 * @param string|array $roles
 * @return bool
 */
function has_role($roles): bool {
    if (!is_logged_in()) {
        return false;
    }

    $currentRole = current_user_role();

    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }

    return $currentRole === $roles;
}

/**
 * Require the user to be logged in, otherwise redirect to login page
 *
 * @return void
 */
function require_login(): void {
    if (!is_logged_in()) {
        if (function_exists('set_flash')) {
            set_flash('danger', 'Please log in to access this page.');
        }
        header('Location: login.php');
        exit;
    }
}

/**
 * Require the user to have a specific role or one of given roles
 *
 * @param string|array $roles
 * @return void
 */
function require_role($roles): void {
    require_login();

    if (!has_role($roles)) {
        if (function_exists('set_flash')) {
            set_flash('danger', 'Access denied: You do not have permission to view that page.');
        }
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Generate or retrieve CSRF token for the session
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
