<?php
// api/admin_actions.php - Superadmin User Management CRUD Actions
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// Auth & Superadmin check
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['status' => 'forbidden', 'message' => 'ไม่มีสิทธิ์เข้าถึง (สำหรับ Super Admin เท่านั้น)'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentAdminId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'stats':
        try {
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $pendingUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
            $approvedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
            $rejectedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'rejected'")->fetchColumn();
            $districtsCount = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
            $orgsCount = $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();

            resp('success', 'Stats loaded', [
                'stats' => [
                    'total' => (int)$totalUsers,
                    'pending' => (int)$pendingUsers,
                    'approved' => (int)$approvedUsers,
                    'rejected' => (int)$rejectedUsers,
                    'districts' => (int)$districtsCount,
                    'orgs' => (int)$orgsCount
                ]
            ]);
        } catch (PDOException $e) {
            resp('error', 'Database error: ' . $e->getMessage());
        }
        break;

    case 'list_users':
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $district_id = intval($_GET['district_id'] ?? 0);
        $role = trim($_GET['role'] ?? '');

        $sql = "SELECT id, username, fullname, phone, district_id, district_name, organization_id, organization_name, role, status, rejection_reason, approved_by, approved_at, created_at, updated_at FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (fullname LIKE ? OR username LIKE ? OR phone LIKE ? OR organization_name LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        if ($district_id > 0) {
            $sql .= " AND district_id = ?";
            $params[] = $district_id;
        }

        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }

        $sql .= " ORDER BY (status = 'pending') DESC, created_at DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            resp('success', 'Users list', [
                'count' => count($users),
                'users' => $users
            ]);
        } catch (PDOException $e) {
            resp('error', 'Error fetching users: ' . $e->getMessage());
        }
        break;

    case 'get_user':
        $userId = intval($_GET['id'] ?? 0);
        if ($userId <= 0) {
            resp('error', 'Invalid User ID');
        }

        $stmt = $pdo->prepare("SELECT id, username, fullname, phone, district_id, district_name, organization_id, organization_name, role, status, rejection_reason, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            resp('error', 'User not found');
        }

        resp('success', 'User found', ['user' => $user]);
        break;

    case 'approve_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Method not allowed');
        $userId = intval($_POST['id'] ?? 0);
        if ($userId <= 0) resp('error', 'Invalid User ID');

        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
            $stmt->execute([$currentAdminId, $userId]);

            // Get user info for audit
            $u = $pdo->query("SELECT username, fullname FROM users WHERE id = {$userId}")->fetch();
            log_audit($pdo, $currentAdminId, 'approve_user', "อนุมัติผู้ใช้งาน ID {$userId}: {$u['username']} ({$u['fullname']})");

            resp('success', "อนุมัติการใช้งานของ {$u['fullname']} เรียบร้อยแล้ว");
        } catch (PDOException $e) {
            resp('error', 'Failed to approve: ' . $e->getMessage());
        }
        break;

    case 'reject_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Method not allowed');
        $userId = intval($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'ข้อมูลไม่ครบถ้วน หรือไม่ตรงกับสังกัดหน่วยงาน');
        if ($userId <= 0) resp('error', 'Invalid User ID');

        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$reason, $currentAdminId, $userId]);

            $u = $pdo->query("SELECT username, fullname FROM users WHERE id = {$userId}")->fetch();
            log_audit($pdo, $currentAdminId, 'reject_user', "ปฏิเสธผู้ใช้งาน ID {$userId}: {$u['username']} เหตุผล: {$reason}");

            resp('success', "ปฏิเสธการใช้งานของ {$u['fullname']} เรียบร้อยแล้ว");
        } catch (PDOException $e) {
            resp('error', 'Failed to reject: ' . $e->getMessage());
        }
        break;

    case 'delete_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Method not allowed');
        $userId = intval($_POST['id'] ?? 0);
        if ($userId <= 0) resp('error', 'Invalid User ID');

        if ($userId === $currentAdminId) {
            resp('error', 'ไม่สามารถลบบัญชีของตนเองได้');
        }

        try {
            $u = $pdo->query("SELECT username, fullname FROM users WHERE id = {$userId}")->fetch();
            if (!$u) resp('error', 'ไม่พบผู้ใช้นี้');

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            log_audit($pdo, $currentAdminId, 'delete_user', "ลบผู้ใช้งาน ID {$userId}: {$u['username']} ({$u['fullname']})");

            resp('success', "ลบข้อมูลผู้ใช้งาน {$u['fullname']} ออกจากระบบแล้ว");
        } catch (PDOException $e) {
            resp('error', 'Failed to delete: ' . $e->getMessage());
        }
        break;

    case 'save_user': // For Add or Edit
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Method not allowed');
        
        $userId = intval($_POST['id'] ?? 0); // 0 = Add, > 0 = Edit
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $district_id = intval($_POST['district_id'] ?? 0);
        $organization_id = intval($_POST['organization_id'] ?? 0);
        $custom_org = trim($_POST['custom_org'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['user', 'admin', 'superadmin']) ? $_POST['role'] : 'user';
        $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected', 'suspended']) ? $_POST['status'] : 'approved';
        $password = $_POST['password'] ?? '';

        if (empty($fullname) || empty($phone) || $district_id <= 0) {
            resp('error', 'กรุณากรอกข้อมูลสำคัญให้ครบถ้วน');
        }

        // District name
        $stmtD = $pdo->prepare("SELECT name_th FROM districts WHERE id = ?");
        $stmtD->execute([$district_id]);
        $districtRow = $stmtD->fetch();
        if (!$districtRow) resp('error', 'อำเภอไม่ถูกต้อง');
        $district_name = $districtRow['name_th'];

        // Organization name
        $organization_name = '';
        if ($organization_id > 0) {
            $stmtO = $pdo->prepare("SELECT name_th FROM organizations WHERE id = ? AND district_id = ?");
            $stmtO->execute([$organization_id, $district_id]);
            $orgRow = $stmtO->fetch();
            if ($orgRow) $organization_name = $orgRow['name_th'];
        }
        if (empty($organization_name) && !empty($custom_org)) {
            $organization_name = $custom_org;
            $organization_id = null;
        }

        if (empty($organization_name)) {
            resp('error', 'กรุณาเลือกหรือระบุหน่วยงาน');
        }

        if ($userId === 0) {
            // INSERT NEW USER
            if (empty($username) || empty($password)) {
                resp('error', 'กรุณาระบุ Username และ Password สำหรับผู้ใช้ใหม่');
            }
            if (strlen($password) < 6) {
                resp('error', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            }

            // Check duplicate
            $stmtC = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtC->execute([$username]);
            if ($stmtC->fetch()) {
                resp('error', 'ชื่อผู้ใช้งานนี้มีในระบบแล้ว');
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $approvedAt = ($status === 'approved') ? date('Y-m-d H:i:s') : null;
            $approvedBy = ($status === 'approved') ? $currentAdminId : null;

            $stmtAdd = $pdo->prepare("
                INSERT INTO users (
                    username, password, fullname, phone, 
                    district_id, district_name, organization_id, organization_name, 
                    role, status, approved_by, approved_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtAdd->execute([
                $username, $hashed, $fullname, $phone,
                $district_id, $district_name, $organization_id ?: null, $organization_name,
                $role, $status, $approvedBy, $approvedAt
            ]);

            $newId = $pdo->lastInsertId();
            log_audit($pdo, $currentAdminId, 'create_user_admin', "สร้างผู้ใช้ใหม่ ID {$newId}: {$username} ({$fullname})");

            resp('success', "เพิ่มผู้ใช้งานใหม่ '{$fullname}' เรียบร้อยแล้ว");
        } else {
            // UPDATE EXISTING USER
            // Update base fields
            $sql = "UPDATE users SET fullname = ?, phone = ?, district_id = ?, district_name = ?, organization_id = ?, organization_name = ?, role = ?, status = ?";
            $params = [
                $fullname, $phone, $district_id, $district_name,
                $organization_id ?: null, $organization_name, $role, $status
            ];

            if ($status === 'approved') {
                $sql .= ", approved_by = ?, approved_at = IFNULL(approved_at, NOW()), rejection_reason = NULL";
                $params[] = $currentAdminId;
            }

            // Optional password update
            if (!empty($password)) {
                if (strlen($password) < 6) resp('error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmtUp = $pdo->prepare($sql);
            $stmtUp->execute($params);

            log_audit($pdo, $currentAdminId, 'update_user', "แก้ไขข้อมูลผู้ใช้งาน ID {$userId}: {$fullname} (สถานะ: {$status}, สิทธิ์: {$role})");

            resp('success', "บันทึกการแก้ไขข้อมูลของ '{$fullname}' เรียบร้อยแล้ว");
        }
        break;

    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp('error', 'Method not allowed');
        $userId = intval($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($userId <= 0 || !in_array($newStatus, ['approved', 'suspended', 'pending', 'rejected'])) {
            resp('error', 'ข้อมูลไม่ถูกต้อง');
        }

        if ($userId === $currentAdminId && $newStatus !== 'approved') {
            resp('error', 'ไม่สามารถระงับหรือเปลี่ยนสถานะบัญชีของตนเองได้');
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);

        $u = $pdo->query("SELECT username, fullname FROM users WHERE id = {$userId}")->fetch();
        log_audit($pdo, $currentAdminId, 'toggle_status', "เปลี่ยนสถานะผู้ใช้ ID {$userId}: {$u['fullname']} เป็น {$newStatus}");

        resp('success', "เปลี่ยนสถานะของ {$u['fullname']} เป็น " . strtoupper($newStatus) . " เรียบร้อย");
        break;

    default:
        resp('error', 'Invalid action');
}
