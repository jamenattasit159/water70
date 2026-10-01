<?php
// dashboard.php - User Portal & Provincial Civil Dashboard
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

<div class="container">
    <!-- Welcome Banner -->
    <div class="gov-card" style="margin-bottom: 2rem; border-left: 5px solid var(--navy-800); background: linear-gradient(to right, #ffffff, #f8fafc);">
        <div class="gov-card-body" style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                        <span class="badge badge-approved" style="font-size: 0.85rem;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            สิทธิ์การใช้งาน: ผ่านการอนุมัติแล้ว
                        </span>
                        <?php if ($user['role'] === 'superadmin'): ?>
                            <span class="badge badge-role-superadmin">★ ผู้ดูแลระบบระดับสูง (Super Admin)</span>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <span class="badge badge-role-superadmin" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">ผู้ดูแลภาพรวมจังหวัด (Provincial Admin)</span>
                        <?php else: ?>
                            <span class="badge badge-role-user">เจ้าหน้าที่ประจำหน่วยงาน (Health Staff)</span>
                        <?php endif; ?>
                    </div>

                    <h1 style="font-size: 1.75rem; color: var(--navy-900); margin-bottom: 0.35rem;">
                        ยินดีต้อนรับ, <?= htmlspecialchars($user['fullname']) ?>
                    </h1>
                    <div style="color: var(--slate-600); font-size: 1rem;">
                        สังกัด: <strong><?= htmlspecialchars($user['organization_name'] ?: 'ไม่ระบุ') ?></strong> • 
                        พื้นที่: <strong><?= htmlspecialchars($user['district_name'] ?: 'ไม่ระบุ') ?></strong> • จังหวัดอ่างทอง
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <?php if (in_array($user['role'], ['admin', 'superadmin'])): ?>
                        <a href="provincial_overview.php" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border-color: #0369a1;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                            <span>ภาพรวมทั้งจังหวัดวันนี้</span>
                        </a>
                    <?php endif; ?>
                    <a href="onepage.php" class="btn btn-warning" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-color: #d97706; color: #ffffff;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                        <span>OnePage Infographic</span>
                    </a>
                    <?php if ($user['role'] === 'superadmin'): ?>
                        <a href="admin.php" class="btn btn-outline" style="border-color: #d97706; color: #92400e;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h6l-5 4 2 7-6-4-6 4 2-7-5-4h6z"></path></svg>
                            <span>จัดการผู้ใช้ (CRUD)</span>
                        </a>
                    <?php endif; ?>
                    <a href="report_entry.php" class="btn btn-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        <span>กรอกรายงาน ข้อ 1-2</span>
                    </a>
                    <a href="medical_services.php" class="btn btn-primary" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                        <span>บันทึกบริการแพทย์ ข้อ 3</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Provincial Quick Snippet (If Admin/Superadmin) -->
    <?php if ($provincialStats): ?>
        <div class="gov-card" style="margin-bottom: 2rem; border-top: 3px solid #0284c7;">
            <div class="gov-card-header" style="background: linear-gradient(to right, #f0f9ff, #e0f2fe);">
                <div class="gov-card-title" style="color: #0369a1;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    <span>สรุปสถานการณ์อุทกภัยระดับจังหวัด ประจำวันนี้ (<?= date('d/m/Y') ?>)</span>
                </div>
                <a href="provincial_overview.php" class="btn btn-sm btn-primary">ดูรายละเอียดเต็ม &rarr;</a>
            </div>
            <div class="gov-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; text-align: center;">
                    <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #bae6fd;">
                        <div style="font-size: 1.6rem; font-weight: 700; color: #0369a1;"><?= $provincialStats['reported'] ?> / <?= $provincialStats['total'] ?></div>
                        <div style="font-size: 0.85rem; color: #64748b;">สถานบริการส่งรายงานแล้ว (แห่ง)</div>
                    </div>
                    <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #bae6fd;">
                        <div style="font-size: 1.6rem; font-weight: 700; color: <?= $provincialStats['affected'] > 0 ? '#b91c1c' : '#059669' ?>;"><?= $provincialStats['affected'] ?> แห่ง</div>
                        <div style="font-size: 0.85rem; color: #64748b;">สถานบริการได้รับผลกระทบ</div>
                    </div>
                    <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #bae6fd;">
                        <div style="font-size: 1.6rem; font-weight: 700; color: #d97706;"><?= number_format($provincialStats['flooded_vulnerable']) ?> ราย</div>
                        <div style="font-size: 0.85rem; color: #64748b;">กลุ่มเปราะบางในพื้นที่น้ำท่วม</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Overview Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Flood Disaster & Water Management Widget -->
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
                    <span>แบบรายงานสถานการณ์อุทกภัย 2569</span>
                </div>
                <?php if ($todayReport): ?>
                    <span class="badge badge-approved">ส่งรายงานแล้ว</span>
                <?php else: ?>
                    <span class="badge badge-pending">รอส่งรายงาน</span>
                <?php endif; ?>
            </div>
            <div class="gov-card-body">
                <?php if ($todayReport): ?>
                    <div class="gov-alert success" style="margin-bottom: 1.25rem;">
                        <div class="gov-alert-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <div>
                            <strong>หน่วยงานของท่านส่งรายงานประจำวันนี้เรียบร้อยแล้ว</strong><br>
                            <span style="font-size: 0.85rem;">สถานะการบริการ: <?= $todayReport['service_status'] === 'normal' ? 'เปิดบริการปกติ' : ($todayReport['service_status'] === 'partial' ? 'เปิดบางส่วน' : 'ปิดบริการ') ?> • บันทึกเมื่อ: <?= $todayReport['updated_at'] ?: $todayReport['created_at'] ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="gov-alert warning" style="margin-bottom: 1.25rem;">
                        <div class="gov-alert-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div>
                            <strong>ยังไม่ได้บันทึกรายงานประจำวันนี้ (<?= date('d/m/Y') ?>)</strong><br>
                            <span style="font-size: 0.85rem;">ขอความร่วมมือบันทึกข้อมูลสถานบริการและกลุ่มเปราะบางภายในเวลา 15.00 น.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                        <span style="color: var(--slate-500);">รอบการรายงานประจำวัน:</span>
                        <strong style="color: var(--navy-900);">ภายในเวลา 14.00 - 15.00 น.</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                        <span style="color: var(--slate-500);">หน่วยงานรับผิดชอบ:</span>
                        <strong style="color: var(--navy-900);"><?= htmlspecialchars($user['organization_name']) ?></strong>
                    </div>
                </div>

                <a href="report_entry.php" class="btn btn-primary btn-block">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    <span><?= $todayReport ? 'เปิดดู / แก้ไขรายงานประจำวัน' : 'เข้าสู่ระบบบันทึกรายงานสถานการณ์' ?></span>
                </a>
            </div>
        </div>

        <!-- 3. Medical Services Widget -->
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #0284c7;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>3. การให้บริการด้านการแพทย์และสาธารณสุข</span>
                </div>
                <?php if ($todayMedServicesCount > 0): ?>
                    <span class="badge badge-approved">บันทึกแล้ว <?= $todayMedServicesCount ?> รายการ</span>
                <?php else: ?>
                    <span class="badge badge-pending">ยังไม่มีบันทึกวันนี้</span>
                <?php endif; ?>
            </div>
            <div class="gov-card-body">
                <p style="font-size: 0.875rem; color: var(--slate-600); margin-bottom: 1rem;">
                    บันทึกข้อมูลการออกหน่วยบริการแพทย์เคลื่อนที่, ทีม MCATT, SRRT, การเยี่ยมบ้าน, การคัดกรองสุขภาพจิต, 13 กลุ่มโรคที่พบบ่อย และการแจกจ่ายเวชภัณฑ์
                </p>
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 1.25rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span style="color: #0369a1;">รายการออกหน่วยวันนี้:</span>
                        <strong style="color: #0c4a6e;"><?= $todayMedServicesCount ?> ครั้ง / จุดบริการ</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #0369a1;">ครอบคลุมหัวข้อ:</span>
                        <span style="color: #0c4a6e;">ทีมปฏิบัติการ, บริการ, สุขภาพจิต, โรค, เวชภัณฑ์</span>
                    </div>
                </div>
                <a href="medical_services.php" class="btn btn-primary btn-block" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"></path></svg>
                    <span>บันทึก / จัดการบริการแพทย์ (ส่วนที่ 3)</span>
                </a>
            </div>
        </div>

        <!-- Executive OnePage Dashboard Widget -->
        <div class="gov-card">
            <div class="gov-card-header" style="background: linear-gradient(to right, #fffbeb, #fef3c7);">
                <div class="gov-card-title" style="color: #92400e;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    <span>OnePage รายงานสถานการณ์ (ผู้บริหาร)</span>
                </div>
                <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">Infographic</span>
            </div>
            <div class="gov-card-body">
                <p style="font-size: 0.875rem; color: var(--slate-600); margin-bottom: 1rem;">
                    แดชบอร์ดสรุปสถานการณ์อุทกภัยด้านการแพทย์และสาธารณสุขจังหวัดอ่างทอง อ้างอิงรูปแบบ OnePage สสจ.อ่างทอง (สถิติน้ำท่วม แผนที่ กลุ่มเปราะบาง บริการ และโรค)
                </p>
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #78350f;">
                    ★ รองรับการสั่งพิมพ์ / ส่งออกไฟล์เอกสารสรุปขนาด A4 สำหรับการประชุม EOC ประจำวัน
                </div>
                <a href="onepage.php" class="btn btn-warning btn-block" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; border-color: #d97706;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span>เปิด OnePage Infographic รายงานสถานการณ์</span>
                </a>
            </div>
        </div>

        <!-- Official Profile Summary Widget -->
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>ข้อมูลทะเบียนผู้ปฏิบัติงาน</span>
                </div>
                <a href="profile.php" style="font-size: 0.85rem;">แก้ไข</a>
            </div>
            <div class="gov-card-body">
                <table style="width: 100%; font-size: 0.925rem; line-height: 2;">
                    <tr>
                        <td style="color: var(--slate-500); width: 35%;">ชื่อผู้ใช้งาน:</td>
                        <td style="font-weight: 600; color: var(--navy-900);">@<?= htmlspecialchars($user['username']) ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--slate-500);">ชื่อ-สกุล:</td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($user['fullname']) ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--slate-500);">เบอร์โทรศัพท์:</td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($user['phone']) ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--slate-500);">อำเภอ:</td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($user['district_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="color: var(--slate-500);">หน่วยงาน:</td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($user['organization_name']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
