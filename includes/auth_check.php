<?php
// includes/auth_check.php - Session and Authorization guards
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'fullname' => $_SESSION['fullname'] ?? '',
        'role' => $_SESSION['role'] ?? 'user',
        'district_id' => $_SESSION['district_id'] ?? null,
        'district_name' => $_SESSION['district_name'] ?? '',
        'organization_id' => $_SESSION['organization_id'] ?? null,
        'organization_name' => $_SESSION['organization_name'] ?? '',
        'phone' => $_SESSION['phone'] ?? ''
    ];
}

function require_login($requiredRole = null) {
    global $pdo;
    
    if (!is_logged_in()) {
        header("Location: index.php?msg=need_login");
        exit;
    }

    // Verify user is still approved and active in database
    $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'approved') {
        session_destroy();
        header("Location: index.php?msg=account_inactive");
        exit;
    }

    // Update session role if changed in DB
    $_SESSION['role'] = $user['role'];

    if ($requiredRole === 'superadmin' && $user['role'] !== 'superadmin') {
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะ Super Admin เท่านั้น)</p><p><a href='dashboard.php'>กลับไปยังหน้าหลัก</a></p>");
    }

    if ($requiredRole === 'admin' && !in_array($user['role'], ['admin', 'superadmin'])) {
        http_response_code(403);
        die("<h1>403 Forbidden</h1><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะ Admin หรือ Super Admin เท่านั้น)</p><p><a href='dashboard.php'>กลับไปยังหน้าหลัก</a></p>");
    }
}
