<?php
// includes/header.php - Modern SaaS Layout with Collapsible Left Sidebar (Linear/Vercel Style)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userFullname = $_SESSION['fullname'] ?? '';
$userDistrict = $_SESSION['district_name'] ?? '';
$userOrg = $_SESSION['organization_name'] ?? '';
$pageTitle = $pageTitle ?? 'ระบบบริหารจัดการข้อมูลและผู้ปฏิบัติงานระดับจังหวัด';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="icon" type="image/png" href="logo.png">
    <script>
        // Init theme immediately to prevent flashing
        (function() {
            const savedTheme = localStorage.getItem('water_theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body>
<a class="skip-link" href="#main-content">ข้ามไปยังเนื้อหาหลัก</a>

<?php if ($isLoggedIn): ?>
<div class="app-layout" id="app_layout">
    <!-- Backdrop for mobile drawer -->
    <div class="sidebar-backdrop" id="sidebar_backdrop"></div>

    <!-- Left Collapsible Sidebar -->
    <aside class="app-sidebar" id="app_sidebar">
        <!-- Sidebar Brand -->
        <div class="sidebar-header">
            <a href="<?= $userRole === 'superadmin' ? 'admin.php' : 'dashboard.php' ?>" class="sidebar-brand">
                <div class="sidebar-brand-avatar">
                    <img src="logo.png" alt="ตรากระทรวงสาธารณสุข" class="brand-logo-img">
                </div>
                <div>
                    <div class="sidebar-brand-title">ระบบข้อมูลอุทกภัย</div>
                    <div class="sidebar-brand-sub">สสจ.อ่างทอง 7 อำเภอ</div>
                </div>
            </a>
        </div>

        <!-- Sidebar Navigation Groups -->
        <nav class="sidebar-nav">
            <!-- Group 1: Overview -->
            <div class="nav-group">
                <div class="nav-group-title">ภาพรวม & สรุปสถานการณ์</div>
                <a href="dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>แดชบอร์ดหลัก</span>
                </a>

                <?php if (in_array($userRole, ['admin', 'superadmin'])): ?>
                    <a href="provincial_overview.php" class="nav-link <?= $currentScript === 'provincial_overview.php' ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>ภาพรวมทั้งจังหวัด</span>
                    </a>
                <?php endif; ?>

                <a href="onepage.php" class="nav-link <?= $currentScript === 'onepage.php' ? 'active' : '' ?>" target="_blank">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span>OnePage สรุปผู้บริหาร</span>
                </a>
            </div>

            <!-- Group 2: Data Entry & Medical Reports -->
            <div class="nav-group">
                <div class="nav-group-title">บันทึกรายงานสถานการณ์</div>
                <a href="report_entry.php" class="nav-link <?= $currentScript === 'report_entry.php' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>แบบรายงานประจำวัน (ข้อ 1 - 3)</span>
                </a>

                <a href="medical_services.php" class="nav-link <?= $currentScript === 'medical_services.php' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                    <span>สมุดออกหน่วยแพทย์ (แบบละเอียด)</span>
                </a>
            </div>

            <!-- Group 3: System Administration -->
            <div class="nav-group">
                <div class="nav-group-title">ระบบและการจัดการ</div>
                <?php if ($userRole === 'superadmin'): ?>
                    <a href="admin.php" class="nav-link <?= $currentScript === 'admin.php' ? 'active' : '' ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>จัดการผู้ใช้ & อนุมัติ</span>
                    </a>
                <?php endif; ?>

                <a href="profile.php" class="nav-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>โปรไฟล์ของฉัน</span>
                </a>
            </div>
        </nav>

        <!-- Sidebar Footer / Quick User Profile -->
        <div class="sidebar-footer">
            <a href="profile.php" class="sidebar-user" title="ดูโปรไฟล์">
                <div class="user-avatar" style="background: <?= $userRole === 'superadmin' ? '#2563eb' : ($userRole === 'admin' ? '#0284c7' : '#059669') ?>;">
                    <?= mb_substr($userFullname, 0, 1, 'UTF-8') ?>
                </div>
                <div class="user-meta">
                    <div class="user-name"><?= htmlspecialchars($userFullname) ?></div>
                    <div class="user-role"><?= $userRole === 'superadmin' ? 'Super Admin' : ($userRole === 'admin' ? 'Admin จังหวัด' : htmlspecialchars($userDistrict)) ?></div>
                </div>
            </a>
            <a href="api/auth.php?action=logout" class="btn btn-icon btn-sm btn-ghost" title="ออกจากระบบ" style="color: var(--color-danger-text);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </a>
        </div>
    </aside>

    <!-- Main Content Shell -->
    <div class="app-main-wrapper">
        <!-- Modern SaaS Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-btn" id="mobile_menu_btn" title="เปิดเมนู">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div style="font-size: 0.85rem; color: var(--color-text-muted); display: flex; align-items: center; gap: 0.4rem;">
                    <span>จังหวัดอ่างทอง</span>
                    <span>&bull;</span>
                    <span style="font-weight: 500; color: var(--color-text);"><?= htmlspecialchars($userOrg ?: 'สำนักงานสาธารณสุขจังหวัด') ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Theme Switcher -->
                <button type="button" class="theme-toggle-btn" id="global_theme_toggle" title="สลับธีม สว่าง / มืด">
                    <svg id="global_icon_sun" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
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
                    <svg id="global_icon_moon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>

                <!-- Avatar Dropdown Menu -->
                <div class="user-dropdown" id="user_dropdown_wrap">
                    <button type="button" class="btn btn-ghost" id="user_dropdown_btn" style="padding: 0.25rem 0.5rem; gap: 0.65rem;">
                        <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem; background: var(--color-primary);">
                            <?= mb_substr($userFullname, 0, 1, 'UTF-8') ?>
                        </div>
                        <div style="text-align: left; line-height: 1.2; display: none; @media(min-width: 640px){display: block;}">
                            <div style="font-size: 0.825rem; font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($userFullname) ?></div>
                            <div style="font-size: 0.7rem; color: var(--color-text-muted);"><?= $userRole === 'superadmin' ? 'Super Admin' : 'เจ้าหน้าที่' ?></div>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <div class="user-dropdown-menu" id="user_dropdown_menu">
                        <div style="padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--color-border); margin-bottom: 0.25rem;">
                            <div style="font-size: 0.825rem; font-weight: 600;"><?= htmlspecialchars($userFullname) ?></div>
                            <div style="font-size: 0.725rem; color: var(--color-text-muted);"><?= htmlspecialchars($userOrg) ?></div>
                        </div>
                        <a href="profile.php" class="dropdown-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>ข้อมูลส่วนตัว (Profile)</span>
                        </a>
                        <?php if ($userRole === 'superadmin'): ?>
                            <a href="admin.php" class="dropdown-item">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h6l-5 4 2 7-6-4-6 4 2-7-5-4h6z"></path></svg>
                                <span>จัดการผู้ใช้งานระบบ</span>
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="api/auth.php?action=logout" class="dropdown-item danger">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>ออกจากระบบ</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="app-content" id="main-content" tabindex="-1">
<?php else: ?>
    <!-- Public Header for non-logged-in views (e.g. register) -->
    <header class="app-topbar" style="border-bottom: 1.5px solid var(--color-border); background-color: var(--color-surface); height: 74px;">
        <div style="max-width: 1280px; width: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 1.25rem;">
            <a href="index.php" style="display: flex; align-items: center; gap: 0.95rem; text-decoration: none; color: var(--color-text);">
                <img src="logo.png" alt="ตรากระทรวงสาธารณสุข" class="brand-logo-img" style="width: 50px; height: 50px;">
                <div>
                    <div style="font-family: var(--font-heading); font-weight: 700; font-size: 1.15rem; color: var(--color-text); line-height: 1.25;">
                        ระบบบริหารจัดการข้อมูลและสถานการณ์อุทกภัย
                    </div>
                    <div style="font-size: 0.9rem; color: var(--color-text-secondary); font-weight: 600;">
                        สำนักงานสาธารณสุขจังหวัดอ่างทอง
                    </div>
                </div>
            </a>
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <a href="onepage.php" class="btn btn-sm btn-outline">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span>OnePage สรุปสถานการณ์</span>
                </a>
                <a href="index.php" class="btn btn-sm btn-primary">เข้าสู่ระบบ</a>
            </div>
        </div>
    </header>
    <main id="main-content" style="padding: 2.5rem 1rem;" tabindex="-1">
<?php endif; ?>
