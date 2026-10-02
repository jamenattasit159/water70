<?php
// admin.php - Superadmin User Management & Approval Panel (Modern SaaS Linear Style)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login('superadmin');

// Get districts for filters and modal
$stmtDistricts = $pdo->query("SELECT id, name_th, code FROM districts ORDER BY id ASC");
$districts = $stmtDistricts->fetchAll();

$pageTitle = 'จัดการผู้ใช้งานและอนุมัติสิทธิ์ (Super Admin) - ระบบระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb & Page Header -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.95rem; color: var(--color-text-secondary); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.45rem; font-weight: 500;">
            <span>การจัดการระบบ</span>
            <span>/</span>
            <span style="color: var(--color-text); font-weight: 700;">จัดการผู้ใช้งาน & อนุมัติสิทธิ์</span>
        </div>
        <h1 style="font-size: 2rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.35rem;">
            จัดการผู้ใช้งานและอนุมัติสิทธิ์เข้าใช้งาน
        </h1>
        <div style="color: var(--color-text-secondary); font-size: 1.05rem;">
            ตรวจสอบคำขอลงทะเบียน อนุมัติสิทธิ์ และบริหารจัดการผู้ใช้งานในระบบ 59 หน่วยงานสาธารณสุข
        </div>
    </div>
    <div style="display: flex; gap: 0.65rem;">
        <button type="button" id="btn_refresh_users" class="btn btn-outline" title="รีเฟรชข้อมูล">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            <span>รีเฟรช</span>
        </button>
        <button type="button" id="btn_add_new_user" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>เพิ่มผู้ใช้งานใหม่</span>
        </button>
    </div>
</div>

<!-- Quick Action / Shortcut to Provincial Flood Analytics -->
<div class="gov-card" style="margin-bottom: 1.75rem; border-left: 4px solid var(--primary-600); background: linear-gradient(to right, #eff6ff, #f8fafc);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.25rem;">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                📊
            </div>
            <div>
                <div style="font-weight: 700; color: #0f2c4c; font-size: 1.05rem;">
                    ศูนย์ติดตามสถานการณ์และสรุปรายงานอุทกภัยระดับจังหวัด (ตามแบบฟอร์ม 2569)
                </div>
                <div style="font-size: 0.85rem; color: #64748b;">
                    วิเคราะห์ข้อมูล 8 กลุ่มเปราะบางรายหมู่บ้าน บริการแพทย์เคลื่อนที่รายอำเภอ และแนวโน้ม Time Series ตามไฟล์รวมรายงานอุทกภัย 2569
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="provincial_overview.php" class="btn btn-primary" style="font-size: 0.875rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>เปิดแดชบอร์ดภาพรวมจังหวัด</span>
            </a>
            <a href="onepage.php" target="_blank" class="btn btn-outline" style="font-size: 0.875rem;">
                <span>ดู OnePage ผู้บริหาร</span>
            </a>
        </div>
    </div>
</div>

<!-- Modern SaaS Stat Cards (Clean white surfaces, soft colored icon circles) -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-details">
            <div class="stat-value" id="stat_total_users">-</div>
            <div class="stat-label">ผู้ใช้งานทั้งหมดในระบบ</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-details">
            <div class="stat-value" id="stat_pending_users" style="color: var(--color-warning-text);">-</div>
            <div class="stat-label">รอการอนุมัติ (Pending)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        <div class="stat-details">
            <div class="stat-value" id="stat_approved_users" style="color: var(--color-success-text);">-</div>
            <div class="stat-label">อนุมัติแล้ว (Active)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <div class="stat-details">
            <div class="stat-value" id="stat_rejected_users" style="color: var(--color-danger-text);">-</div>
            <div class="stat-label">ไม่อนุมัติ / ระงับการใช้งาน</div>
        </div>
    </div>
</div>

