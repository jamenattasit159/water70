<?php
// dashboard.php - Modern SaaS User Portal & Civil Dashboard (Linear/Vercel Style)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login(); // Requires active approved session

$user = current_user();
$todayDate = date('Y-m-d');

// Check today's submission status for user's facility
$todayReport = null;
$todayMedServicesCount = 0;
if (!empty($user['organization_id'])) {
    $stmtCheck = $pdo->prepare("SELECT id, impact_status, service_status, bedridden_flooded, elderly_flooded, disabled_flooded, people_served, created_at, updated_at FROM flood_reports WHERE organization_id = ? AND report_date = ?");
    $stmtCheck->execute([$user['organization_id'], $todayDate]);
    $todayReport = $stmtCheck->fetch();

    $stmtMedCheck = $pdo->prepare("SELECT COUNT(*) FROM flood_medical_services WHERE organization_id = ? AND report_date = ?");
    $stmtMedCheck->execute([$user['organization_id'], $todayDate]);
    $todayMedServicesCount = (int)$stmtMedCheck->fetchColumn();
}

// For Admin/Superadmin: quick provincial summary preview
$provincialStats = null;
if (in_array($user['role'], ['admin', 'superadmin'])) {
    $totalFac = $pdo->query("SELECT COUNT(*) FROM organizations WHERE type IN ('hospital', 'health_office', 'health_center', 'provincial_office')")->fetchColumn();
    $reportedFac = $pdo->query("SELECT COUNT(DISTINCT organization_id) FROM flood_reports WHERE report_date = '{$todayDate}'")->fetchColumn();
    $affectedFac = $pdo->query("SELECT COUNT(DISTINCT organization_id) FROM flood_reports WHERE report_date = '{$todayDate}' AND impact_status = 'affected'")->fetchColumn();
    $floodedVulnerable = $pdo->query("SELECT COALESCE(SUM(bedridden_flooded + dialysis_flooded + psychiatric_flooded + elderly_flooded + disabled_flooded + ncd_flooded + pregnant_flooded + children_flooded), 0) FROM flood_reports WHERE report_date = '{$todayDate}'")->fetchColumn();

    $provincialStats = [
        'total' => $totalFac,
        'reported' => $reportedFac,
        'affected' => $affectedFac,
        'flooded_vulnerable' => $floodedVulnerable
    ];
}

$pageTitle = 'หน้าหลักผู้ปฏิบัติงาน - ระบบบริหารจัดการข้อมูลระดับจังหวัด';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-body" style="padding: 1.75rem 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.65rem; flex-wrap: wrap;">
                    <span class="badge badge-success">
                        <span class="badge-dot"></span>
                        <span>สิทธิ์การใช้งาน: ผ่านการอนุมัติแล้ว</span>
                    </span>
                    <?php if ($user['role'] === 'superadmin'): ?>
                        <span class="badge badge-info">Super Admin</span>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <span class="badge badge-info">Admin จังหวัด</span>
                    <?php else: ?>
                        <span class="badge" style="background: var(--color-surface-hover); color: var(--color-text-secondary);">เจ้าหน้าที่หน่วยงาน</span>
                    <?php endif; ?>
                </div>

                <h1 style="font-size: 2rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.35rem;">
                    ยินดีต้อนรับ, <?= htmlspecialchars($user['fullname']) ?>
                </h1>
                <div style="color: var(--color-text-secondary); font-size: 1.05rem;">
                    สังกัด: <strong style="color: var(--color-text); font-weight: 700;"><?= htmlspecialchars($user['organization_name'] ?: 'ไม่ระบุ') ?></strong> &bull; 
                    พื้นที่: <strong style="color: var(--color-text); font-weight: 700;"><?= htmlspecialchars($user['district_name'] ?: 'ไม่ระบุ') ?></strong> &bull; จังหวัดอ่างทอง
                </div>
            </div>

            <!-- Header Quick Actions (Disciplined primary button + secondary outline) -->
            <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
                <a href="report_entry.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span>กรอกรายงาน ข้อ 1-2</span>
                </a>

                <a href="medical_services.php" class="btn btn-outline">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                    <span>บริการแพทย์ ข้อ 3</span>
                </a>

                <a href="onepage.php" class="btn btn-outline" target="_blank">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span>OnePage</span>
                </a>

                <?php if (in_array($user['role'], ['admin', 'superadmin'])): ?>
                    <a href="provincial_overview.php" class="btn btn-ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <span>ภาพรวมจังหวัด</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Admin Provincial Quick Snippet (If Admin/Superadmin) -->
