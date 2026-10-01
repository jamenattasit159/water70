<?php
// register.php - Official Registration with Chained District-Organization Dropdown
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

// Fetch all districts for the dropdown
$stmtDistricts = $pdo->query("SELECT id, name_th, code FROM districts ORDER BY id ASC");
$districts = $stmtDistricts->fetchAll();

$pageTitle = 'ลงทะเบียนผู้ปฏิบัติงาน - ระบบบริหารจัดการข้อมูลระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-container" style="max-width: 680px;">
    <!-- Registration Alert -->
    <div id="register_alert" style="display: none;"></div>

    <!-- Registration Card -->
    <div class="gov-card" id="register_card">
        <div class="gov-card-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #19436e 100%); color: #ffffff;">
            <div>
                <div style="color: #ffd778; font-size: 0.825rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                    Civil Official Registration
                </div>
                <div style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: #ffffff;">
                    ลงทะเบียนขอรับสิทธิ์เข้าใช้งานระบบ
                </div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); border-radius: var(--radius-md); padding: 0.45rem 0.75rem; font-size: 0.8rem; text-align: right;">
                <span style="font-weight: 600;">จังหวัดอ่างทอง</span>
            </div>
        </div>

        <div class="gov-card-body">
            <!-- Official Workflow Notice -->
            <div class="gov-alert warning" style="margin-bottom: 1.5rem;">
                <div class="gov-alert-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div style="font-size: 0.9rem;">
                    <strong>ข้อกำหนดการเข้าใช้งาน:</strong> เมื่อลงทะเบียนเสร็จสิ้น บัญชีของท่านจะอยู่ในสถานะ 
                    <span class="badge badge-pending" style="vertical-align: middle;">รอการอนุมัติ</span> 
                    โดยต้องได้รับการยืนยันสิทธิ์จาก <strong>ผู้ดูแลระบบระดับสูง (Super Admin)</strong> ประจำจังหวัด ก่อนจึงจะสามารถเข้าสู่ระบบและปฏิบัติงานได้
                </div>
            </div>

            <form id="register_form" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <!-- Fullname -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="reg_fullname">
                            ชื่อ - นามสกุล <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            </div>
                            <input type="text" id="reg_fullname" name="fullname" class="form-control" placeholder="เช่น นายสมศักดิ์ ภักดีชน" required>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="reg_phone">
                            เบอร์โทรศัพท์ติดต่อ <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <input type="tel" id="reg_phone" name="phone" class="form-control" placeholder="เช่น 081-234-5678" required>
                        </div>
                    </div>

                    <!-- District Dropdown (Chained Source) -->
                    <div class="form-group">
                        <label class="form-label" for="district_select">
                            อำเภอที่ปฏิบัติงาน <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            </div>
                            <select id="district_select" name="district_id" class="form-select" required>
                                <option value="">-- เลือกอำเภอ --</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name_th']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-text">เลือกอำเภอเพื่อแสดงรายชื่อหน่วยงานในสังกัด</div>
                    </div>

                    <!-- Organization Dropdown (Chained Target) -->
                    <div class="form-group">
                        <label class="form-label" for="organization_select">
                            หน่วยงานในอำเภอ <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 11h2M6 15h2M11 11h2M11 15h2M16 11h2M16 15h2M9 3h6v4H9z"></path></svg>
                            </div>
                            <select id="organization_select" name="organization_id" class="form-select" disabled required>
                                <option value="">-- กรุณาเลือกอำเภอก่อน --</option>
                            </select>
                        </div>
                        <div class="form-text">รพ.สต., สสอ., หรือ โรงพยาบาลในพื้นที่</div>
                    </div>

                    <!-- Custom Organization Input (Revealed if 'other' is chosen) -->
                    <div class="form-group" id="custom_org_group" style="grid-column: span 2; display: none;">
                        <label class="form-label" for="custom_org_input">
                            ระบุชื่อหน่วยงาน (กรณีไม่มีในรายการ) <span class="req">*</span>
                        </label>
                        <input type="text" id="custom_org_input" name="custom_org" class="form-control" placeholder="พิมพ์ชื่อหน่วยงาน / ส่วนราชการ / โครงการ">
                    </div>

                    <!-- Divider -->
                    <div style="grid-column: span 2; border-top: 1px solid var(--slate-200); margin: 0.5rem 0 0.75rem 0;"></div>

                    <!-- Username -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="reg_username">
                            ชื่อผู้ใช้งานสำหรับเข้าสู่ระบบ (Username) <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                            </div>
                            <input type="text" id="reg_username" name="username" class="form-control" placeholder="ภาษาอังกฤษหรือตัวเลขอย่างน้อย 3 ตัวอักษร เช่น somchai.s" required autocomplete="username">
                        </div>
                        <div class="form-text">ใช้สำหรับล็อกอินเข้าใช้งานระบบ</div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="reg_password">
                            กำหนดรหัสผ่าน (Password) <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            </div>
                            <input type="password" id="reg_password" name="password" class="form-control" placeholder="อย่างน้อย 6 ตัวอักษร" required autocomplete="new-password">
                            <button type="button" class="password-toggle-btn" title="แสดง/ซ่อนรหัสผ่าน">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="reg_confirm_password">
                            ยืนยันรหัสผ่านอีกครั้ง <span class="req">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <div class="input-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                            <input type="password" id="reg_confirm_password" name="confirm_password" class="form-control" placeholder="กรอกรหัสผ่านซ้ำอีกครั้ง" required autocomplete="new-password">
                            <button type="button" class="password-toggle-btn" title="แสดง/ซ่อนรหัสผ่าน">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem; font-size: 1.05rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        <span>ยืนยันการลงทะเบียน</span>
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--slate-200); font-size: 0.925rem;">
                มีบัญชีผู้ใช้งานอยู่แล้ว? 
                <a href="index.php" style="font-weight: 600; color: var(--navy-800); text-decoration: underline;">
                    เข้าสู่ระบบที่นี่
                </a>
            </div>
        </div>
    </div>

    <!-- Registration Success State (Shown after submission) -->
    <div class="gov-card" id="register_success_card" style="display: none; text-align: center; padding: 2.5rem 1.5rem;">
        <div style="width: 70px; height: 70px; background: var(--gold-50); border: 2px solid var(--gold-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto; color: var(--gold-600);">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        
        <h2 style="font-size: 1.5rem; color: var(--navy-900); margin-bottom: 0.5rem;">
            ลงทะเบียนส่งคำขอเรียบร้อยแล้ว
        </h2>
        <div style="display: inline-block; margin-bottom: 1.25rem;">
            <span class="badge badge-pending" style="font-size: 0.95rem; padding: 0.35rem 1rem;">สถานะ: รอการอนุมัติสิทธิ์จาก Super Admin</span>
        </div>

        <p style="color: var(--slate-600); max-width: 520px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
            ระบบได้บันทึกข้อมูลคำขอเปิดสิทธิ์การใช้งานของท่านแล้ว บัญชีผู้ใช้จะสามารถเข้าสู่ระบบและปฏิบัติงานได้ 
            <strong>หลังจากผู้ดูแลระบบระดับสูง (Super Admin) ของจังหวัดดำเนินการตรวจสอบและอนุมัติการใช้งาน</strong>
        </p>

        <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-md); padding: 1.25rem; max-width: 480px; margin: 0 auto 2rem auto; text-align: left; font-size: 0.875rem;">
            <div style="font-weight: 600; color: var(--navy-900); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span>ช่องทางติดต่อเร่งด่วนสำหรับการอนุมัติ:</span>
            </div>
            <div>• ศูนย์ประสานงานข้อมูลสารสนเทศจังหวัดอ่างทอง: โทร 035-611234 ต่อ 102</div>
            <div>• กลุ่มงานบริหารยุทธศาสตร์ สำนักงานสาธารณสุขจังหวัดอ่างทอง</div>
        </div>

        <div style="display: flex; justify-content: center; gap: 1rem;">
            <a href="index.php" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span>กลับสู่หน้าเข้าสู่ระบบ</span>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
