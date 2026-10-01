<?php
// api/auth.php - Authentication API (Login, Register, Logout, Profile update)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to send json response
function json_resp($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'check_username':
        $username = trim($_GET['username'] ?? '');
        if (empty($username)) {
            json_resp('error', 'กรุณาระบุชื่อผู้ใช้งาน');
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            json_resp('exists', 'ชื่อผู้ใช้งานนี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น');
        } else {
            json_resp('available', 'ชื่อผู้ใช้งานนี้สามารถใช้ได้');
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_resp('error', 'Method not allowed');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $district_id = intval($_POST['district_id'] ?? 0);
        $organization_id = intval($_POST['organization_id'] ?? 0);
        $custom_org = trim($_POST['custom_org'] ?? '');

        // Validations
        if (empty($username) || empty($password) || empty($fullname) || empty($phone) || $district_id <= 0) {
            json_resp('error', 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
        }

        if (mb_strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_\.\-]+$/', $username)) {
            json_resp('error', 'ชื่อผู้ใช้งานต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข จุด หรือขีดล่าง ความยาวอย่างน้อย 3 ตัวอักษร');
        }

        if (mb_strlen($password) < 6) {
            json_resp('error', 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }

        if ($password !== $confirm_password) {
            json_resp('error', 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        }

        // Clean phone number
        $cleanPhone = preg_replace('/[^0-9\-]/', '', $phone);
        if (strlen(str_replace('-', '', $cleanPhone)) < 9) {
            json_resp('error', 'กรุณากรอกเบอร์โทรศัพท์ที่ถูกต้อง (อย่างน้อย 9-10 หลัก)');
        }

        // Check duplicate username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            json_resp('error', 'ชื่อผู้ใช้งานนี้มีผู้ใช้งานแล้ว โปรดเลือกชื่ออื่น');
        }

        // Get district name
        $stmtD = $pdo->prepare("SELECT name_th FROM districts WHERE id = ?");
        $stmtD->execute([$district_id]);
        $districtRow = $stmtD->fetch();
        if (!$districtRow) {
            json_resp('error', 'อำเภอที่เลือกไม่ถูกต้อง');
        }
        $district_name = $districtRow['name_th'];

        // Get organization name
        $organization_name = '';
        if ($organization_id > 0) {
            $stmtO = $pdo->prepare("SELECT name_th FROM organizations WHERE id = ? AND district_id = ?");
            $stmtO->execute([$organization_id, $district_id]);
            $orgRow = $stmtO->fetch();
            if ($orgRow) {
                $organization_name = $orgRow['name_th'];
            }
        }
        
        if (empty($organization_name) && !empty($custom_org)) {
            $organization_name = $custom_org;
            $organization_id = null;
        }

        if (empty($organization_name)) {
            json_resp('error', 'กรุณาเลือกหรือระบุหน่วยงานในสังกัด');
        }

        // Insert new user with 'pending' status
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO users (
                    username, password, fullname, phone, 
                    district_id, district_name, organization_id, organization_name, 
                    role, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    'user', 'pending', NOW()
                )
            ");
            $stmtInsert->execute([
                $username,
                $hashed_password,
                $fullname,
                $phone,
                $district_id,
                $district_name,
                $organization_id ?: null,
                $organization_name
            ]);

            $newUserId = $pdo->lastInsertId();
            log_audit($pdo, $newUserId, 'user_registered', "ลงทะเบียนผู้ใช้ใหม่: {$username} ({$fullname})");

            json_resp('success', 'ลงทะเบียนสำเร็จเรียบร้อยแล้ว!', [
                'status_type' => 'pending',
                'notice' => 'บัญชีของท่านอยู่ระหว่างรอการอนุมัติสิทธิ์เข้าใช้งานจากผู้ดูแลระบบระดับสูง (Super Admin) กรุณาติดต่อผู้ดูแลระบบเพื่อเปิดใช้งาน หรือรอรับการยืนยัน'
            ]);
        } catch (PDOException $e) {
            json_resp('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_resp('error', 'Method not allowed');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            json_resp('error', 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน');
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            log_audit($pdo, null, 'login_failed', "เข้าสู่ระบบไม่สำเร็จ: username={$username}");
            json_resp('error', 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง');
        }

        // Check account status
        if ($user['status'] === 'pending') {
            log_audit($pdo, $user['id'], 'login_denied_pending', "พยายามเข้าสู่ระบบแต่สถานะยังรออนุมัติ: {$username}");
            json_resp('pending', 'บัญชีของท่านยังไม่สามารถเข้าใช้งานได้ เนื่องจากอยู่ระหว่างรอการอนุมัติสิทธิ์จากผู้ดูแลระบบระดับสูง (Super Admin)', [
                'user_info' => [
                    'fullname' => $user['fullname'],
                    'district_name' => $user['district_name'],
                    'organization_name' => $user['organization_name'],
                    'registered_at' => $user['created_at']
                ]
            ]);
        }

        if ($user['status'] === 'rejected') {
            log_audit($pdo, $user['id'], 'login_denied_rejected', "พยายามเข้าสู่ระบบแต่ถูกปฏิเสธ: {$username}");
            $reason = !empty($user['rejection_reason']) ? 'เหตุผล: ' . $user['rejection_reason'] : 'กรุณาติดต่อผู้ดูแลระบบส่วนกลางเพื่อตรวจสอบสิทธิ์';
            json_resp('rejected', 'บัญชีของท่านไม่ผ่านการอนุมัติการใช้งาน (' . $reason . ')');
        }

        if ($user['status'] === 'suspended') {
            log_audit($pdo, $user['id'], 'login_denied_suspended', "พยายามเข้าสู่ระบบแต่ถูกระงับ: {$username}");
            json_resp('suspended', 'บัญชีผู้ใช้นี้ถูกระงับการใช้งานชั่วคราว กรุณาติดต่อผู้ดูแลระบบจังหวัด');
        }

        // Approved account -> setup session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['district_id'] = $user['district_id'];
        $_SESSION['district_name'] = $user['district_name'];
        $_SESSION['organization_id'] = $user['organization_id'];
        $_SESSION['organization_name'] = $user['organization_name'];
        $_SESSION['phone'] = $user['phone'];

        log_audit($pdo, $user['id'], 'login_success', "เข้าสู่ระบบสำเร็จ: {$username} (สิทธิ์: {$user['role']})");

        $redirect = ($user['role'] === 'superadmin') ? 'admin.php' : 'dashboard.php';
        json_resp('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ ' . $user['fullname'], [
            'redirect' => $redirect,
            'role' => $user['role'],
            'fullname' => $user['fullname']
        ]);
        break;

    case 'logout':
        if (isset($_SESSION['user_id'])) {
            log_audit($pdo, $_SESSION['user_id'], 'logout', "ออกจากระบบ: {$_SESSION['username']}");
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (isset($_GET['ajax'])) {
            json_resp('success', 'ออกจากระบบเรียบร้อย');
        }
        header("Location: ../index.php?msg=logged_out");
        exit;

    default:
        json_resp('error', 'คำสั่งไม่ถูกต้อง (Invalid action)');
}
