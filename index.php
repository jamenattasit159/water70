<?php
// index.php - Login Portal for Provincial Water & Civil Management System
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

if (is_logged_in()) {
    if ($_SESSION['role'] === 'superadmin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$pageTitle = 'เข้าสู่ระบบ - ระบบบริหารจัดการข้อมูลระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';

$msg = $_GET['msg'] ?? '';
?>

<div class="container auth-container">
    <?php if ($msg === 'logged_out'): ?>
        <div class="gov-alert info">
            <div class="gov-alert-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div>คุณได้ออกจากระบบเรียบร้อยแล้ว</div>
        </div>
    <?php elseif ($msg === 'need_login'): ?>
        <div class="gov-alert warning">
            <div class="gov-alert-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div>กรุณาเข้าสู่ระบบก่อนเข้าใช้งานส่วนงานราชการ</div>
        </div>
    <?php elseif ($msg === 'account_inactive'): ?>
        <div class="gov-alert danger">
            <div class="gov-alert-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div>บัญชีของคุณถูกระงับหรือยังไม่ได้รับการอนุมัติ กรุณาติดต่อ Super Admin</div>
        </div>
    <?php endif; ?>

    <div class="gov-card">
        <div class="gov-card-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #19436e 100%); color: #ffffff;">
            <div>
                <div style="color: #ffd778; font-size: 0.825rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                    Official Civil Authentication
                </div>
                <div style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: #ffffff;">
                    เข้าสู่ระบบผู้ปฏิบัติงาน
                </div>
            </div>
            <div style="background: rgba(255, 255, 255, 0.15); border-radius: var(--radius-md); padding: 0.5rem 0.75rem; font-size: 0.8rem; text-align: right;">
                <span style="display: block; font-weight: 600;">จังหวัดอ่างทอง</span>
                <span style="opacity: 0.85;">7 อำเภอ</span>
            </div>
        </div>

        <div class="gov-card-body">
            <!-- Dynamic Alert Box for AJAX responses -->
            <div id="login_alert" style="display: none;"></div>

            <form id="login_form" method="POST">
                <div class="form-group">
                    <label class="form-label" for="login_username">
                        ชื่อผู้ใช้งาน (Username) <span class="req">*</span>
                    </label>
                    <div class="input-wrapper has-icon">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <input type="text" id="login_username" name="username" class="form-control" placeholder="ระบุชื่อผู้ใช้งาน" required autocomplete="username" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label class="form-label" for="login_password" style="margin-bottom: 0;">
                            รหัสผ่าน (Password) <span class="req">*</span>
                        </label>
                    </div>
                    <div class="input-wrapper has-icon">
                        <div class="input-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <input type="password" id="login_password" name="password" class="form-control" placeholder="ระบุรหัสผ่าน" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" title="แสดง/ซ่อนรหัสผ่าน">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div style="margin-top: 1.75rem;">
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 0.8rem; font-size: 1.05rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        <span>เข้าสู่ระบบ</span>
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--slate-200); font-size: 0.925rem;">
                ยังไม่มีบัญชีผู้ใช้งานส่วนงานราชการ? 
                <a href="register.php" style="font-weight: 600; color: var(--navy-800); text-decoration: underline;">
                    ลงทะเบียนขอรับสิทธิ์เข้าใช้งาน
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Credentials Helper Box for Testing and Evaluation -->
    <div class="gov-card" style="margin-top: 1.5rem; border: 1px dashed #cbd5e1; background: #ffffff;">
        <div style="padding: 1.15rem 1.35rem;">
            <div style="font-size: 0.85rem; font-weight: 600; color: #0f2c4c; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.65rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>บัญชีทดสอบระบบที่สร้างไว้ล่วงหน้า (Demo Accounts)</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; font-size: 0.825rem;">
                <div style="background: #f8fafc; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #d97706;">
                    <div style="font-weight: 600; color: #92400e;">1. Super Admin (จัดการอนุมัติ & CRUD)</div>
                    <div>User: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">superadmin</code></div>
                    <div>Pass: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">admin1234</code></div>
                    <button type="button" onclick="fillCreds('superadmin', 'admin1234')" class="btn btn-sm btn-outline" style="margin-top: 0.35rem; padding: 0.15rem 0.5rem; font-size: 0.75rem;">กรอกบัญชีนี้</button>
                </div>
                <div style="background: #f8fafc; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #0284c7;">
                    <div style="font-weight: 600; color: #0369a1;">2. Admin จังหวัด (ดูยอดภาพรวมจังหวัด)</div>
                    <div>User: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">admin.angthong</code></div>
                    <div>Pass: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">admin1234</code></div>
                    <button type="button" onclick="fillCreds('admin.angthong', 'admin1234')" class="btn btn-sm btn-outline" style="margin-top: 0.35rem; padding: 0.15rem 0.5rem; font-size: 0.75rem;">กรอกบัญชีนี้</button>
                </div>
                <div style="background: #f8fafc; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #059669;">
                    <div style="font-weight: 600; color: #065f46;">3. เจ้าหน้าที่ รพ.สต. (กรอกรายงานอุทกภัย)</div>
                    <div>User: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">sarawut.m</code></div>
                    <div>Pass: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">user1234</code></div>
                    <button type="button" onclick="fillCreds('sarawut.m', 'user1234')" class="btn btn-sm btn-outline" style="margin-top: 0.35rem; padding: 0.15rem 0.5rem; font-size: 0.75rem;">กรอกบัญชีนี้</button>
                </div>
                <div style="background: #f8fafc; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid #f59e0b;">
                    <div style="font-weight: 600; color: #b45309;">4. บัญชีที่รอการอนุมัติ (Pending)</div>
                    <div>User: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">somchai.s</code></div>
                    <div>Pass: <code style="background: #e2e8f0; padding: 1px 4px; border-radius: 3px;">user1234</code></div>
                    <button type="button" onclick="fillCreds('somchai.s', 'user1234')" class="btn btn-sm btn-outline" style="margin-top: 0.35rem; padding: 0.15rem 0.5rem; font-size: 0.75rem;">ทดสอบสถานะรออนุมัติ</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCreds(u, p) {
    document.getElementById('login_username').value = u;
    document.getElementById('login_password').value = p;
    document.getElementById('login_username').focus();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