<?php if ($provincialStats): ?>
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <div style="font-size: 0.95rem; font-weight: 600; color: var(--color-text); display: flex; align-items: center; gap: 0.45rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>ภาพรวมสถานการณ์อุทกภัยระดับจังหวัด วันนี้ (<?= date('d/m/Y') ?>)</span>
            </div>
            <a href="provincial_overview.php" class="btn btn-sm btn-outline">ดูรายงานฉบับเต็ม &rarr;</a>
        </div>

        <div class="stats-grid" style="margin-bottom: 0;">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?= $provincialStats['reported'] ?> <span style="font-size: 1rem; font-weight: 400; color: var(--color-text-muted);">/ <?= $provincialStats['total'] ?></span></div>
                    <div class="stat-label">สถานบริการส่งรายงานแล้ว (แห่ง)</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon <?= $provincialStats['affected'] > 0 ? 'danger' : 'success' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="stat-details">
                    <div class="stat-value" style="color: <?= $provincialStats['affected'] > 0 ? 'var(--color-danger-text)' : 'var(--color-success-text)' ?>;">
                        <?= $provincialStats['affected'] ?> แห่ง
                    </div>
                    <div class="stat-label">สถานบริการได้รับผลกระทบ</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="stat-details">
                    <div class="stat-value" style="color: var(--color-warning-text);"><?= number_format($provincialStats['flooded_vulnerable']) ?> ราย</div>
                    <div class="stat-label">กลุ่มเปราะบางในพื้นที่น้ำท่วม</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Overview Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- 1. Flood Disaster & Water Management Widget -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                </svg>
                <div class="card-title">แบบรายงานสถานการณ์อุทกภัย 2569</div>
            </div>
            <?php if ($todayReport): ?>
                <span class="badge badge-success"><span class="badge-dot"></span> ส่งรายงานแล้ว</span>
            <?php else: ?>
                <span class="badge badge-warning"><span class="badge-dot"></span> รอส่งรายงาน</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($todayReport): ?>
                <div class="gov-alert success" style="margin-bottom: 1.15rem;">
                    <div class="gov-alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div>
                        <strong style="font-weight: 600;">ส่งรายงานประจำวันนี้เรียบร้อยแล้ว</strong><br>
                        <span style="font-size: 0.825rem;">สถานะการบริการ: <?= $todayReport['service_status'] === 'normal' ? 'เปิดบริการปกติ' : ($todayReport['service_status'] === 'partial' ? 'เปิดบางส่วน' : 'ปิดบริการ') ?> &bull; บันทึกเมื่อ: <?= $todayReport['updated_at'] ?: $todayReport['created_at'] ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="gov-alert warning" style="margin-bottom: 1.15rem;">
                    <div class="gov-alert-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <div>
                        <strong style="font-weight: 600;">ยังไม่ได้บันทึกรายงานประจำวันนี้ (<?= date('d/m/Y') ?>)</strong><br>
                        <span style="font-size: 0.825rem;">ขอความร่วมมือบันทึกข้อมูลสถานบริการและกลุ่มเปราะบางภายในเวลา 15.00 น.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div style="background: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem; font-size: 0.85rem;">
                    <span style="color: var(--color-text-muted);">รอบการรายงานประจำวัน:</span>
                    <strong style="color: var(--color-text); font-weight: 500;">ภายในเวลา 14.00 - 15.00 น.</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                    <span style="color: var(--color-text-muted);">หน่วยงานรับผิดชอบ:</span>
                    <strong style="color: var(--color-text); font-weight: 500;"><?= htmlspecialchars($user['organization_name']) ?></strong>
                </div>
            </div>

            <a href="report_entry.php" class="btn <?= $todayReport ? 'btn-outline' : 'btn-primary' ?> btn-block">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span><?= $todayReport ? 'เปิดดู / แก้ไขรายงานประจำวัน' : 'เข้าสู่ระบบบันทึกรายงานสถานการณ์' ?></span>
            </a>
        </div>
    </div>

    <!-- 2. Medical Services Widget -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
                <div class="card-title">3. บริการด้านการแพทย์และสาธารณสุข</div>
            </div>
            <?php if ($todayMedServicesCount > 0): ?>
                <span class="badge badge-success"><span class="badge-dot"></span> บันทึกแล้ว <?= $todayMedServicesCount ?> รายการ</span>
            <?php else: ?>
                <span class="badge badge-warning"><span class="badge-dot"></span> ยังไม่มีบันทึกวันนี้</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1rem; line-height: 1.5;">
                บันทึกการออกหน่วยแพทย์เคลื่อนที่, ทีม MCATT, SRRT, เยี่ยมบ้าน, คัดกรองสุขภาพจิต, 13 กลุ่มโรคที่พบบ่อย และการแจกจ่ายเวชภัณฑ์
            </p>
            <div style="background: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                    <span style="color: var(--color-text-muted);">รายการออกหน่วยวันนี้:</span>
                    <strong style="color: var(--color-text); font-weight: 500;"><?= $todayMedServicesCount ?> ครั้ง / จุดบริการ</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">ครอบคลุมหัวข้อ:</span>
                    <span style="color: var(--color-text); font-weight: 500;">ทีมปฏิบัติการ, สุขภาพจิต, โรค, เวชภัณฑ์</span>
                </div>
            </div>
            <a href="medical_services.php" class="btn btn-outline btn-block">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>บันทึก / จัดการบริการแพทย์ (ส่วนที่ 3)</span>
            </a>
        </div>
    </div>

    <!-- 3. Executive OnePage Dashboard Widget -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <div class="card-title">OnePage รายงานสถานการณ์ (ผู้บริหาร)</div>
            </div>
            <span class="badge badge-info">Infographic</span>
        </div>
        <div class="card-body">
            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 1rem; line-height: 1.5;">
                แดชบอร์ดสรุปสถานการณ์อุทกภัยด้านการแพทย์และสาธารณสุขจังหวัดอ่างทอง อ้างอิงรูปแบบ OnePage สสจ.อ่างทอง (สถิติ แผนที่ กลุ่มเปราะบาง บริการ และโรค)
            </p>
            <div style="background: var(--color-surface-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--color-text-secondary);">
                &bull; รองรับการสั่งพิมพ์ / ส่งออกไฟล์เอกสารสรุปขนาด A4 สำหรับการประชุม EOC ประจำวัน
            </div>
            <a href="onepage.php" class="btn btn-outline btn-block" target="_blank">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                <span>เปิด OnePage Infographic รายงานสถานการณ์</span>
            </a>
        </div>
    </div>

    <!-- 4. Official Profile Summary Widget -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <div class="card-title">ข้อมูลทะเบียนผู้ปฏิบัติงาน</div>
            </div>
            <a href="profile.php" class="btn btn-sm btn-ghost" style="color: var(--color-primary);">แก้ไขข้อมูล</a>
        </div>
        <div class="card-body">
            <table style="width: 100%; font-size: 0.875rem; line-height: 2;">
                <tr>
                    <td style="color: var(--color-text-muted); width: 35%;">ชื่อผู้ใช้งาน:</td>
                    <td style="font-weight: 500; color: var(--color-text);">@<?= htmlspecialchars($user['username']) ?></td>
                </tr>
                <tr>
                    <td style="color: var(--color-text-muted);">ชื่อ-สกุล:</td>
                    <td style="font-weight: 500; color: var(--color-text);"><?= htmlspecialchars($user['fullname']) ?></td>
                </tr>
                <tr>
                    <td style="color: var(--color-text-muted);">เบอร์โทรศัพท์:</td>
                    <td style="font-weight: 500; color: var(--color-text);"><?= htmlspecialchars($user['phone']) ?></td>
                </tr>
                <tr>
                    <td style="color: var(--color-text-muted);">อำเภอ:</td>
                    <td style="font-weight: 500; color: var(--color-text);"><?= htmlspecialchars($user['district_name']) ?></td>
                </tr>
                <tr>
                    <td style="color: var(--color-text-muted);">หน่วยงาน:</td>
                    <td style="font-weight: 500; color: var(--color-text);"><?= htmlspecialchars($user['organization_name']) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
