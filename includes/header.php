<?php
// includes/header.php - Top Bar & Official Government Navigation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'user';
$userFullname = $_SESSION['fullname'] ?? '';
$userDistrict = $_SESSION['district_name'] ?? '';
$pageTitle = $pageTitle ?? 'ระบบบริหารจัดการข้อมูลและผู้ปฏิบัติงานระดับจังหวัด';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23d97706'><circle cx='12' cy='12' r='10'/><path fill='%23ffffff' d='M12 2L15 9H21L16 13L18 20L12 16L6 20L8 13L3 9H9L12 2Z'/></svg>">
</head>
<body>

<header class="gov-topbar">
    <div class="gov-topbar-inner">
        <a href="<?= $isLoggedIn ? ($userRole === 'superadmin' ? 'admin.php' : 'dashboard.php') : 'index.php' ?>" class="brand-wrapper">
            <div class="brand-emblem" title="ตราสัญลักษณ์ระบบงานบริหารส่วนภูมิภาค">
                <svg viewBox="0 0 24 24">
                    <!-- Stylized Garuda / Royal Provincial Crest Symbol -->
                    <path d="M12 2L14.5 7.5L20 8.5L16 12.5L17 18L12 15L7 18L8 12.5L4 8.5L9.5 7.5L12 2Z"/>
                    <circle cx="12" cy="12" r="2.5" fill="#0f2942"/>
                </svg>
            </div>
            <div class="brand-info">
                <span class="brand-title">ระบบบริหารจัดการข้อมูลและสถานการณ์อุทกภัยระดับจังหวัด</span>
                <span class="brand-subtitle">จังหวัดอ่างทอง • สำนักงานจังหวัดและหน่วยงานสาธารณสุขในพื้นที่</span>
            </div>
        </a>

        <nav class="topbar-nav">
            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'superadmin'): ?>
                    <a href="admin.php" class="btn btn-sm" style="background: rgba(217, 119, 6, 0.25); color: #fef3c7; border: 1px solid rgba(245, 158, 11, 0.5);" title="จัดการผู้ใช้งาน">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h6l-5 4 2 7-6-4-6 4 2-7-5-4h6z"></path></svg>
                        <span>จัดการผู้ใช้</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'superadmin'])): ?>
                    <a href="provincial_overview.php" class="btn btn-sm" style="background: rgba(2, 132, 199, 0.25); color: #bae6fd; border: 1px solid rgba(56, 189, 248, 0.5);" title="ภาพรวมจังหวัด">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                        <span>ภาพรวมจังหวัด</span>
                    </a>
                <?php endif; ?>

                <a href="report_entry.php" class="btn btn-sm" style="color: #ffffff; background: rgba(16, 185, 129, 0.25); border: 1px solid rgba(16, 185, 129, 0.5);" title="บันทึกข้อ 1-2 ผลกระทบและกลุ่มเปราะบาง">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    <span>ข้อ 1-2 รายงานอุทกภัย</span>
                </a>

                <a href="medical_services.php" class="btn btn-sm" style="color: #ffffff; background: rgba(14, 165, 233, 0.25); border: 1px solid rgba(56, 189, 248, 0.5);" title="บันทึกข้อ 3 บริการแพทย์และสาธารณสุข">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>ข้อ 3 บริการแพทย์</span>
                </a>

                <a href="onepage.php" class="btn btn-sm" style="color: #ffffff; background: rgba(245, 158, 11, 0.25); border: 1px solid rgba(245, 158, 11, 0.5);" title="OnePage สรุปสถานการณ์อุทกภัย">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span>OnePage</span>
                </a>

                <a href="dashboard.php" class="btn btn-sm btn-outline" style="color: #ffffff; background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <span>แดชบอร์ด</span>
                </a>

                <div class="user-badge-nav">
                    <div class="user-avatar-circle" style="background: <?= $userRole === 'superadmin' ? '#d97706' : ($userRole === 'admin' ? '#0284c7' : '#059669') ?>;">
                        <?= mb_substr($userFullname, 0, 1, 'UTF-8') ?>
                    </div>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-weight: 600;"><?= htmlspecialchars($userFullname) ?></span>
                        <span style="font-size: 0.75rem; opacity: 0.85;">
                            <?= $userRole === 'superadmin' ? 'Super Admin' : ($userRole === 'admin' ? 'Admin จังหวัด' : htmlspecialchars($userDistrict)) ?>
                        </span>
                    </div>
                </div>

                <a href="profile.php" class="btn btn-sm" style="color: #ffffff; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18);" title="โปรไฟล์ของฉัน">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>

                <a href="api/auth.php?action=logout" class="btn btn-sm btn-danger" title="ออกจากระบบ">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    <span>ออก</span>
                </a>
            <?php else: ?>
                <a href="onepage.php" class="btn btn-sm" style="color: #ffffff; background: rgba(245, 158, 11, 0.25); border: 1px solid rgba(245, 158, 11, 0.5);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span>OnePage สถานการณ์อุทกภัย</span>
                </a>
                <a href="index.php" class="btn btn-sm" style="color: #ffffff; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <span>เข้าสู่ระบบ</span>
                </a>
                <a href="register.php" class="btn btn-sm btn-success">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <span>ลงทะเบียนใช้งาน</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
