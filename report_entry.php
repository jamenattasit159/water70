<?php
// report_entry.php - Daily Flood Situation Report Entry (By Village & By Topic Block)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$pageTitle = 'บันทึกรายงานสถานการณ์อุทกภัยประจำวัน (รายหมู่บ้าน) - ' . htmlspecialchars($user['organization_name'] ?: 'สถานบริการสาธารณสุข');
require_once __DIR__ . '/includes/header.php';

$todayDate = date('Y-m-d');
?>

<div class="container" style="max-width: 1200px;">
    <!-- Breadcrumb & Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 0.25rem;">
                หน้าหลัก &gt; บันทึกข้อมูลประจำวัน &gt; แบบรายงานสถานการณ์อุทกภัย 2569 (รายหมู่บ้าน)
            </div>
            <h1 style="font-size: 1.65rem; color: var(--navy-900);">
                บันทึกแบบรายงานสถานการณ์อุทกภัย ด้านการแพทย์และสาธารณสุข
            </h1>
            <div style="color: var(--slate-600); font-size: 0.95rem;">
                บันทึกข้อมูลจำแนก <strong>รายหมู่บ้าน</strong> และแยกตาม <strong>ล็อกหัวข้อกลุ่มเปราะบาง</strong> (ส่งข้อมูลภายใน 15.00 น.)
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <?php if (in_array($user['role'], ['admin', 'superadmin'])): ?>
                <a href="provincial_overview.php" class="btn btn-primary" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border-color: #0369a1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    <span>ดูภาพรวมทั้งจังหวัดวันนี้</span>
                </a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>กลับแดชบอร์ด</span>
            </a>
        </div>
    </div>

    <!-- Facility Info & Date Selector Banner -->
    <div class="gov-card" style="margin-bottom: 1.5rem; border-left: 5px solid var(--gold-600); background: linear-gradient(to right, #ffffff, #fffbeb);">
        <div style="padding: 1.15rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
            <div>
                <div style="font-size: 0.8rem; font-weight: 600; color: var(--gold-700); text-transform: uppercase;">
                    หน่วยงานผู้บันทึกข้อมูล (Facility in charge)
                </div>
                <div style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700; color: var(--navy-900); margin: 2px 0;">
                    <?= htmlspecialchars($user['organization_name'] ?: 'ไม่ระบุหน่วยงาน') ?>
                </div>
                <div style="font-size: 0.9rem; color: var(--slate-600);">
                    พื้นที่: <strong><?= htmlspecialchars($user['district_name']) ?></strong> • จังหวัดอ่างทอง • ผู้บันทึก: <?= htmlspecialchars($user['fullname']) ?>
                </div>
            </div>

            <!-- Date Picker for Report Date -->
            <div style="background: #ffffff; padding: 0.65rem 1rem; border-radius: var(--radius-md); border: 1px solid #fcd34d; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 0.75rem;">
                <label for="report_date_input" style="font-weight: 600; color: var(--navy-900); font-size: 0.9rem; white-space: nowrap;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    วันที่ของรายงาน:
                </label>
                <input type="date" id="report_date_input" name="report_date" value="<?= $todayDate ?>" class="form-control" style="width: auto; padding: 0.45rem 0.75rem; font-size: 0.95rem; font-weight: 600;">
                <button type="button" class="btn btn-sm btn-outline" onclick="setTodayDate()">วันนี้</button>
            </div>
        </div>
    </div>

    <!-- Active status banner -->
    <div id="report_status_banner" style="margin-bottom: 1.25rem; display: none;"></div>

    <!-- Main Entry Form -->
    <form id="flood_report_form" method="POST">
        <input type="hidden" id="form_report_date" name="report_date" value="<?= $todayDate ?>">

        <!-- ========================================================
             FACILITY IMPACT ACCORDION (ส่วนที่ 1: สถานะสถานบริการ)
             ======================================================== -->
        <div class="gov-card" style="margin-bottom: 1.5rem;">
            <div class="gov-card-header" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); cursor: pointer;" onclick="toggleFacilityImpact()">
                <div class="gov-card-title" style="font-size: 1.1rem;">
                    <span style="background: var(--navy-800); color: #ffffff; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem;">1</span>
                    <span>สถานบริการสาธารณสุขที่ได้รับผลกระทบและการเปิดให้บริการ</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span id="facility_status_badge" class="badge badge-approved">เปิดบริการปกติ</span>
                    <span style="font-size: 0.85rem; color: var(--slate-500);">&#9660;</span>
                </div>
            </div>
            <div class="gov-card-body" id="facility_impact_body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                    <!-- Impact Status -->
                    <div class="form-group">
                        <label class="form-label">ผลกระทบจากอุทกภัยต่อตัวอาคาร/สถานบริการ <span class="req">*</span></label>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.4rem;">
                            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="impact_status" value="normal" checked> ปกติ (ไม่ได้รับผลกระทบ)
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-weight: 600; color: var(--rose-700);">
                                <input type="radio" name="impact_status" value="affected"> ได้รับผลกระทบจากอุทกภัย
                            </label>
                        </div>
                    </div>

                    <!-- Service Status -->
                    <div class="form-group">
                        <label class="form-label" for="service_status">สถานะการให้บริการตรวจรักษา <span class="req">*</span></label>
                        <select id="service_status" name="service_status" class="form-select" required>
                            <option value="normal">เปิดบริการปกติ (Normal Service)</option>
                            <option value="partial">เปิดบางส่วน (Partial Service)</option>
                            <option value="closed">ปิดบริการชั่วคราว (Closed)</option>
                        </select>
                    </div>

                    <!-- Impact Details -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="impact_details">รายละเอียดผลกระทบที่ได้รับ (ถ้ามี)</label>
                        <textarea id="impact_details" name="impact_details" class="form-control" rows="2" placeholder="เช่น น้ำท่วมขังบริเวณทางเข้าอาคารสูง 20 ซม. สามารถเดินเท้าเข้าได้, ระบบไฟสำรองใช้งานได้ปกติ หรือทรัพย์สิน/เวชภัณฑ์ที่ได้รับความเสียหาย"></textarea>
                    </div>

                    <!-- Substitute Location -->
                    <div class="form-group" id="substitute_group" style="grid-column: span 2; display: none;">
                        <label class="form-label" for="substitute_location" style="color: var(--rose-700);">
                            ชื่อสถานที่ที่ให้บริการทดแทน (กรณีปิดบริการ หรือย้ายจุดบริการ) <span class="req">*</span>
                        </label>
                        <input type="text" id="substitute_location" name="substitute_location" class="form-control" placeholder="เช่น ศาลาการเปรียญวัด..., อาคารอเนกประสงค์ หรือ รพ.สต.ข้างเคียง">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================
             TOPIC BLOCK SELECTOR (แยกเป็น ล็อกๆ ตามหัวข้อแบบในไฟล์)
             ======================================================== -->
        <div class="gov-card" style="margin-bottom: 2rem;">
            <div class="gov-card-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #19436e 100%); color: #ffffff;">
                <div>
                    <div style="color: #ffd778; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                        Vulnerable Population by Village
                    </div>
                    <div style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700;">
                        ส่วนที่ 2: บันทึกข้อมูลจำแนกรายหมู่บ้าน (แบ่งเป็นล็อกตามหัวข้อในไฟล์)
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="addVillageRow()" style="color: #ffffff; background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3);">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>+ เพิ่มหมู่บ้าน</span>
                    </button>
                    <button type="submit" class="btn btn-sm btn-success">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>บันทึกทั้งหมด</span>
                    </button>
                </div>
            </div>

            <!-- Block Navigation Tabs (ล็อก 1 ถึง ล็อก 8) -->
            <div style="background: #f8fafc; border-bottom: 1px solid var(--slate-200); padding: 0.5rem 1rem; display: flex; gap: 0.4rem; overflow-x: auto; white-space: nowrap;">
                <button type="button" class="block-tab-btn active" data-block="bedridden" style="border: 1px solid #d97706; background: #fffbeb; color: #92400e;">
                    🛏️ ล็อก 1: ผู้ป่วยติดเตียง
                </button>
                <button type="button" class="block-tab-btn" data-block="dialysis">
                    🩺 ล็อก 2: ผู้ป่วยฟอกไต
                </button>
                <button type="button" class="block-tab-btn" data-block="psychiatric">
                    🧠 ล็อก 3: จิตเวช (รับยา)
                </button>
                <button type="button" class="block-tab-btn" data-block="elderly">
                    👵 ล็อก 4: ผู้สูงอายุ (60+)
                </button>
                <button type="button" class="block-tab-btn" data-block="disabled">
                    ♿ ล็อก 5: ผู้พิการ
                </button>
                <button type="button" class="block-tab-btn" data-block="ncd">
                    💊 ล็อก 6: โรคเรื้อรัง (NCD)
                </button>
                <button type="button" class="block-tab-btn" data-block="pregnant">
                    🤰 ล็อก 7: หญิงตั้งครรภ์
                </button>
                <button type="button" class="block-tab-btn" data-block="children">
                    👶 ล็อก 8: เด็ก (0-5 ปี)
                </button>
                <button type="button" class="block-tab-btn" data-block="medical_team" style="border-left: 2px solid #cbd5e1; margin-left: 0.35rem;">
                    🚑 ทีมปฏิบัติการ & ออกหน่วย
                </button>
            </div>

            <!-- Block Sub-header & Navigation buttons -->
            <div style="padding: 0.85rem 1.25rem; background: #ffffff; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                <div id="block_title_display" style="font-weight: 700; color: var(--navy-900); font-size: 1.05rem;">
                    <!-- Updated via JS -->
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-sm btn-outline" id="btn_prev_block">◀ ล็อกก่อนหน้า</button>
                    <button type="button" class="btn btn-sm btn-outline" id="btn_next_block">ล็อกถัดไป ▶</button>
                </div>
            </div>

            <!-- Dynamic Table Container for Villages -->
            <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                <table class="gov-table" id="village_table" style="text-align: center; border-collapse: separate;">
                    <thead id="village_thead" style="position: sticky; top: 0; z-index: 10;">
                        <!-- Rendered by JS per block -->
                    </thead>
                    <tbody id="village_tbody">
                        <tr><td colspan="8" style="padding: 2rem;">กำลังโหลดรายชื่อหมู่บ้าน...</td></tr>
                    </tbody>
                    <tfoot id="village_tfoot" style="position: sticky; bottom: 0; z-index: 9; background: #f8fafc; font-weight: 700;">
                        <!-- Rendered by JS per block -->
                    </tfoot>
                </table>
            </div>

            <!-- Block 9: Medical Team Content (Shown when medical_team block selected) -->
            <div id="medical_team_content" style="display: none; padding: 1.5rem;">
                <h4 style="font-size: 1.05rem; color: var(--navy-900); margin-bottom: 1rem;">
                    3. การให้บริการด้านการแพทย์และสาธารณสุข (การออกหน่วยและเยี่ยมบ้านประจำวัน)
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="mobile_clinic_visits">หน่วยแพทย์เคลื่อนที่ (ครั้ง)</label>
                        <input type="number" min="0" id="mobile_clinic_visits" name="mobile_clinic_visits" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="mcatt_visits">ทีม MCATT เยียวยาจิตใจ (ครั้ง)</label>
                        <input type="number" min="0" id="mcatt_visits" name="mcatt_visits" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="srrt_visits">ทีมสอบสวนโรค SRRT (ครั้ง)</label>
                        <input type="number" min="0" id="srrt_visits" name="srrt_visits" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="people_served">ประชาชนรับบริการ (คน)</label>
                        <input type="number" min="0" id="people_served" name="people_served" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="home_visits">ออกเยี่ยมบ้าน (หลัง/ราย)</label>
                        <input type="number" min="0" id="home_visits" name="home_visits" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="med_distribution">แจกยา/เวชภัณฑ์ (ชุด)</label>
                        <input type="number" min="0" id="med_distribution" name="med_distribution" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pfa_support">ปฐมพยาบาลทางใจ PFA (ราย)</label>
                        <input type="number" min="0" id="pfa_support" name="pfa_support" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="doctor_consults">ส่งพบแพทย์ (ราย)</label>
                        <input type="number" min="0" id="doctor_consults" name="doctor_consults" class="form-control" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">หมายเหตุหรือข้อเสนอแนะเพิ่มเติมต่อจังหวัด</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="ระบุสิ่งที่ต้องการการสนับสนุน เช่น ยานพาหนะยกสูง, ยาชุดน้ำท่วม, หรือถุงยังชีพ"></textarea>
                </div>
            </div>

            <!-- Submit Bottom Bar -->
            <div style="padding: 1.15rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.875rem; color: var(--slate-600);">
                    * เมื่อกด <strong>บันทึกส่งรายงานประจำวัน</strong> ข้อมูลทุกหมู่บ้านในทุกหัวข้อจะถูกบันทึกพร้อมประวัติการทำงาน (NOW()) ทันที
                </div>
                <button type="submit" id="btn_save_all_report" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1.05rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>บันทึกส่งรายงานประจำวัน</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Recent History of Submissions by this facility -->
    <div class="gov-card" style="margin-bottom: 2rem;">
        <div class="gov-card-header">
            <div class="gov-card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span>ประวัติการส่งรายงานของหน่วยงาน (30 วันล่าสุด)</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="gov-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>วันที่รายงาน</th>
                        <th>ผลกระทบ</th>
                        <th>สถานะการเปิดบริการ</th>
                        <th>กลุ่มเปราะบางน้ำท่วม</th>
                        <th>ประชาชนรับบริการ</th>
                        <th>เวลาบันทึก (NOW)</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="my_reports_table_body">
                    <tr><td colspan="7" style="text-align: center; padding: 1.5rem;">กำลังโหลดประวัติ...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.block-tab-btn {
    font-family: var(--font-heading);
    font-weight: 500;
    font-size: 0.85rem;
    padding: 0.45rem 0.85rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--slate-300);
    background: #ffffff;
    color: var(--slate-700);
    cursor: pointer;
    transition: all 0.15s ease;
}
.block-tab-btn:hover {
    background: #f1f5f9;
}
.block-tab-btn.active {
    background: #0f2c4c;
    color: #ffffff;
    border-color: #0a1f36;
    font-weight: 600;
}
.village-input {
    width: 100%;
    min-width: 65px;
    padding: 0.35rem 0.45rem;
    font-size: 0.9rem;
    text-align: center;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    outline: none;
    transition: border-color 0.15s;
}
.village-input:focus {
    border-color: #2563eb;
    background-color: #f0f7ff;
}
.village-input.flooded {
    border-color: #fca5a5;
    background-color: #fffbfa;
    font-weight: 600;
}
.col-header-blue {
    background-color: #60a5fa !important;
    color: #ffffff !important;
    font-weight: 600;
}
.col-header-block {
    background-color: #fef08a !important;
    color: #854d0e !important;
    font-weight: 700;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('report_date_input');
    const formDate = document.getElementById('form_report_date');
    const form = document.getElementById('flood_report_form');
    const saveBtn = document.getElementById('btn_save_all_report');
    const statusBanner = document.getElementById('report_status_banner');
    const serviceSelect = document.getElementById('service_status');
    const substituteGroup = document.getElementById('substitute_group');
    const historyTbody = document.getElementById('my_reports_table_body');

    // Global in-memory storage for village data
    let villageList = [];
    let currentBlock = 'bedridden';
    let defaultSubdistrict = 'มงคลธรรมนิมิต';

    // Definition of the 8 Topic Blocks (ล็อก 1 - ล็อก 8)
    const topicBlocks = {
        'bedridden': {
            id: 'bedridden',
            title: '1. ผู้ป่วยติดเตียง',
            subtitle: 'การดูแลผู้ป่วยติดเตียงในพื้นที่รับผิดชอบและพื้นที่น้ำท่วม',
            headerColor: '#fef08a', // Light gold/yellow matching Image 1
            headerTextColor: '#854d0e',
            columns: [
                { key: 'bedridden_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '13%' },
                { key: 'bedridden_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'bedridden_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'bedridden_shelter', label: 'การช่วยเหลือ:<br>ศูนย์พักพิง (ราย)', width: '13%' },
                { key: 'bedridden_hospital', label: 'ส่งต่อ รพ.<br>(ราย)', width: '13%' }
            ]
        },
        'dialysis': {
            id: 'dialysis',
            title: '2. ผู้ป่วยฟอกไต',
            subtitle: 'การดูแลผู้ป่วยฟอกไตและปัญหาการขาดการฟอกไตในพื้นที่น้ำท่วม',
            headerColor: '#e9d5ff', // Light purple/lilac matching Image 2
            headerTextColor: '#581c87',
            columns: [
                { key: 'dialysis_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'dialysis_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'dialysis_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'dialysis_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'dialysis_missed', label: 'ขาดการฟอกไต<br>(ราย)', width: '13%', isAlert: true }
            ]
        },
        'psychiatric': {
            id: 'psychiatric',
            title: '3. จิตเวช (ที่ต้องรับยา)',
            subtitle: 'การดูแลผู้ป่วยจิตเวชในพื้นที่น้ำท่วมและการส่งต่อยาต่อเนื่อง',
            headerColor: '#fed7aa',
            headerTextColor: '#7c2d12',
            columns: [
                { key: 'psychiatric_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'psychiatric_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'psychiatric_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'psychiatric_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'psychiatric_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '13%', isAlert: true }
            ]
        },
        'elderly': {
            id: 'elderly',
            title: '4. ผู้สูงอายุ (60 ปีขึ้นไป)',
            subtitle: 'การดูแลผู้สูงอายุที่มีโรคประจำตัวในพื้นที่น้ำท่วมและการสนับสนุนยา',
            headerColor: '#dbeafe',
            headerTextColor: '#1e3a8a',
            columns: [
                { key: 'elderly_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'elderly_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'elderly_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'elderly_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'elderly_out_of_meds', label: 'มีโรคประจำตัว/<br>ขาดยา (ราย)', width: '13%', isAlert: true }
            ]
        },
        'disabled': {
            id: 'disabled',
            title: '5. ผู้พิการ',
            subtitle: 'การดูแลผู้พิการในพื้นที่น้ำท่วม การอพยพ และสุขภาวะ',
            headerColor: '#ccfbf1',
            headerTextColor: '#115e59',
            columns: [
                { key: 'disabled_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'disabled_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'disabled_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'disabled_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'disabled_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '13%', isAlert: true }
            ]
        },
        'ncd': {
            id: 'ncd',
            title: '6. ผู้ป่วยโรคเรื้อรัง (NCD)',
            subtitle: 'ผู้ป่วยเบาหวาน/ความดันโลหิตสูงในพื้นที่น้ำท่วม และปัญหาการขาดยา',
            headerColor: '#fecdd3',
            headerTextColor: '#881337',
            columns: [
                { key: 'ncd_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'ncd_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'ncd_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'ncd_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'ncd_out_of_meds', label: 'ขาดยาประจำตัว<br>(ราย)', width: '13%', isAlert: true }
            ]
        },
        'pregnant': {
            id: 'pregnant',
            title: '7. หญิงตั้งครรภ์',
            subtitle: 'การดูแลหญิงตั้งครรภ์ การเฝ้าระวังวันใกล้คลอดในพื้นที่น้ำท่วม',
            headerColor: '#fae8ff',
            headerTextColor: '#701a75',
            columns: [
                { key: 'pregnant_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'pregnant_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'pregnant_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'pregnant_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'pregnant_health_issue', label: 'พบปัญหา/เสี่ยง<br>ใกล้คลอด (ราย)', width: '13%', isAlert: true }
            ]
        },
        'children': {
            id: 'children',
            title: '8. เด็กอายุ (0-5 ปี)',
            subtitle: 'การดูแลเด็กเล็กและสุขอนามัยในพื้นที่ประสบอุทกภัย',
            headerColor: '#e0e7ff',
            headerTextColor: '#312e81',
            columns: [
                { key: 'children_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'children_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'children_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '13%' },
                { key: 'children_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'children_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '13%', isAlert: true }
            ]
        }
    };

    const blockKeys = Object.keys(topicBlocks);

    // Block tab buttons click
    document.querySelectorAll('.block-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const blk = this.getAttribute('data-block');
            switchBlock(blk);
        });
    });

    document.getElementById('btn_prev_block').addEventListener('click', function () {
        const curIdx = blockKeys.indexOf(currentBlock);
        if (curIdx > 0) switchBlock(blockKeys[curIdx - 1]);
    });

    document.getElementById('btn_next_block').addEventListener('click', function () {
        const curIdx = blockKeys.indexOf(currentBlock);
        if (curIdx < blockKeys.length - 1) switchBlock(blockKeys[curIdx + 1]);
        else switchBlock('medical_team');
    });

    function switchBlock(blk) {
        currentBlock = blk;
        document.querySelectorAll('.block-tab-btn').forEach(b => {
            b.classList.remove('active');
            if (b.getAttribute('data-block') === blk) b.classList.add('active');
        });

        const vTable = document.getElementById('village_table');
        const medContent = document.getElementById('medical_team_content');

        if (blk === 'medical_team') {
            vTable.style.display = 'none';
            medContent.style.display = 'block';
            document.getElementById('block_title_display').innerHTML = '🚑 <strong>การให้บริการด้านการแพทย์และสาธารณสุข</strong>';
        } else {
            vTable.style.display = 'table';
            medContent.style.display = 'none';
            renderVillageTableForBlock(blk);
        }
    }

    // Render Table Headers, Rows, and Footers for a specific Block
    function renderVillageTableForBlock(blockKey) {
        const blk = topicBlocks[blockKey];
        if (!blk) return;

        document.getElementById('block_title_display').innerHTML = `
            <span style="font-weight: 700; color: #0f2c4c;">${blk.title}</span> 
            <span style="font-size: 0.85rem; font-weight: normal; color: #64748b; margin-left: 0.5rem;">${blk.subtitle}</span>
        `;

        const thead = document.getElementById('village_thead');
        const tbody = document.getElementById('village_tbody');
        const tfoot = document.getElementById('village_tfoot');

        // 1. Two-tier Table Header exactly like Excel
        let headerHtml = `
            <tr style="text-align: center;">
                <th rowspan="2" class="col-header-blue" style="width: 45px; vertical-align: middle;">ที่</th>
                <th rowspan="2" class="col-header-blue" style="width: 140px; vertical-align: middle;">ตำบล</th>
                <th rowspan="2" class="col-header-blue" style="width: 75px; vertical-align: middle;">หมู่</th>
                <th colspan="${blk.columns.length}" style="background-color: ${blk.headerColor}; color: ${blk.headerTextColor}; font-size: 1rem; padding: 0.5rem;">
                    ${blk.title}
                </th>
                <th rowspan="2" style="width: 45px; vertical-align: middle; background: #f1f5f9;">ลบ</th>
            </tr>
            <tr style="text-align: center;">
        `;

        blk.columns.forEach(col => {
            const isFlood = col.isFlood;
            headerHtml += `
                <th style="width: ${col.width}; background-color: ${isFlood ? '#fee2e2' : '#f8fafc'}; color: ${isFlood ? '#991b1b' : '#334155'}; font-size: 0.825rem; padding: 0.5rem;">
                    ${col.label}
                </th>
            `;
        });
        headerHtml += `</tr>`;
        thead.innerHTML = headerHtml;

        // 2. Table Body (Villages rows)
        renderVillageRows(blockKey);

        // 3. Table Footer (Totals)
        renderVillageFooter(blockKey);
    }

    function renderVillageRows(blockKey) {
        const blk = topicBlocks[blockKey];
        const tbody = document.getElementById('village_tbody');

        if (!villageList || villageList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="padding: 1.5rem; text-align: center;">ไม่มีข้อมูลหมู่บ้าน กรุณากด "+ เพิ่มหมู่บ้าน"</td></tr>';
            return;
        }

        let html = '';
        villageList.forEach((v, idx) => {
            html += `
                <tr data-index="${idx}">
                    <td style="font-weight: 600; color: #64748b; font-size: 0.85rem;">${idx + 1}</td>
                    <td style="text-align: left;">
                        <input type="text" class="village-input" style="text-align: left; font-weight: 500;" value="${escapeHtml(v.subdistrict || defaultSubdistrict)}" onchange="updateVillageField(${idx}, 'subdistrict', this.value)">
                    </td>
                    <td>
                        <input type="number" min="1" class="village-input" style="font-weight: 700; color: #0f2c4c;" value="${v.village_no || (idx + 1)}" onchange="updateVillageField(${idx}, 'village_no', this.value)">
                    </td>
            `;

            blk.columns.forEach(col => {
                const val = v[col.key] !== undefined ? v[col.key] : 0;
                const isFlood = col.isFlood;
                html += `
                    <td>
                        <input type="number" min="0" 
                            class="village-input ${isFlood ? 'flooded' : ''}" 
                            value="${val}" 
                            oninput="updateVillageNumber(${idx}, '${col.key}', this.value, '${blockKey}')"
                        >
                    </td>
                `;
            });

            html += `
                <td>
                    <button type="button" onclick="removeVillageRow(${idx})" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 2px;" title="ลบหมู่บ้านนี้">
                        &times;
                    </button>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
    }

    function renderVillageFooter(blockKey) {
        const blk = topicBlocks[blockKey];
        const tfoot = document.getElementById('village_tfoot');

        let footHtml = `
            <tr style="text-align: center; border-top: 2px solid #cbd5e1;">
                <td colspan="3" style="text-align: right; padding-right: 1rem; color: #0f2c4c; font-size: 0.95rem;">
                    รวมทั้งตำบล (${villageList.length} หมู่บ้าน):
                </td>
        `;

        blk.columns.forEach(col => {
            let sum = 0;
            villageList.forEach(v => {
                sum += parseInt(v[col.key] || 0, 10);
            });
            const isFlood = col.isFlood;
            footHtml += `
                <td id="foot_sum_${col.key}" style="font-size: 1.05rem; font-weight: 700; color: ${isFlood ? '#b91c1c' : '#0f2c4c'}; background: ${isFlood ? '#fee2e2' : '#f1f5f9'};">
                    ${sum.toLocaleString()}
                </td>
            `;
        });

        footHtml += `<td></td></tr>`;
        tfoot.innerHTML = footHtml;
    }

    // Live update cell values
    window.updateVillageNumber = function (idx, field, val, blockKey) {
        if (!villageList[idx]) return;
        villageList[idx][field] = Math.max(0, parseInt(val || 0, 10));

        // recalculate column sum in footer
        let sum = 0;
        villageList.forEach(v => {
            sum += parseInt(v[field] || 0, 10);
        });
        const footCell = document.getElementById(`foot_sum_${field}`);
        if (footCell) footCell.textContent = sum.toLocaleString();
    };

    window.updateVillageField = function (idx, field, val) {
        if (!villageList[idx]) return;
        villageList[idx][field] = val;
    };

    window.addVillageRow = function () {
        const nextMoo = villageList.length + 1;
        const newRow = {
            subdistrict: defaultSubdistrict,
            village_no: nextMoo,
            village_name: `หมู่ที่ ${nextMoo}`
        };

        // initialize all keys
        for (const [bk, blk] of Object.entries(topicBlocks)) {
            blk.columns.forEach(col => { newRow[col.key] = 0; });
        }

        villageList.push(newRow);
        if (currentBlock !== 'medical_team') {
            renderVillageRows(currentBlock);
            renderVillageFooter(currentBlock);
        }
        showToast('success', `เพิ่ม หมู่ที่ ${nextMoo} เรียบร้อย`);
    };

    window.removeVillageRow = function (idx) {
        if (confirm(`ยืนยันการลบหมู่ที่ ${villageList[idx].village_no} ออกจากตารางหรือไม่?`)) {
            villageList.splice(idx, 1);
            if (currentBlock !== 'medical_team') {
                renderVillageRows(currentBlock);
                renderVillageFooter(currentBlock);
            }
        }
    };

    // Load Report Data for Date
    function loadReportForDate(dateStr) {
        saveBtn.disabled = true;
        fetch(`api/flood_report.php?action=get_report&report_date=${dateStr}`)
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                if (data.status === 'success') {
                    if (data.subdistrict_default) {
                        defaultSubdistrict = data.subdistrict_default;
                    }

                    // Set village list
                    villageList = data.villages || [];

                    // Facility impact
                    const r = data.report;
                    if (r) {
                        if (r.impact_status === 'affected') {
                            document.querySelector('input[name="impact_status"][value="affected"]').checked = true;
                        } else {
                            document.querySelector('input[name="impact_status"][value="normal"]').checked = true;
                        }

                        serviceSelect.value = r.service_status || 'normal';
                        serviceSelect.dispatchEvent(new Event('change'));
                        document.getElementById('impact_details').value = r.impact_details || '';
                        document.getElementById('substitute_location').value = r.substitute_location || '';

                        // Services
                        document.getElementById('mobile_clinic_visits').value = r.mobile_clinic_visits || 0;
                        document.getElementById('mcatt_visits').value = r.mcatt_visits || 0;
                        document.getElementById('srrt_visits').value = r.srrt_visits || 0;
                        document.getElementById('people_served').value = r.people_served || 0;
                        document.getElementById('home_visits').value = r.home_visits || 0;
                        document.getElementById('med_distribution').value = r.med_distribution || 0;
                        document.getElementById('pfa_support').value = r.pfa_support || 0;
                        document.getElementById('doctor_consults').value = r.doctor_consults || 0;
                        document.getElementById('notes').value = r.notes || '';

                        statusBanner.className = 'gov-alert success';
                        statusBanner.innerHTML = `
                            <div class="gov-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                            <div>
                                <strong>หน่วยงานของท่านได้บันทึกรายงานสำหรับวันที่ ${dateStr} แล้ว (บันทึกรายหมู่บ้าน)</strong><br>
                                <span style="font-size: 0.85rem; opacity: 0.9;">บันทึกล่าสุดเมื่อ: ${r.updated_at || r.created_at} โดยคุณ ${escapeHtml(r.user_name || '')} (หากต้องการแก้ไขข้อมูล สามารถปรับปรุงตัวเลขและกดบันทึกใหม่ได้ทันที)</span>
                            </div>
                        `;
                        statusBanner.style.display = 'flex';
                    } else {
                        // Reset base report fields
                        document.querySelector('input[name="impact_status"][value="normal"]').checked = true;
                        serviceSelect.value = 'normal';
                        serviceSelect.dispatchEvent(new Event('change'));
                        document.getElementById('impact_details').value = '';
                        document.getElementById('substitute_location').value = '';
                        document.getElementById('notes').value = '';

                        statusBanner.className = 'gov-alert warning';
                        statusBanner.innerHTML = `
                            <div class="gov-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                            <div>
                                <strong>ยังไม่มีการส่งรายงานสำหรับวันที่ ${dateStr}</strong><br>
                                <span style="font-size: 0.85rem; opacity: 0.9;">กรุณาบันทึกข้อมูลแยกตามหมู่บ้านในแต่ละล็อกหัวข้อ และกดบันทึกข้อมูล</span>
                            </div>
                        `;
                        statusBanner.style.display = 'flex';
                    }

                    // Render active block table
                    if (currentBlock !== 'medical_team') {
                        renderVillageTableForBlock(currentBlock);
                    }
                }
            })
            .catch(err => {
                saveBtn.disabled = false;
                console.error(err);
            });
    }

    // Submit handler
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        setLoading(saveBtn, true, 'กำลังบันทึกข้อมูลทุกหมู่บ้าน...');

        const fd = new FormData(form);
        // append villages JSON
        fd.append('villages', JSON.stringify(villageList));

        fetch('api/flood_report.php?action=save_report', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                setLoading(saveBtn, false, 'บันทึกส่งรายงานประจำวัน');
                if (res.status === 'success') {
                    showToast('success', res.message);
                    loadReportForDate(dateInput.value);
                    loadHistory();
                } else {
                    showToast('error', res.message || 'บันทึกไม่สำเร็จ');
                }
            })
            .catch(err => {
                setLoading(saveBtn, false, 'บันทึกส่งรายงานประจำวัน');
                showToast('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
            });
    });

    // Date change
    dateInput.addEventListener('change', function () {
        formDate.value = this.value;
        loadReportForDate(this.value);
    });

    window.setTodayDate = function () {
        dateInput.value = '<?= $todayDate ?>';
        dateInput.dispatchEvent(new Event('change'));
    };

    // Service Status change
    serviceSelect.addEventListener('change', function () {
        const badge = document.getElementById('facility_status_badge');
        if (this.value === 'closed') {
            substituteGroup.style.display = 'block';
            badge.className = 'badge badge-rejected';
            badge.textContent = 'ปิดบริการชั่วคราว';
        } else if (this.value === 'partial') {
            substituteGroup.style.display = 'block';
            badge.className = 'badge badge-pending';
            badge.textContent = 'เปิดบริการบางส่วน';
        } else {
            substituteGroup.style.display = 'none';
            badge.className = 'badge badge-approved';
            badge.textContent = 'เปิดบริการปกติ';
        }
    });

    window.toggleFacilityImpact = function () {
        const b = document.getElementById('facility_impact_body');
        b.style.display = (b.style.display === 'none') ? 'block' : 'none';
    };

    // History Table
    function loadHistory() {
        fetch('api/flood_report.php?action=my_reports')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.reports) {
                    if (data.reports.length === 0) {
                        historyTbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #64748b; padding: 1.5rem;">ยังไม่มีประวัติการส่งรายงาน</td></tr>';
                        return;
                    }

                    let html = '';
                    data.reports.forEach(r => {
                        const isAffected = r.impact_status === 'affected';
                        const badgeImpact = isAffected 
                            ? '<span class="badge badge-rejected">ได้รับผลกระทบ</span>' 
                            : '<span class="badge badge-approved">ปกติ</span>';

                        let badgeService = '<span class="badge badge-approved">เปิดปกติ</span>';
                        if (r.service_status === 'partial') badgeService = '<span class="badge badge-pending">เปิดบางส่วน</span>';
                        if (r.service_status === 'closed') badgeService = '<span class="badge badge-rejected">ปิดบริการ</span>';

                        const floodedCount = (parseInt(r.bedridden_flooded||0) + parseInt(r.elderly_flooded||0) + parseInt(r.disabled_flooded||0));

                        html += `
                            <tr>
                                <td style="font-weight: 600; color: #0f2c4c;">${r.report_date}</td>
                                <td>${badgeImpact}</td>
                                <td>${badgeService}</td>
                                <td style="color: ${floodedCount > 0 ? '#b91c1c' : '#334155'}; font-weight: 600;">${floodedCount} ราย</td>
                                <td>${r.people_served || 0} คน</td>
                                <td style="font-size: 0.825rem; color: #64748b;">${r.created_at}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="selectDate('${r.report_date}')">
                                        เปิดดู / แก้ไข
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    historyTbody.innerHTML = html;
                }
            });
    }

    window.selectDate = function (d) {
        dateInput.value = d;
        dateInput.dispatchEvent(new Event('change'));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Initial load
    loadReportForDate(dateInput.value);
    loadHistory();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
