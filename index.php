<?php
// index.php - Modern SaaS Login Portal (Linear / Vercel Style)
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

$pageTitle = 'เข้าสู่ระบบ - ระบบบริหารจัดการข้อมูลและสถานการณ์อุทกภัยระดับจังหวัด';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="icon" type="image/png" href="logo.png">
    <script>
        // Init theme immediately to prevent flashing (Default to Light theme on first visit)
        (function() {
            const savedTheme = localStorage.getItem('water_theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="auth-page-root">
<a class="skip-link" href="#login_form">ข้ามไปยังแบบฟอร์มเข้าสู่ระบบ</a>

<div class="auth-minimal-wrapper">
    <!-- Top Utility Bar: OnePage & Theme Switch -->
    <div class="auth-topbar">
        <a href="onepage.php" class="btn btn-sm btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
            </svg>
            <span>OnePage สถานการณ์</span>
        </a>

        <button type="button" class="theme-toggle-btn" id="theme_toggle_btn" aria-label="สลับธีมสว่างหรือมืด" title="สลับธีม สว่าง / มืด">
            <svg id="theme_icon_sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg id="theme_icon_moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>
    </div>

    <!-- Minimal Login Card -->
    <div class="auth-card">
        <!-- Logo and Header -->
        <div class="auth-card-header">
            <div class="auth-logo-badge">
                <img src="logo.png" alt="ตรากระทรวงสาธารณสุข">
            </div>
            <div class="auth-subtitle">สำนักงานสาธารณสุขจังหวัดอ่างทอง</div>
            <h1 class="auth-title">ระบบบริหารจัดการข้อมูลและสถานการณ์อุทกภัย</h1>
            <p class="auth-desc">เข้าสู่ระบบผู้ปฏิบัติงาน (7 อำเภอ 59 หน่วยงาน)</p>
        </div>

        <!-- Server Status Messages -->
        <?php if ($msg === 'logged_out'): ?>
            <div class="gov-alert info">
                <div class="gov-alert-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <div>คุณได้ออกจากระบบเรียบร้อยแล้ว</div>
            </div>
        <?php elseif ($msg === 'need_login'): ?>
            <div class="gov-alert warning">
                <div class="gov-alert-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div>กรุณาเข้าสู่ระบบก่อนเข้าใช้งานส่วนงานราชการ</div>
            </div>
        <?php elseif ($msg === 'account_inactive'): ?>
            <div class="gov-alert danger">
                <div class="gov-alert-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </div>
                <div>บัญชีของคุณถูกระงับหรือยังไม่ได้รับการอนุมัติ กรุณาติดต่อ Super Admin</div>
            </div>
        <?php endif; ?>

        <!-- Dynamic Alert Box for AJAX responses -->
        <div id="login_alert" style="display: none;"></div>

        <form id="login_form" method="POST">
            <div class="form-group">
                <label class="form-label" for="login_username">
                    ชื่อผู้ใช้งาน (Username) <span class="req">*</span>
                </label>
                <div class="input-wrapper has-icon">
                    <div class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
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
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <input type="password" id="login_password" name="password" class="form-control" placeholder="ระบุรหัสผ่าน" required autocomplete="current-password">
                    <button type="button" class="password-toggle-btn" aria-label="แสดงหรือซ่อนรหัสผ่าน" title="แสดง/ซ่อนรหัสผ่าน">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 0.75rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    <span>เข้าสู่ระบบ</span>
                </button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.95rem; color: var(--color-text-secondary);">
            ยังไม่มีบัญชีผู้ปฏิบัติงาน? 
            <a href="register.php" style="font-weight: 700; color: var(--color-primary); text-decoration: underline;">ลงทะเบียนขอรับสิทธิ์ใช้งาน</a>
        </div>

        <!-- Modern Discreet Collapsible Demo Accounts -->
        <div class="demo-accounts-collapse" id="demo_collapse">
            <button type="button" class="demo-accounts-trigger" id="demo_trigger">
                <span style="display: flex; align-items: center; gap: 0.45rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>กรอกบัญชีทดสอบระบบ (Demo Accounts)</span>
                </span>
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
            <div class="demo-accounts-content">
                <div class="demo-pills-grid">
                    <div class="demo-pill" onclick="fillCreds('superadmin', 'admin1234')">
                        <span class="demo-pill-title">Super Admin</span>
                        <span class="demo-pill-role">จัดการผู้ใช้ / CRUD</span>
                        <span class="demo-pill-btn">คลิกเพื่อกรอก &rarr;</span>
                    </div>
                    <div class="demo-pill" onclick="fillCreds('admin.angthong', 'admin1234')">
                        <span class="demo-pill-title">Admin จังหวัด</span>
                        <span class="demo-pill-role">ภาพรวม 7 อำเภอ</span>
                        <span class="demo-pill-btn">คลิกเพื่อกรอก &rarr;</span>
                    </div>
                    <div class="demo-pill" onclick="fillCreds('sarawut.m', 'user1234')">
                        <span class="demo-pill-title">จนท. รพ.สต.</span>
                        <span class="demo-pill-role">บันทึกรายงานอุทกภัย</span>
                        <span class="demo-pill-btn">คลิกเพื่อกรอก &rarr;</span>
                    </div>
                    <div class="demo-pill" onclick="fillCreds('somchai.s', 'user1234')">
                        <span class="demo-pill-title">ผู้ใช้รออนุมัติ</span>
                        <span class="demo-pill-role">ทดสอบสถานะ Pending</span>
                        <span class="demo-pill-btn">คลิกเพื่อกรอก &rarr;</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="auth-bottom-note">
        ศูนย์ปฏิบัติการภาวะฉุกเฉินทางสาธารณสุข (PHEOC) • สำนักงานสาธารณสุขจังหวัดอ่างทอง 2569
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
// Demo accounts collapsible toggle
const demoCollapse = document.getElementById('demo_collapse');
const demoTrigger = document.getElementById('demo_trigger');
if (demoTrigger && demoCollapse) {
    demoTrigger.addEventListener('click', function() {
        demoCollapse.classList.toggle('open');
    });
}

function fillCreds(u, p) {
    document.getElementById('login_username').value = u;
    document.getElementById('login_password').value = p;
    document.getElementById('login_username').focus();
    showToast('info', `กรอกข้อมูลบัญชี ${u} เรียบร้อยแล้ว`);
}

// Dark Mode Toggle Logic
const themeBtn = document.getElementById('theme_toggle_btn');
const iconSun = document.getElementById('theme_icon_sun');
const iconMoon = document.getElementById('theme_icon_moon');

function updateThemeIcons() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
        iconSun.style.display = 'block';
        iconMoon.style.display = 'none';
    } else {
        iconSun.style.display = 'none';
        iconMoon.style.display = 'block';
    }
}
updateThemeIcons();

if (themeBtn) {
    themeBtn.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('water_theme', newTheme);
        updateThemeIcons();
    });
}
</script>
</body>
</html>
