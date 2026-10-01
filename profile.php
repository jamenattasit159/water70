<?php
// profile.php - User Profile Settings & Password Change
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$feedback = '';
$feedbackType = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $district_id = intval($_POST['district_id'] ?? 0);
        $organization_id = intval($_POST['organization_id'] ?? 0);
        $custom_org = trim($_POST['custom_org'] ?? '');

        if (empty($fullname) || empty($phone) || $district_id <= 0) {
            $feedback = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
            $feedbackType = 'danger';
        } else {
            // Get district name
            $stmtD = $pdo->prepare("SELECT name_th FROM districts WHERE id = ?");
            $stmtD->execute([$district_id]);
            $districtRow = $stmtD->fetch();
            $district_name = $districtRow ? $districtRow['name_th'] : '';

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

            try {
                $stmtUp = $pdo->prepare("UPDATE users SET fullname = ?, phone = ?, district_id = ?, district_name = ?, organization_id = ?, organization_name = ? WHERE id = ?");
                $stmtUp->execute([$fullname, $phone, $district_id, $district_name, $organization_id ?: null, $organization_name, $user['id']]);

                $_SESSION['fullname'] = $fullname;
                $_SESSION['phone'] = $phone;
                $_SESSION['district_id'] = $district_id;
                $_SESSION['district_name'] = $district_name;
                $_SESSION['organization_id'] = $organization_id ?: null;
                $_SESSION['organization_name'] = $organization_name;

                $user = current_user();
                $feedback = 'บันทึกการแก้ไขข้อมูลส่วนตัวเรียบร้อยแล้ว';
                $feedbackType = 'success';
            } catch (PDOException $e) {
                $feedback = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
                $feedbackType = 'danger';
            }
        }
    } elseif ($action === 'change_password') {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_new_password'] ?? '';

        $stmtU = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtU->execute([$user['id']]);
        $row = $stmtU->fetch();

        if (!password_verify($old_pass, $row['password'])) {
            $feedback = 'รหัสผ่านเดิมไม่ถูกต้อง';
            $feedbackType = 'danger';
        } elseif (strlen($new_pass) < 6) {
            $feedback = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            $feedbackType = 'danger';
        } elseif ($new_pass !== $confirm_pass) {
            $feedback = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
            $feedbackType = 'danger';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmtP = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtP->execute([$hashed, $user['id']]);

            $feedback = 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว';
            $feedbackType = 'success';
        }
    }
}

// Get districts
$stmtDistricts = $pdo->query("SELECT id, name_th, code FROM districts ORDER BY id ASC");
$districts = $stmtDistricts->fetchAll();

$pageTitle = 'แก้ไขข้อมูลส่วนตัว - ระบบระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.65rem; color: var(--navy-900);">ข้อมูลส่วนตัวและการตั้งค่า</h1>
        <div style="color: var(--slate-600);">จัดการข้อมูลผู้ปฏิบัติงาน สังกัดหน่วยงาน และเปลี่ยนรหัสผ่าน</div>
    </div>

    <?php if (!empty($feedback)): ?>
        <div class="gov-alert <?= $feedbackType ?>">
            <div class="gov-alert-icon">
                <?php if ($feedbackType === 'success'): ?>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php else: ?>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <?php endif; ?>
            </div>
            <div><?= htmlspecialchars($feedback) ?></div>
        </div>
    <?php endif; ?>

    <!-- Profile Edit Form -->
    <div class="gov-card" style="margin-bottom: 2rem;">
        <div class="gov-card-header">
            <div class="gov-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>แก้ไขข้อมูลทั่วไป</span>
            </div>
        </div>
        <div class="gov-card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="update_profile">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Username (read only) -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled style="background: #f1f5f9;">
                    </div>

                    <!-- Fullname -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="fullname">ชื่อ - สกุล <span class="req">*</span></label>
                        <input type="text" id="fullname" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>

                    <!-- Phone -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="phone">เบอร์โทรศัพท์ <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>

                    <!-- District -->
                    <div class="form-group">
                        <label class="form-label" for="district_select">อำเภอ <span class="req">*</span></label>
                        <select id="district_select" name="district_id" class="form-select" required>
                            <option value="">-- เลือกอำเภอ --</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($user['district_id'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name_th']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Organization -->
                    <div class="form-group">
                        <label class="form-label" for="organization_select">หน่วยงานในสังกัด <span class="req">*</span></label>
                        <select id="organization_select" name="organization_id" class="form-select" data-selected-id="<?= $user['organization_id'] ?: 'custom' ?>" required>
                            <option value="<?= $user['organization_id'] ?>"><?= htmlspecialchars($user['organization_name']) ?></option>
                        </select>
                    </div>

                    <!-- Custom org -->
                    <div class="form-group" id="custom_org_group" style="grid-column: span 2; display: <?= empty($user['organization_id']) ? 'block' : 'none' ?>;">
                        <label class="form-label" for="custom_org_input">ชื่อหน่วยงาน (ระบุเอง)</label>
                        <input type="text" id="custom_org_input" name="custom_org" class="form-control" value="<?= empty($user['organization_id']) ? htmlspecialchars($user['organization_name']) : '' ?>">
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>บันทึกการแก้ไขข้อมูล</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Change Form -->
    <div class="gov-card">
        <div class="gov-card-header">
            <div class="gov-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                <span>เปลี่ยนรหัสผ่าน</span>
            </div>
        </div>
        <div class="gov-card-body">
            <form method="POST">
                <input type="hidden" name="form_action" value="change_password">

                <div class="form-group">
                    <label class="form-label" for="old_password">รหัสผ่านเดิม <span class="req">*</span></label>
                    <div class="input-wrapper">
                        <input type="password" id="old_password" name="old_password" class="form-control" required>
                        <button type="button" class="password-toggle-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="new_password">รหัสผ่านใหม่ <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="อย่างน้อย 6 ตัวอักษร">
                            <button type="button" class="password-toggle-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password">ยืนยันรหัสผ่านใหม่อีกครั้ง <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required placeholder="กรอกซ้ำให้ตรงกัน">
                            <button type="button" class="password-toggle-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="submit" class="btn btn-outline" style="border-color: var(--navy-800); color: var(--navy-800);">
                        <span>อัปเดตรหัสผ่านใหม่</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