<!-- Modern Filter & Search Toolbar -->
<div class="filter-bar">
    <div class="filter-grid">
        <!-- Search input -->
        <div class="input-wrapper has-icon">
            <div class="input-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            <input type="text" id="filter_search" class="form-control" placeholder="ค้นหาชื่อ, username, เบอร์โทร...">
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
                <option value="pending" selected>เฉพาะ: รออนุมัติ (Pending)</option>
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

    <!-- Active Filter Chips -->
    <div class="filter-chips" id="filter_chips_wrap" style="display: flex;">
        <span style="font-size: 0.95rem; font-weight: 600; color: var(--color-text-secondary);">ตัวกรองที่ใช้งาน:</span>
        <span class="filter-chip" id="chip_status">
            <span>สถานะ: รออนุมัติ</span>
            <button type="button" class="chip-remove" onclick="clearFilter('status')" aria-label="ล้างตัวกรองสถานะ">&times;</button>
        </span>
        <button type="button" class="btn btn-sm btn-ghost" id="btn_clear_all_filters" style="font-size: 0.9rem; padding: 0.25rem 0.65rem;">
            ล้างตัวกรองทั้งหมด
        </button>
    </div>
</div>

<!-- Modern User Table Card -->
<div class="card">
    <div class="card-header" style="padding: 1.35rem 1.6rem;">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div class="card-title" style="font-size: 1.3rem;">บัญชีรายชื่อผู้ใช้งานและคำขออนุมัติ</div>
            <span class="badge badge-info" id="user_count_badge">กำลังโหลด</span>
        </div>
        <div style="font-size: 0.95rem; color: var(--color-text-secondary); font-weight: 500;">
            คำขอที่รออนุมัติจะแสดงขึ้นก่อนโดยอัตโนมัติ
        </div>
    </div>

    <div class="table-responsive">
        <table class="gov-table">
            <thead>
                <tr>
                    <th style="width: 48px;">#</th>
                    <th>ข้อมูลผู้ใช้งาน</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>อำเภอ / หน่วยงานสังกัด</th>
                    <th>บทบาท</th>
                    <th>สถานะบัญชี</th>
                    <th style="text-align: right; padding-right: 1.5rem;">การจัดการ</th>
                </tr>
            </thead>
            <tbody id="user_table_body">
                <!-- Skeletons will load initially -->
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--color-text-muted);">
                        กำลังโหลดข้อมูลผู้ใช้งาน...
                    </td>
                </tr>
            </tbody>
        </table>
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
        <div class="modal-header" style="background: var(--color-danger-bg);">
            <h3 class="modal-title" style="color: var(--color-danger-text);">ไม่อนุมัติคำขอเข้าใช้งาน</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form id="reject_form">
            <div class="modal-body">
                <input type="hidden" id="reject_user_id" name="id">
                <p style="margin-bottom: 1rem; color: var(--color-text-secondary); font-size: 0.9rem;">
                    คุณกำลังจะปฏิเสธคำขอของผู้ใช้งาน: <strong id="reject_user_name_display" style="color: var(--color-text);"></strong>
                </p>
                <div class="form-group">
                    <label class="form-label" for="reject_reason">
                        เหตุผลการปฏิเสธ (เพื่อแสดงให้ผู้ใช้ทราบ) <span class="req">*</span>
                    </label>
                    <textarea id="reject_reason" name="reason" class="form-control" rows="3" required placeholder="เช่น ข้อมูลสังกัดหน่วยงานไม่ตรงกับทะเบียนบุคลากร หรือเบอร์โทรศัพท์ติดต่อไม่ได้" style="height: auto;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline btn-modal-cancel">ยกเลิก</button>
                <button type="submit" class="btn btn-danger-solid">ยืนยันการปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL: Delete Confirmation
     ========================================== -->
<div class="modal-overlay" id="delete_modal">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: var(--color-danger-text); display: flex; align-items: center; gap: 0.45rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>ยืนยันการลบผู้ใช้งาน</span>
            </h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_user_id">
            <p style="color: var(--color-text-secondary); font-size: 0.9rem; line-height: 1.6;">
                คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีผู้ใช้งาน <strong id="delete_user_name_display" style="color: var(--color-text);"></strong> ออกจากระบบ?
            </p>
            <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.5rem;">
                การกระทำนี้จะลบสิทธิ์การเข้าใช้งานของผู้ใช้นี้ และไม่สามารถย้อนกลับได้
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-modal-cancel">ยกเลิก</button>
            <button type="button" id="btn_confirm_delete" class="btn btn-danger-solid">ยืนยันลบข้อมูล</button>
        </div>
    </div>
</div>

<script src="assets/js/admin.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
