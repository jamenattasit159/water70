<?php
// admin.php - Superadmin User Management & Approval Panel (CRUD)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login('superadmin');

// Get districts for filters and modal
$stmtDistricts = $pdo->query("SELECT id, name_th, code FROM districts ORDER BY id ASC");
$districts = $stmtDistricts->fetchAll();

$pageTitle = 'จัดการผู้ใช้งานและอนุมัติสิทธิ์ (Super Admin) - ระบบระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb & Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 0.25rem;">
                หน้าหลัก &gt; ส่วนงานบริหาร &gt; ผู้ดูแลระบบระดับสูง
            </div>
            <h1 style="font-size: 1.75rem; color: var(--navy-900);">
                ระบบจัดการผู้ใช้งานและอนุมัติสิทธิ์เข้าใช้งาน
            </h1>
            <div style="color: var(--slate-600); font-size: 0.95rem;">
                อนุมัติคำขอลงทะเบียน ตรวจสอบสังกัดหน่วยงาน และบริหารสิทธิ์การใช้งาน (CRUD) ของจังหวัดอ่างทอง
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" id="btn_refresh_users" class="btn btn-outline" title="รีเฟรชข้อมูล">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <span>รีเฟรช</span>
            </button>
            <button type="button" id="btn_add_new_user" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>เพิ่มผู้ใช้งานใหม่</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="stat_total_users">-</div>
                <div class="stat-label">ผู้ใช้งานทั้งหมดในระบบ</div>
            </div>
        </div>

        <div class="stat-card" style="border-color: #fde68a; background: linear-gradient(to bottom, #ffffff, #fffbeb);">
            <div class="stat-icon gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="stat_pending_users" style="color: var(--gold-700);">-</div>
                <div class="stat-label" style="color: var(--gold-700); font-weight: 600;">รอการอนุมัติสิทธิ์ (Pending)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="stat_approved_users" style="color: var(--emerald-600);">-</div>
                <div class="stat-label">อนุมัติแล้ว (Active)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="stat_rejected_users" style="color: var(--rose-600);">-</div>
                <div class="stat-label">ไม่อนุมัติ / ระงับการใช้งาน</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="gov-card" style="margin-bottom: 1.5rem;">
        <div style="padding: 1rem 1.25rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: center;">
                <!-- Search input -->
                <div class="input-wrapper has-icon">
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>
                    <input type="text" id="filter_search" class="form-control" placeholder="ค้นหาชื่อ, username, เบอร์โทร, หรือหน่วยงาน...">
                </div>

                <!-- District Filter -->
                <div>
                    <select id="filter_district" class="form-select">
                        <option value="">-- อำเภอทั้งหมด (7 อำเภอ) --</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name_th']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filter_status" class="form-select">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="pending" selected>เฉพาะ: รอการอนุมัติ (Pending)</option>
                        <option value="approved">เฉพาะ: อนุมัติแล้ว (Approved)</option>
                        <option value="rejected">เฉพาะ: ไม่อนุมัติ (Rejected)</option>
                        <option value="suspended">เฉพาะ: ระงับการใช้งาน (Suspended)</option>
                    </select>
                </div>

                <!-- Role Filter -->
                <div>
                    <select id="filter_role" class="form-select">
                        <option value="">-- บทบาททั้งหมด --</option>
                        <option value="user">เจ้าหน้าที่ทั่วไป (User)</option>
                        <option value="superadmin">ผู้ดูแลระบบ (Super Admin)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- User Table Card -->
    <div class="gov-card">
        <div class="gov-card-header">
            <div class="gov-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>บัญชีรายชื่อผู้ใช้งานและคำขออนุมัติ</span>
            </div>
            <div style="font-size: 0.85rem; color: var(--slate-500);">
                ลำดับความสำคัญ: คำขอที่รออนุมัติจะแสดงขึ้นก่อน
            </div>
        </div>

        <div class="table-responsive">
            <table class="gov-table">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>ข้อมูลผู้ใช้งาน</th>
                        <th>เบอร์โทรศัพท์</th>
                        <th>อำเภอ / หน่วยงานในสังกัด</th>
                        <th>บทบาท</th>
                        <th>สถานะบัญชี</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="user_table_body">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">กำลังโหลดข้อมูล...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: Add / Edit User (CRUD)
     ========================================== -->
