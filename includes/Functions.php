<?php
// ============================================================
// includes/functions.php  – global helper functions
// Include this AFTER db.php (session already started there)
// ============================================================

// ── Role helpers ─────────────────────────────────────────────

/** Returns true if the logged-in user is a seller OR admin */
function isSeller(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['seller', 'admin']);
}

/** Returns true if the logged-in user is an admin */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ── Auth guard ────────────────────────────────────────────────

/**
 * Redirect to $loginUrl if the user is not logged in.
 * Call at the top of any protected page.
 */
function requireLogin(string $loginUrl = 'auth/login.php'): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: $loginUrl");
        exit;
    }
}

// ── Flash messages ────────────────────────────────────────────

/** Store a one-time flash message in the session */
function setFlash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

/** Read and clear a flash message; returns '' if not set */
function getFlash(string $key): string {
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}