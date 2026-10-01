<?php
// medical_services.php - แบบฟอร์มส่วนที่ 3: การให้บริการด้านการแพทย์และสาธารณสุข
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$currentUser = current_user();
$pageTitle = 'แบบบันทึกบริการแพทย์ & สาธารณสุข (ส่วนที่ 3) - จังหวัดอ่างทอง';

$todayDate = date('Y-m-d');
$selectedDate = trim($_GET['report_date'] ?? $todayDate);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $todayDate;
}

// Facility info
$userOrgId = $currentUser['organization_id'];
$userDistrictId = $currentUser['district_id'];

// If Admin/Superadmin, load facility list for dropdown selector
$facilities = [];
if (in_array($currentUser['role'], ['admin', 'superadmin'])) {
    $stmtF = $pdo->query("SELECT o.id, o.name_th, d.name_th as district_name FROM organizations o JOIN districts d ON o.district_id = d.id ORDER BY d.id, o.name_th");
    $facilities = $stmtF->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="gov-container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Breadcrumb & Top bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 2px;">
                <a href="dashboard.php" style="color: var(--blue-600); text-decoration: none;">หน้าหลัก</a> &gt;
                <span>3. การให้บริการด้านการแพทย์และสาธารณสุข</span>
            </div>
            <h1 class="page-title" style="margin: 0; font-size: 1.45rem; color: var(--navy-900); display: flex; align-items: center; gap: 0.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: #0284c7;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                <span>3. การให้บริการด้านการแพทย์และสาธารณสุข (การออกหน่วยและบริการในพื้นที่)</span>
            </h1>
            <p style="font-size: 0.9rem; color: var(--slate-600); margin: 0.2rem 0 0 0;">
                บันทึกการออกหน่วยแพทย์เคลื่อนที่, MCATT, การเยี่ยมบ้าน, การคัดกรองสุขภาพจิต, โรคที่พบบ่อย และการแจกจ่ายเวชภัณฑ์
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="report_entry.php?report_date=<?= urlencode($selectedDate) ?>" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>รายงานกลุ่มเปราะบาง (ส่วน 1-2)</span>
            </a>
            <a href="onepage.php?report_date=<?= urlencode($selectedDate) ?>" class="btn btn-sm" style="background: linear-gradient(135deg, #0f2c4c 0%, #1e3a8a 100%); color: #ffffff; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: var(--shadow-sm);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <span>ดู OnePage สถานการณ์อุทกภัย</span>
            </a>
        </div>
    </div>

    <!-- Filter and Control Bar -->
    <div class="gov-card" style="margin-bottom: 1.5rem; padding: 1.1rem 1.35rem; background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
                <div>
                    <label for="filter_date" style="font-size: 0.85rem; font-weight: 600; color: var(--navy-900); display: block; margin-bottom: 3px;">
                        📅 วันที่ของรายงาน:
                    </label>
                    <input type="date" id="filter_date" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" style="font-weight: 600;">
                </div>

                <?php if (in_array($currentUser['role'], ['admin', 'superadmin'])): ?>
                    <div>
                        <label for="filter_org" style="font-size: 0.85rem; font-weight: 600; color: var(--navy-900); display: block; margin-bottom: 3px;">
                            🏥 หน่วยงานสังกัด:
                        </label>
                        <select id="filter_org" class="form-control form-control-sm" style="min-width: 250px;">
                            <option value="">-- แสดงทุกหน่วยงานในจังหวัด --</option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= ($userOrgId == $f['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['district_name']) ?> : <?= htmlspecialchars($f['name_th']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div>
                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--navy-900); display: block; margin-bottom: 3px;">สังกัด:</span>
                        <div class="badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-size: 0.875rem;">
                            <?= htmlspecialchars($currentUser['organization_name']) ?> (<?= htmlspecialchars($currentUser['district_name']) ?>)
                        </div>
                    </div>
                <?php endif; ?>

                <div style="align-self: flex-end;">
                    <button type="button" class="btn btn-sm btn-outline" id="btn_refresh_list" style="margin-bottom: 1px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        <span>รีเฟรช</span>
                    </button>
                </div>
            </div>

            <div>
                <button type="button" class="btn btn-sm btn-success" id="btn_open_form_modal" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ บันทึกการออกหน่วย / บริการแพทย์</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards for the selected date -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;" id="kpi_grid">
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">ประชาชนรับบริการสะสม</div>
            <div class="stat-value" id="kpi_people_served" style="color: #0284c7; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">คน ในพื้นที่ออกหน่วย</div>
        </div>
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">ออกหน่วยแพทย์ / MCATT</div>
            <div class="stat-value" id="kpi_teams_visits" style="color: #7c3aed; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">ครั้งสะสม</div>
        </div>
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">ออกเยี่ยมบ้าน & แจกยา</div>
            <div class="stat-value" id="kpi_home_visits" style="color: #059669; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">ราย/หลัง</div>
        </div>
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">คัดกรองสุขภาพจิต (MCATT)</div>
            <div class="stat-value" id="kpi_mental_screened" style="color: #d97706; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">ราย (ปฐมพยาบาลใจ)</div>
        </div>
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">โรคน้ำกัดเท้าที่พบ</div>
            <div class="stat-value" id="kpi_athletes_foot" style="color: #dc2626; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">ราย (อันดับ 1)</div>
        </div>
        <div class="stat-card" style="padding: 1rem 1.25rem;">
            <div class="stat-label">ยารักษาน้ำกัดเท้าที่แจก</div>
            <div class="stat-value" id="kpi_foot_cream" style="color: #0f172a; font-size: 1.6rem;">0</div>
            <div style="font-size: 0.775rem; color: #64748b;">หลอด</div>
        </div>
    </div>

    <!-- Main Table Container: List of Section 3 Outreach Sessions -->
    <div class="gov-card">
        <div class="gov-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div class="gov-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>ตารางข้อมูล 3. การให้บริการด้านการแพทย์และสาธารณสุข ประจำวันที่ <span id="table_date_display"><?= htmlspecialchars($selectedDate) ?></span></span>
            </div>
            <span class="badge" id="badge_total_rows" style="background: #eff6ff; color: #1e40af;">0 รายการ</span>
        </div>

        <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
            <table class="gov-table" style="text-align: center; border-collapse: separate; font-size: 0.85rem;" id="services_table">
                <thead style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th rowspan="2" class="col-header-blue" style="width: 4%;">ที่</th>
                        <th rowspan="2" class="col-header-blue" style="width: 14%;">สถานบริการ / หน่วยงาน</th>
                        <th rowspan="2" class="col-header-blue" style="width: 12%;">สถานที่ออกหน่วย</th>
                        <th rowspan="2" class="col-header-blue" style="width: 10%;">หมู่ / ชุมชน</th>
                        <th rowspan="2" style="background: #dbeafe; color: #1e3a8a; font-weight: 700; width: 8%;">ปชช. รับบริการ<br>(คน)</th>
                        <th colspan="3" style="background: #fef08a; color: #854d0e; font-weight: 700;">ทีมปฏิบัติการ (ครั้ง)</th>
                        <th colspan="4" style="background: #d1fae5; color: #065f46; font-weight: 700;">การให้บริการด้านการแพทย์ (ราย)</th>
                        <th colspan="2" style="background: #fed7aa; color: #7c2d12; font-weight: 700;">สุขภาพจิต (ราย)</th>
                        <th colspan="2" style="background: #fce7f3; color: #831843; font-weight: 700;">โรคพบบ่อย (ราย)</th>
                        <th colspan="2" style="background: #e2e8f0; color: #334155; font-weight: 700;">ทรัพยากรแจก</th>
                        <th rowspan="2" style="width: 8%;">เวลาบันทึก<br>(NOW)</th>
                        <th rowspan="2" style="width: 8%;">การจัดการ</th>
                    </tr>
                    <tr>
                        <th style="background: #fef9c3; color: #854d0e; font-size: 0.75rem;">แพทย์เคลื่อนที่</th>
                        <th style="background: #fef9c3; color: #854d0e; font-size: 0.75rem;">MCATT</th>
                        <th style="background: #fef9c3; color: #854d0e; font-size: 0.75rem;">SRRT</th>

                        <th style="background: #ecfdf5; color: #065f46; font-size: 0.75rem;">ส่งยา</th>
                        <th style="background: #ecfdf5; color: #065f46; font-size: 0.75rem;">เยี่ยมบ้าน</th>
                        <th style="background: #ecfdf5; color: #065f46; font-size: 0.75rem;">แจกยา</th>
                        <th style="background: #ecfdf5; color: #065f46; font-size: 0.75rem;">สุขศึกษา</th>

                        <th style="background: #ffedd5; color: #7c2d12; font-size: 0.75rem;">คัดกรอง</th>
                        <th style="background: #ffedd5; color: #7c2d12; font-size: 0.75rem;">PFA</th>

                        <th style="background: #fdf2f8; color: #831843; font-size: 0.75rem;">น้ำกัดเท้า</th>
                        <th style="background: #fdf2f8; color: #831843; font-size: 0.75rem;">ผิวหนัง</th>

                        <th style="background: #f1f5f9; color: #334155; font-size: 0.75rem;">ยาน้ำกัดเท้า</th>
                        <th style="background: #f1f5f9; color: #334155; font-size: 0.75rem;">ยาชุดช่วย</th>
                    </tr>
                </thead>
                <tbody id="services_tbody">
                    <tr><td colspan="19" style="padding: 2.5rem; color: #64748b;">กำลังโหลดข้อมูล...</td></tr>
                </tbody>
                <tfoot id="services_tfoot" style="position: sticky; bottom: 0; z-index: 4; background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                    <!-- Sum totals -->
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: Form for Section 3 Medical Outreach
     ========================================== -->
<div class="modal-overlay" id="medical_form_modal">
    <div class="modal-dialog" style="max-width: 1000px; width: 95vw; max-height: 92vh;">
        <div class="modal-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #1e3a8a 100%); color: #ffffff;">
            <h3 class="modal-title" id="modal_form_title" style="color: #ffffff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                <span>แบบบันทึก 3. การให้บริการด้านการแพทย์และสาธารณสุข</span>
            </h3>
            <button type="button" class="modal-close" style="color: #ffffff; opacity: 0.85;">&times;</button>
        </div>

        <form id="form_medical_service">
            <input type="hidden" id="entry_id" name="id" value="0">
            <input type="hidden" id="entry_report_date" name="report_date" value="<?= htmlspecialchars($selectedDate) ?>">

            <div class="modal-body" style="padding: 1.25rem 1.5rem; overflow-y: auto; max-height: calc(92vh - 130px);">
                
                <!-- Facility & Location Section -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                    <h4 style="font-size: 0.95rem; color: #0f2c4c; margin: 0 0 0.75rem 0; font-weight: 700;">
                        📍 3.1 ข้อมูลพื้นที่และจุดออกหน่วยบริการ
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem;">
                        <?php if (in_array($currentUser['role'], ['admin', 'superadmin'])): ?>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="entry_org_id">หน่วยงานสังกัด *</label>
                                <select id="entry_org_id" name="organization_id" class="form-control" required>
                                    <?php foreach ($facilities as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= ($userOrgId == $f['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['district_name']) ?> : <?= htmlspecialchars($f['name_th']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="outreach_location">สถานที่ออกหน่วย (เช่น ต.มงคลธรรมนิมิต / ศาลาประชาคม) *</label>
                            <input type="text" id="outreach_location" name="outreach_location" class="form-control" placeholder="ระบุตำบลหรือจุดออกหน่วย" required>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="village_no">หมู่ที่ (เช่น 2)</label>
                            <input type="number" min="0" max="99" id="village_no" name="village_no" class="form-control" placeholder="ระบุเลขหมู่">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="village_name">ชื่อหมู่บ้าน / ชุมชน (เช่น บ้านหนองถ้ำ)</label>
                            <input type="text" id="village_name" name="village_name" class="form-control" placeholder="ระบุชื่อหมู่บ้านหรือชุมชน">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="people_served" style="color: #0284c7; font-weight: 700;">จำนวนประชาชนที่เข้ารับบริการ (คน) *</label>
                            <input type="number" min="0" id="people_served" name="people_served" class="form-control" value="0" style="font-weight: 700; border-color: #93c5fd;" required>
                        </div>
                    </div>
                </div>

                <!-- Accordion / Grid of Sections 3.2 to 3.7 -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    
                    <!-- 3.2 Operational Teams -->
                    <div style="background: #fffbeb; border: 1px solid #fef08a; border-radius: var(--radius-md); padding: 1rem;">
                        <h4 style="font-size: 0.95rem; color: #854d0e; margin: 0 0 0.65rem 0; font-weight: 700;">
                            🚑 3.2 ทีมปฏิบัติการ (จำนวนครั้ง)
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="team_mobile_clinic" style="font-size: 0.8rem;">หน่วยแพทย์เคลื่อนที่</label>
                                <input type="number" min="0" id="team_mobile_clinic" name="team_mobile_clinic" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="team_mcatt" style="font-size: 0.8rem;">ทีม MCATT (เยียวยาใจ)</label>
                                <input type="number" min="0" id="team_mcatt" name="team_mcatt" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="team_srrt" style="font-size: 0.8rem;">ทีม SRRT (สอบสวนโรค)</label>
                                <input type="number" min="0" id="team_srrt" name="team_srrt" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="team_shert" style="font-size: 0.8rem;">ทีม ShERT (ศูนย์พักพิง)</label>
                                <input type="number" min="0" id="team_shert" name="team_shert" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- 3.3 Medical Services -->
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-md); padding: 1rem;">
                        <h4 style="font-size: 0.95rem; color: #065f46; margin: 0 0 0.65rem 0; font-weight: 700;">
                            🩺 3.3 การให้บริการทางการแพทย์ (ราย)
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_chronic_meds" style="font-size: 0.8rem;">จัดส่งยาโรคประจำตัว</label>
                                <input type="number" min="0" id="service_chronic_meds" name="service_chronic_meds" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_home_visit" style="font-size: 0.8rem;">ออกเยี่ยมบ้าน</label>
                                <input type="number" min="0" id="service_home_visit" name="service_home_visit" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_med_dispense" style="font-size: 0.8rem;">รับยา / แจกยา</label>
                                <input type="number" min="0" id="service_med_dispense" name="service_med_dispense" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_health_edu" style="font-size: 0.8rem;">ให้บริการสุขศึกษา/แนะนำ</label>
                                <input type="number" min="0" id="service_health_edu" name="service_health_edu" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_treatment" style="font-size: 0.8rem;">ตรวจรักษา</label>
                                <input type="number" min="0" id="service_treatment" name="service_treatment" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="service_referral" style="font-size: 0.8rem;">การส่งต่อผู้ป่วย</label>
                                <input type="number" min="0" id="service_referral" name="service_referral" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3.4 Mental Health Screening -->
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                    <h4 style="font-size: 0.95rem; color: #9a3412; margin: 0 0 0.65rem 0; font-weight: 700;">
                        🧠 3.4 การคัดกรองสุขภาพจิต (MCATT) (ราย)
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_screened" style="font-size: 0.8rem;">ผู้รับการคัดกรอง</label>
                            <input type="number" min="0" id="mental_screened" name="mental_screened" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_high_stress" style="font-size: 0.8rem; color: #b91c1c;">เครียดสูง</label>
                            <input type="number" min="0" id="mental_high_stress" name="mental_high_stress" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_depression" style="font-size: 0.8rem; color: #b91c1c;">เสี่ยงซึมเศร้า</label>
                            <input type="number" min="0" id="mental_depression" name="mental_depression" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_suicide_risk" style="font-size: 0.8rem; color: #b91c1c;">เสี่ยงฆ่าตัวตาย</label>
                            <input type="number" min="0" id="mental_suicide_risk" name="mental_suicide_risk" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_help_pfa" style="font-size: 0.8rem; color: #0284c7;">ปฐมพยาบาลใจ (PFA)</label>
                            <input type="number" min="0" id="mental_help_pfa" name="mental_help_pfa" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="mental_help_doctor" style="font-size: 0.8rem;">พบแพทย์</label>
                            <input type="number" min="0" id="mental_help_doctor" name="mental_help_doctor" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                </div>

                <!-- 3.5 Top Diseases Found (13 Groups) -->
                <div style="background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                    <h4 style="font-size: 0.95rem; color: #831843; margin: 0 0 0.65rem 0; font-weight: 700;">
                        🦠 3.5 โรคที่พบบ่อยจากการออกหน่วยแพทย์เคลื่อนที่ (13 กลุ่มโรค) (ราย)
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.65rem;">
                        <div class="form-group" style="margin: 0; background: #fff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f472b6;">
                            <label class="form-label" for="disease_athletes_foot" style="font-size: 0.8rem; font-weight: 700; color: #9d174d;">6. โรคน้ำกัดเท้า ⭐</label>
                            <input type="number" min="0" id="disease_athletes_foot" name="disease_athletes_foot" class="form-control form-control-sm" value="0" style="font-weight: 700;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_skin" style="font-size: 0.8rem;">5. โรคผิวหนัง (แพ้/ผื่นคัน)</label>
                            <input type="number" min="0" id="disease_skin" name="disease_skin" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_muscle_pain" style="font-size: 0.8rem;">7. ปวดเมื่อยกล้ามเนื้อ</label>
                            <input type="number" min="0" id="disease_muscle_pain" name="disease_muscle_pain" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_headache" style="font-size: 0.8rem;">8. ปวดศีรษะ เวียนศีรษะ</label>
                            <input type="number" min="0" id="disease_headache" name="disease_headache" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_eye_infection" style="font-size: 0.8rem;">4. โรคตาอักเสบ / ตาแดง</label>
                            <input type="number" min="0" id="disease_eye_infection" name="disease_eye_infection" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_dengue" style="font-size: 0.8rem;">9. ไข้เลือดออก</label>
                            <input type="number" min="0" id="disease_dengue" name="disease_dengue" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_respiratory" style="font-size: 0.8rem;">1. ทางเดินหายใจ</label>
                            <input type="number" min="0" id="disease_respiratory" name="disease_respiratory" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_gastrointestinal" style="font-size: 0.8rem;">2. ทางเดินอาหาร</label>
                            <input type="number" min="0" id="disease_gastrointestinal" name="disease_gastrointestinal" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_circulatory" style="font-size: 0.8rem;">3. ไหลเวียนโลหิต</label>
                            <input type="number" min="0" id="disease_circulatory" name="disease_circulatory" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_diarrhea" style="font-size: 0.8rem;">10. อุจจาระร่วง/อาหารเป็นพิษ</label>
                            <input type="number" min="0" id="disease_diarrhea" name="disease_diarrhea" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_fatigue" style="font-size: 0.8rem;">11. เหนื่อย อ่อนเพลีย</label>
                            <input type="number" min="0" id="disease_fatigue" name="disease_fatigue" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_viral" style="font-size: 0.8rem;">12. ติดเชื้อไวรัส</label>
                            <input type="number" min="0" id="disease_viral" name="disease_viral" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_other" style="font-size: 0.8rem;">13. อื่นๆ ระบุ (ราย)</label>
                            <input type="number" min="0" id="disease_other" name="disease_other" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" for="disease_other_text" style="font-size: 0.8rem;">ระบุชื่อโรคอื่นๆ</label>
                            <input type="text" id="disease_other_text" name="disease_other_text" class="form-control form-control-sm" placeholder="เช่น แมลงสัตว์กัดต่อย">
                        </div>
                    </div>
                </div>

                <!-- 3.6 Water Accidents & 3.7 Resources Distribution -->
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1.25rem;">
                    
                    <!-- 3.6 Water Accidents -->
                    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: var(--radius-md); padding: 1rem;">
                        <h4 style="font-size: 0.95rem; color: #1e293b; margin: 0 0 0.65rem 0; font-weight: 700;">
                            ⚠️ 3.6 อุบัติเหตุทางน้ำ (ราย)
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="accident_drowning" style="font-size: 0.8rem; color: #b91c1c;">จมน้ำ (ราย)</label>
                                <input type="number" min="0" id="accident_drowning" name="accident_drowning" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="accident_electrocution" style="font-size: 0.8rem; color: #b91c1c;">ไฟช็อต (ราย)</label>
                                <input type="number" min="0" id="accident_electrocution" name="accident_electrocution" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- 3.7 Resources -->
                    <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: var(--radius-md); padding: 1rem;">
                        <h4 style="font-size: 0.95rem; color: #0f766e; margin: 0 0 0.65rem 0; font-weight: 700;">
                            📦 3.7 การสนับสนุนทรัพยากร & ศูนย์พักพิง
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.5rem;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_foot_cream" style="font-size: 0.8rem; font-weight: 600; color: #0f766e;">ยาน้ำกัดเท้า (หลอด)</label>
                                <input type="number" min="0" id="resource_foot_cream" name="resource_foot_cream" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_relief_kit" style="font-size: 0.8rem;">ยาชุดช่วยผู้ประสบภัย</label>
                                <input type="number" min="0" id="resource_relief_kit" name="resource_relief_kit" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_garbage_bag" style="font-size: 0.8rem;">ถุงดำ (ใบ)</label>
                                <input type="number" min="0" id="resource_garbage_bag" name="resource_garbage_bag" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_mosquito_repellent" style="font-size: 0.8rem;">ยากันยุง (ซอง/กล่อง)</label>
                                <input type="number" min="0" id="resource_mosquito_repellent" name="resource_mosquito_repellent" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_home_medicine" style="font-size: 0.8rem;">ยาสามัญประจำบ้าน</label>
                                <input type="number" min="0" id="resource_home_medicine" name="resource_home_medicine" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="resource_boots" style="font-size: 0.8rem;">รองเท้าบูท (คู่)</label>
                                <input type="number" min="0" id="resource_boots" name="resource_boots" class="form-control form-control-sm" value="0">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.5rem; margin-top: 0.5rem; border-top: 1px dashed #99f6e4; padding-top: 0.5rem;">
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="shelter_count" style="font-size: 0.8rem;">ศูนย์พักพิง (แห่ง)</label>
                                <input type="number" min="0" id="shelter_count" name="shelter_count" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="shelter_capacity" style="font-size: 0.8rem;">รองรับได้ (คน)</label>
                                <input type="number" min="0" id="shelter_capacity" name="shelter_capacity" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" for="shelter_occupants" style="font-size: 0.8rem;">ผู้เข้าพัก (คน)</label>
                                <input type="number" min="0" id="shelter_occupants" name="shelter_occupants" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporter & Notes -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 0.85rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="reporter_name" style="font-size: 0.8rem;">ชื่อ-สกุล ผู้รายงาน</label>
                        <input type="text" id="reporter_name" name="reporter_name" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['fullname']) ?>">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="reporter_phone" style="font-size: 0.8rem;">เบอร์โทรศัพท์ติดต่อ</label>
                        <input type="text" id="reporter_phone" name="reporter_phone" class="form-control form-control-sm" value="<?= htmlspecialchars($currentUser['phone']) ?>">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" for="notes" style="font-size: 0.8rem;">ข้อเสนอแนะ / ความต้องการสนับสนุน</label>
                        <input type="text" id="notes" name="notes" class="form-control form-control-sm" placeholder="เช่น ขอรับการสนับสนุนเรือท้องแบนและยาน้ำกัดเท้าเพิ่ม">
                    </div>
                </div>

            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 0.85rem 1.5rem;">
                <button type="button" class="btn btn-outline btn-modal-cancel">ยกเลิก</button>
                <button type="submit" class="btn btn-success" id="btn_save_service" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>บันทึกข้อมูลบริการ</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterDate = document.getElementById('filter_date');
    const filterOrg = document.getElementById('filter_org');
    const refreshBtn = document.getElementById('btn_refresh_list');
    const openModalBtn = document.getElementById('btn_open_form_modal');
    const modal = document.getElementById('medical_form_modal');
    const form = document.getElementById('form_medical_service');
    const tbody = document.getElementById('services_tbody');
    const tfoot = document.getElementById('services_tfoot');
    const badgeTotal = document.getElementById('badge_total_rows');
    const tableDateDisplay = document.getElementById('table_date_display');

    let currentServicesList = [];

    // Load services list
    function loadServices() {
        const dateStr = filterDate.value;
        const orgId = filterOrg ? filterOrg.value : '';
        tableDateDisplay.textContent = dateStr;

        tbody.innerHTML = '<tr><td colspan="19" style="padding: 2.5rem; color: #64748b;">กำลังโหลดข้อมูล...</td></tr>';

        fetch(`api/medical_services.php?action=list_services&report_date=${dateStr}&organization_id=${orgId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    currentServicesList = res.services || [];
                    renderTable(currentServicesList);
                    loadKPIs(dateStr, orgId);
                } else {
                    tbody.innerHTML = `<tr><td colspan="19" style="padding: 2.5rem; color: #dc2626;">เกิดข้อผิดพลาด: ${escapeHtml(res.message)}</td></tr>`;
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="19" style="padding: 2.5rem; color: #dc2626;">เกิดข้อผิดพลาด: ${escapeHtml(err.message)}</td></tr>`;
            });
    }

    // Render table
    function renderTable(services) {
        badgeTotal.textContent = `${services.length} รายการ`;

        if (services.length === 0) {
            tbody.innerHTML = '<tr><td colspan="19" style="padding: 3rem; text-align: center; color: #94a3b8;">ยังไม่มีข้อมูลการออกหน่วยบริการสำหรับวันนี้ กดปุ่ม "+ บันทึกการออกหน่วย" เพื่อเพิ่มข้อมูล</td></tr>';
            tfoot.innerHTML = '';
            return;
        }

        let html = '';
        let totPeople = 0, totMobile = 0, totMcatt = 0, totSrrt = 0;
        let totChronic = 0, totHome = 0, totDisp = 0, totEdu = 0;
        let totScreen = 0, totPfa = 0, totFoot = 0, totSkin = 0;
        let totCream = 0, totRelief = 0;

        services.forEach((s, idx) => {
            totPeople += parseInt(s.people_served || 0, 10);
            totMobile += parseInt(s.team_mobile_clinic || 0, 10);
            totMcatt += parseInt(s.team_mcatt || 0, 10);
            totSrrt += parseInt(s.team_srrt || 0, 10);
            totChronic += parseInt(s.service_chronic_meds || 0, 10);
            totHome += parseInt(s.service_home_visit || 0, 10);
            totDisp += parseInt(s.service_med_dispense || 0, 10);
            totEdu += parseInt(s.service_health_edu || 0, 10);
            totScreen += parseInt(s.mental_screened || 0, 10);
            totPfa += parseInt(s.mental_help_pfa || 0, 10);
            totFoot += parseInt(s.disease_athletes_foot || 0, 10);
            totSkin += parseInt(s.disease_skin || 0, 10);
            totCream += parseInt(s.resource_foot_cream || 0, 10);
            totRelief += parseInt(s.resource_relief_kit || 0, 10);

            html += `
                <tr>
                    <td style="color: #64748b;">${idx + 1}</td>
                    <td style="font-weight: 600; color: #0f2c4c; text-align: left;">
                        ${escapeHtml(s.organization_name)}
                        <div style="font-size: 0.75rem; color: #64748b;">${escapeHtml(s.district_name)}</div>
                    </td>
                    <td style="font-weight: 500; text-align: left;">${escapeHtml(s.outreach_location || '-')}</td>
                    <td>${s.village_no ? `ม.${s.village_no}` : ''} ${escapeHtml(s.village_name || '-')}</td>
                    <td style="font-weight: 700; color: #0284c7; background: #f0f9ff;">${s.people_served}</td>
                    
                    <td>${s.team_mobile_clinic || 0}</td>
                    <td>${s.team_mcatt || 0}</td>
                    <td>${s.team_srrt || 0}</td>

                    <td>${s.service_chronic_meds || 0}</td>
                    <td style="font-weight: 600; color: #059669;">${s.service_home_visit || 0}</td>
                    <td>${s.service_med_dispense || 0}</td>
                    <td>${s.service_health_edu || 0}</td>

                    <td>${s.mental_screened || 0}</td>
                    <td>${s.mental_help_pfa || 0}</td>

                    <td style="font-weight: 700; color: #dc2626;">${s.disease_athletes_foot || 0}</td>
                    <td>${s.disease_skin || 0}</td>

                    <td>${s.resource_foot_cream || 0}</td>
                    <td>${s.resource_relief_kit || 0}</td>

                    <td style="font-size: 0.775rem; color: #64748b;">${s.created_at || '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline" onclick="editService(${s.id})" title="แก้ไข">✏️</button>
                        <button type="button" class="btn btn-sm btn-outline" style="color: #dc2626;" onclick="deleteService(${s.id})" title="ลบ">🗑️</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        // Render Tfoot
        tfoot.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: right; font-weight: 700; color: #0f2c4c; padding-right: 1rem;">
                    รวมทั้งสิ้น (${services.length} จุดบริการ):
                </td>
                <td style="font-weight: 700; color: #0284c7; background: #e0f2fe; font-size: 0.95rem;">${totPeople}</td>
                <td>${totMobile}</td>
                <td>${totMcatt}</td>
                <td>${totSrrt}</td>
                <td>${totChronic}</td>
                <td style="color: #059669; font-weight: 700;">${totHome}</td>
                <td>${totDisp}</td>
                <td>${totEdu}</td>
                <td>${totScreen}</td>
                <td>${totPfa}</td>
                <td style="color: #dc2626; font-weight: 700;">${totFoot}</td>
                <td>${totSkin}</td>
                <td>${totCream}</td>
                <td>${totRelief}</td>
                <td colspan="2"></td>
            </tr>
        `;
    }

    // Load KPIs
    function loadKPIs(dateStr, orgId) {
        fetch(`api/medical_services.php?action=get_summary&report_date=${dateStr}&organization_id=${orgId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.summary) {
                    const s = res.summary;
                    document.getElementById('kpi_people_served').textContent = Number(s.people_served || 0).toLocaleString();
                    document.getElementById('kpi_teams_visits').textContent = Number((parseInt(s.team_mobile_clinic||0) + parseInt(s.team_mcatt||0))).toLocaleString();
                    document.getElementById('kpi_home_visits').textContent = Number(s.service_home_visit || 0).toLocaleString();
                    document.getElementById('kpi_mental_screened').textContent = Number(s.mental_screened || 0).toLocaleString();
                    document.getElementById('kpi_athletes_foot').textContent = Number(s.disease_athletes_foot || 0).toLocaleString();
                    document.getElementById('kpi_foot_cream').textContent = Number(s.resource_foot_cream || 0).toLocaleString();
                }
            });
    }

    // Open Modal for adding new
    openModalBtn.addEventListener('click', function () {
        form.reset();
        document.getElementById('entry_id').value = '0';
        document.getElementById('entry_report_date').value = filterDate.value;
        document.getElementById('modal_form_title').querySelector('span').textContent = `บันทึกการออกหน่วยบริการประจำวันที่ ${filterDate.value}`;
        modal.classList.add('active');
    });

    // Edit service
    window.editService = function (id) {
        const item = currentServicesList.find(s => s.id == id);
        if (!item) return;

        form.reset();
        document.getElementById('entry_id').value = item.id;
        document.getElementById('entry_report_date').value = item.report_date;

        if (document.getElementById('entry_org_id')) {
            document.getElementById('entry_org_id').value = item.organization_id;
        }

        // Fill all form inputs by id matching property
        for (const [key, val] of Object.entries(item)) {
            const input = document.getElementById(key);
            if (input) {
                input.value = (val !== null) ? val : 0;
            }
        }

        document.getElementById('modal_form_title').querySelector('span').textContent = `แก้ไขข้อมูลการออกหน่วย: ${item.outreach_location || ''}`;
        modal.classList.add('active');
    };

    // Delete service
    window.deleteService = function (id) {
        if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการบริการทางการแพทย์นี้?')) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('api/medical_services.php?action=delete_service', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                showToast('success', res.message);
                loadServices();
            } else {
                showToast('error', res.message);
            }
        });
    };

    // Form submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const saveBtn = document.getElementById('btn_save_service');
        saveBtn.disabled = true;
        saveBtn.innerHTML = 'กำลังบันทึก...';

        const formData = new FormData(form);

        fetch('api/medical_services.php?action=save_service', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>บันทึกข้อมูลบริการ</span>
            `;

            if (res.status === 'success') {
                showToast('success', res.message);
                modal.classList.remove('active');
                loadServices();
            } else {
                showToast('error', res.message);
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'บันทึกข้อมูลบริการ';
            showToast('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + err.message);
        });
    });

    filterDate.addEventListener('change', loadServices);
    if (filterOrg) filterOrg.addEventListener('change', loadServices);
    refreshBtn.addEventListener('click', loadServices);

    // Initial load
    loadServices();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