<div class="modal-overlay" id="user_modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="user_modal_title">ข้อมูลผู้ใช้งาน</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="user_crud_form">
            <div class="modal-body">
                <input type="hidden" id="crud_user_id" name="id" value="0">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Username -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="crud_username">
                            ชื่อผู้ใช้งาน (Username) <span class="req">*</span>
                        </label>
                        <input type="text" id="crud_username" name="username" class="form-control" required placeholder="เช่น somchai.s">
                    </div>

                    <!-- Password -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" id="crud_password_label" for="crud_password">
                            รหัสผ่าน <span class="req">*</span>
                        </label>
                        <input type="password" id="crud_password" name="password" class="form-control" placeholder="อย่างน้อย 6 ตัวอักษร">
                    </div>

                    <!-- Fullname -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="crud_fullname">
                            ชื่อ - สกุล <span class="req">*</span>
                        </label>
                        <input type="text" id="crud_fullname" name="fullname" class="form-control" required placeholder="เช่น นายสมชาย สุขเกษม">
                    </div>

                    <!-- Phone -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="crud_phone">
                            เบอร์โทรศัพท์ <span class="req">*</span>
                        </label>
                        <input type="tel" id="crud_phone" name="phone" class="form-control" required placeholder="เช่น 089-1234567">
                    </div>

                    <!-- District Dropdown -->
                    <div class="form-group">
                        <label class="form-label" for="modal_district_select">
                            อำเภอ <span class="req">*</span>
                        </label>
                        <select id="modal_district_select" name="district_id" class="form-select" required>
                            <option value="">-- เลือกอำเภอ --</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name_th']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Organization Dropdown -->
                    <div class="form-group">
                        <label class="form-label" for="modal_org_select">
                            หน่วยงาน <span class="req">*</span>
                        </label>
                        <select id="modal_org_select" name="organization_id" class="form-select" disabled required>
                            <option value="">-- กรุณาเลือกอำเภอก่อน --</option>
                        </select>
                    </div>

                    <!-- Custom Org -->
                    <div class="form-group" id="modal_custom_org_group" style="grid-column: span 2; display: none;">
                        <label class="form-label" for="modal_custom_org_input">ชื่อหน่วยงาน (กรณีพิมพ์เอง)</label>
                        <input type="text" id="modal_custom_org_input" name="custom_org" class="form-control" placeholder="พิมพ์ชื่อหน่วยงาน...">
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label class="form-label" for="crud_role">สิทธิ์การใช้งาน (Role)</label>
                        <select id="crud_role" name="role" class="form-select">
                            <option value="user">เจ้าหน้าที่ทั่วไป (User)</option>
                            <option value="superadmin">ผู้ดูแลระบบ (Super Admin)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="form-label" for="crud_status">สถานะบัญชี (Status)</label>
                        <select id="crud_status" name="status" class="form-select">
                            <option value="approved">อนุมัติแล้ว (Approved)</option>
                            <option value="pending">รอการอนุมัติ (Pending)</option>
                            <option value="rejected">ไม่อนุมัติ (Rejected)</option>
                            <option value="suspended">ระงับชั่วคราว (Suspended)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-modal-cancel">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: Reject Reason Form
     ========================================== -->
<div class="modal-overlay" id="reject_modal">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header" style="background: #fff1f2;">
            <h3 class="modal-title" style="color: var(--rose-700);">ไม่อนุมัติคำขอเข้าใช้งาน</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="reject_form">
            <div class="modal-body">
                <input type="hidden" id="reject_user_id" name="id">
                <p style="margin-bottom: 1rem; color: var(--slate-700);">
                    คุณกำลังจะปฏิเสธคำขอของผู้ใช้งาน: <strong id="reject_user_name_display" style="color: var(--navy-900);"></strong>
                </p>
                <div class="form-group">
                    <label class="form-label" for="reject_reason">
                        เหตุผลการปฏิเสธ (เพื่อแสดงให้ผู้ใช้ทราบ) <span class="req">*</span>
                    </label>
                    <textarea id="reject_reason" name="reason" class="form-control" rows="3" required placeholder="เช่น ข้อมูลสังกัดหน่วยงานไม่ตรงกับทะเบียนบุคลากร หรือเบอร์โทรศัพท์ติดต่อไม่ได้"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-modal-cancel">ยกเลิก</button>
                <button type="submit" class="btn btn-danger">ยืนยันการปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/admin.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
