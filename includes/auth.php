<?php
// ============================================================
// Authentication Helpers
// ============================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Check if a user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require the user to be logged in; redirect to login otherwise.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // If user must change password, force them to the change_password page
    if (!empty($_SESSION['must_change_password'])) {
        $current = basename($_SERVER['PHP_SELF']);
        if ($current !== 'change_password.php' && $current !== 'logout.php') {
            header('Location: ' . BASE_URL . '/change_password.php');
            exit;
        }
    }
}

/**
 * Require the current user to be an admin.
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== ROLE_ADMIN) {
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
    }
}

/**
 * Get the currently logged-in user array from session.
 */
function getCurrentUser() {
    return [
        'id'                  => $_SESSION['user_id']   ?? null,
        'username'            => $_SESSION['username']  ?? '',
        'full_name'           => $_SESSION['full_name'] ?? '',
        'role'                => $_SESSION['user_role'] ?? '',
        'profile_picture'     => $_SESSION['profile_picture'] ?? null,
        'must_change_password'=> $_SESSION['must_change_password'] ?? 0,
    ];
}

/**
 * Log a user in by setting session variables.
 */
function loginUser($user) {
    session_regenerate_id(true);
    $_SESSION['user_id']             = $user['id'];
    $_SESSION['username']            = $user['username'];
    $_SESSION['full_name']           = $user['full_name'];
    $_SESSION['user_role']           = $user['role'];
    $_SESSION['profile_picture']     = $user['profile_picture'] ?? null;
    $_SESSION['must_change_password']= (int)$user['must_change_password'];
}

/**
 * Log the current user out.
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Store a flash message in the session.
 * @param string $type  'success'|'error'|'warning'|'info'
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message.
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
