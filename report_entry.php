<?php
// report_entry.php - Daily Flood Situation Report Entry (By Village & By Topic Block)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();
$user = current_user();

$pageTitle = 'บันทึกรายงานสถานการณ์อุทกภัยประจำวัน (รายหมู่บ้าน) - ' . htmlspecialchars($user['organization_name'] ?: 'สถานบริการสาธารณสุข');
require_once __DIR__ . '/includes/header.php';

$todayDate = date('Y-m-d');
$selectedDate = trim($_GET['report_date'] ?? $todayDate);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $todayDate;
}
?>

<div class="container" style="max-width: 1200px;">
    <!-- Breadcrumb & Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.95rem; color: var(--color-text-secondary); margin-bottom: 0.35rem; font-weight: 500;">
                หน้าหลัก &gt; บันทึกข้อมูลประจำวัน &gt; แบบรายงานสถานการณ์อุทกภัย 2569 (รายหมู่บ้าน)
            </div>
            <h1 style="font-size: 2rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.35rem;">
                บันทึกแบบรายงานสถานการณ์อุทกภัย ด้านการแพทย์และสาธารณสุข
            </h1>
            <div style="color: var(--color-text-secondary); font-size: 1.05rem;">
                บันทึกข้อมูลจำแนก <strong>รายหมู่บ้าน</strong> และแยกตาม <strong>ล็อกหัวข้อกลุ่มเปราะบาง</strong> (ส่งข้อมูลภายใน 15.00 น.)
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="medical_services.php?report_date=<?= urlencode($selectedDate) ?>" onclick="goToSection3(event)" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                <span>3. บริการแพทย์ & ออกหน่วย</span>
            </a>
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
    <div class="card" style="margin-bottom: 1.5rem;">
        <div style="padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
            <div>
                <div style="font-size: 0.9rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase; letter-spacing: 0.04em;">
                    หน่วยงานผู้บันทึกข้อมูล (Facility in charge)
                </div>
                <div style="font-family: var(--font-heading); font-size: 1.6rem; font-weight: 700; color: var(--color-text); margin: 4px 0;">
                    <?= htmlspecialchars($user['organization_name'] ?: 'ไม่ระบุหน่วยงาน') ?>
                </div>
                <div style="font-size: 1rem; color: var(--color-text-secondary);">
                    พื้นที่: <strong style="color: var(--color-text);"><?= htmlspecialchars($user['district_name']) ?></strong> &bull; จังหวัดอ่างทอง &bull; ผู้บันทึก: <strong style="color: var(--color-text);"><?= htmlspecialchars($user['fullname']) ?></strong>
                </div>
            </div>

            <!-- Date Picker for Report Date -->
            <div style="background: var(--color-surface-subtle); padding: 0.55rem 0.85rem; border-radius: var(--radius-md); border: 1.5px solid var(--color-border); display: flex; align-items: center; gap: 0.75rem;">
                <label for="report_date_input" style="font-weight: 700; color: var(--color-text); font-size: 0.95rem; white-space: nowrap;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -3px; margin-right: 3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    วันที่รายงาน:
                </label>
                <input type="date" id="report_date_input" name="report_date" value="<?= htmlspecialchars($selectedDate) ?>" class="form-control" style="width: auto; padding: 0.45rem 0.75rem; font-size: 1rem; font-weight: 700; height: 38px; color: var(--color-text);">
                <button type="button" class="btn btn-sm btn-outline" onclick="setTodayDate()" style="font-weight: 600;">วันนี้</button>
            </div>
        </div>
    </div>

    <!-- Active status banner -->
    <div id="report_status_banner" style="margin-bottom: 1.25rem; display: none;"></div>

    <!-- Main Entry Form -->
    <form id="flood_report_form" method="POST">
        <input type="hidden" id="form_report_date" name="report_date" value="<?= htmlspecialchars($selectedDate) ?>">

        <!-- ========================================================
             FACILITY IMPACT ACCORDION (ส่วนที่ 1: สถานะสถานบริการ)
             ======================================================== -->
        <div class="gov-card" style="margin-bottom: 1.5rem;">
            <button type="button" class="facility-impact-toggle" id="facility_impact_toggle" aria-expanded="true" aria-controls="facility_impact_body">
                <div class="gov-card-title" style="font-size: 1.1rem;">
                    <span style="background: var(--navy-800); color: #ffffff; width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem;">1</span>
                    <span>สถานบริการสาธารณสุขที่ได้รับผลกระทบและการเปิดให้บริการ</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span id="facility_status_badge" class="badge badge-approved">เปิดบริการปกติ</span>
                    <span style="font-size: 0.85rem; color: var(--slate-500);">&#9660;</span>
                </div>
            </button>
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
             TOPIC BLOCK SELECTOR (ส่วนที่ 2: การดูแลกลุ่มเปราะบาง รายหมู่บ้าน)
             ======================================================== -->
        <div class="gov-card" style="margin-bottom: 2rem;">
            <div class="gov-card-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #19436e 100%); color: #ffffff; padding: 1.1rem 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="background: #ffd778; color: #0f2c4c; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700;">2</span>
                    <div>
                        <div style="color: #ffd778; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                            2. การดูแลกลุ่มเปราะบาง (สถานบริการบันทึกรายหมู่บ้าน)
                        </div>
                        <div style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 700;">
                            ส่วนที่ 2: บันทึกข้อมูลกลุ่มเปราะบางรายหมู่บ้าน (8 กลุ่มเป้าหมาย)
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.6rem; align-items: center;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="addVillageRow()" style="color: #ffffff; background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); font-size: 0.95rem; padding: 0.5rem 0.9rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>+ เพิ่มหมู่บ้าน</span>
                    </button>
                    <button type="submit" class="btn btn-sm btn-success" style="font-size: 0.95rem; padding: 0.5rem 1rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>บันทึกทั้งหมด</span>
                    </button>
                </div>
            </div>

            <!-- Block Navigation (Grid Layout: 4 columns x 2 rows, Clear & Big, No Horizontal Scrolling) -->
            <div style="background: #f8fafc; border-bottom: 1.5px solid var(--slate-200); padding: 1.1rem 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div style="font-size: 1rem; font-weight: 700; color: var(--navy-900); display: flex; align-items: center; gap: 0.4rem;">
                        <span>📋</span>
                        <span>เลือกล็อกหัวข้อกลุ่มเปราะบางที่ต้องการบันทึก (คลิกเลือกหัวข้อด้านล่างเพื่อแสดงตาราง):</span>
                    </div>
                    <span style="font-size: 0.85rem; color: var(--slate-500); background: #ffffff; padding: 3px 10px; border-radius: 999px; border: 1px solid var(--slate-200);">
                        แสดงพร้อมกันครบทั้ง 8 กลุ่มเป้าหมาย
                    </span>
                </div>

                <div class="block-selector-grid">
                    <button type="button" class="block-select-card active" data-block="bedridden">
                        <div class="block-card-badge" style="background: #fef08a; color: #854d0e;">ล็อก 1</div>
                        <div class="block-card-icon" style="background: #fffbeb;">🛏️</div>
                        <div class="block-card-info">
                            <div class="block-card-title">1. ผู้ป่วยติดเตียง</div>
                            <div class="block-card-desc">อยู่บ้าน / พักพิง / ส่งต่อ รพ.</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="dialysis">
                        <div class="block-card-badge" style="background: #e9d5ff; color: #581c87;">ล็อก 2</div>
                        <div class="block-card-icon" style="background: #faf5ff;">🩺</div>
                        <div class="block-card-info">
                            <div class="block-card-title">2. ผู้ป่วยฟอกไต</div>
                            <div class="block-card-desc">ขาดการฟอกไต / การอพยพ</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="psychiatric">
                        <div class="block-card-badge" style="background: #fed7aa; color: #7c2d12;">ล็อก 3</div>
                        <div class="block-card-icon" style="background: #fff7ed;">🧠</div>
                        <div class="block-card-info">
                            <div class="block-card-title">3. จิตเวช (รับยา)</div>
                            <div class="block-card-desc">รับยาต่อเนื่อง / ปัญหาสุขภาพ</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="elderly">
                        <div class="block-card-badge" style="background: #dbeafe; color: #1e3a8a;">ล็อก 4</div>
                        <div class="block-card-icon" style="background: #eff6ff;">👵</div>
                        <div class="block-card-info">
                            <div class="block-card-title">4. ผู้สูงอายุ (60+)</div>
                            <div class="block-card-desc">มีโรคประจำตัว / ปัญหาขาดยา</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="disabled">
                        <div class="block-card-badge" style="background: #ccfbf1; color: #115e59;">ล็อก 5</div>
                        <div class="block-card-icon" style="background: #f0fdfa;">♿</div>
                        <div class="block-card-info">
                            <div class="block-card-title">5. ผู้พิการ</div>
                            <div class="block-card-desc">อยู่บ้าน / อพยพ / สุขภาพ</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="ncd">
                        <div class="block-card-badge" style="background: #fecdd3; color: #881337;">ล็อก 6</div>
                        <div class="block-card-icon" style="background: #fff1f2;">💊</div>
                        <div class="block-card-info">
                            <div class="block-card-title">6. โรคเรื้อรัง (NCD)</div>
                            <div class="block-card-desc">เบาหวาน-ความดัน / ขาดยา</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="pregnant">
                        <div class="block-card-badge" style="background: #fae8ff; color: #701a75;">ล็อก 7</div>
                        <div class="block-card-icon" style="background: #fdf4ff;">🤰</div>
                        <div class="block-card-info">
                            <div class="block-card-title">7. หญิงตั้งครรภ์</div>
                            <div class="block-card-desc">เฝ้าระวังวันใกล้คลอด / อพยพ</div>
                        </div>
                    </button>

                    <button type="button" class="block-select-card" data-block="children">
                        <div class="block-card-badge" style="background: #e0e7ff; color: #312e81;">ล็อก 8</div>
                        <div class="block-card-icon" style="background: #eef2ff;">👶</div>
                        <div class="block-card-info">
                            <div class="block-card-title">8. เด็ก (0-5 ปี)</div>
                            <div class="block-card-desc">สุขอนามัย / ปัญหาสุขภาพ</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Block Sub-header & Navigation buttons -->
            <div style="padding: 0.95rem 1.35rem; background: #ffffff; border-bottom: 1.5px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div id="block_title_display" style="font-weight: 700; color: var(--navy-900); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                    <!-- Updated via JS -->
                </div>
                <div style="display: flex; gap: 0.6rem; align-items: center;">
                    <span style="font-size: 0.9rem; color: var(--slate-500); margin-right: 0.25rem;">เลื่อนล็อก:</span>
                    <button type="button" class="btn btn-sm btn-outline" id="btn_prev_block" style="font-size: 0.95rem; font-weight: 600; padding: 0.4rem 0.85rem;">◀ ล็อกก่อนหน้า</button>
                    <button type="button" class="btn btn-sm btn-outline" id="btn_next_block" style="font-size: 0.95rem; font-weight: 600; padding: 0.4rem 0.85rem;">ล็อกถัดไป ▶</button>
                </div>
            </div>

            <!-- Dynamic Table Container for Villages (Display all rows, horizontally scrollable on mobile) -->
            <div class="table-responsive" style="overflow-x: auto; overflow-y: visible; width: 100%;">
                <table class="gov-table" id="village_table" style="text-align: center; border-collapse: separate; width: 100%; min-width: 1020px;">
                    <thead id="village_thead" style="position: sticky; top: 0; z-index: 10;">
                        <!-- Rendered by JS per block -->
                    </thead>
                    <tbody id="village_tbody">
                        <tr><td colspan="8" style="padding: 2rem;">กำลังโหลดรายชื่อหมู่บ้าน...</td></tr>
                    </tbody>
                    <tfoot id="village_tfoot" style="background: #f8fafc; font-weight: 700;">
                        <!-- Rendered by JS per block -->
                    </tfoot>
                </table>
            </div>
            <!-- Block Notes & Support Request -->
            <div style="padding: 1.25rem 1.5rem; background: #ffffff; border-top: 1.5px solid var(--slate-200);">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="notes" style="font-size: 1.05rem; font-weight: 700; color: var(--navy-900); display: flex; align-items: center; gap: 0.45rem;">
                        <span>📝</span> หมายเหตุ / สรุปสถานการณ์ / ข้อเสนอแนะและความต้องการสนับสนุนต่อจังหวัด
                    </label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" style="font-size: 1.05rem; line-height: 1.6;" placeholder="ระบุสิ่งที่ต้องการสนับสนุน เช่น ยานพาหนะยกสูง, เรือตรวจเยี่ยม, ยาชุดน้ำท่วมเพิ่มเติม, อาหาร/น้ำดื่ม หรือถุงยังชีพสำหรับกลุ่มเปราะบาง"></textarea>
                </div>
            </div>

            <!-- Submit Bottom Bar for Section 1 & 2 -->
            <div style="padding: 1.35rem 1.75rem; background: #f8fafc; border-top: 1.5px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.95rem; color: var(--slate-600); line-height: 1.5;">
                    * เมื่อกด <strong>บันทึกส่งรายงานประจำวัน</strong> ข้อมูลทั้ง <strong>ส่วนที่ 1 (สถานะหน่วยบริการ)</strong> และ <strong>ส่วนที่ 2 (กลุ่มเปราะบางทุกหมู่บ้าน)</strong> จะถูกบันทึกเข้าระบบทันที
                </div>
                <button type="submit" id="btn_save_all_report" class="btn btn-primary" style="padding: 0.85rem 2.5rem; font-size: 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.65rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>บันทึกส่งรายงานประจำวัน (ส่วนที่ 1 - 2)</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Notice Card: Link to Section 3 (Medical & Outreach Services) -->
    <div class="gov-card" style="margin-bottom: 2rem; border-left: 4px solid var(--color-primary); background: #f0f9ff;">
        <div class="gov-card-body" style="padding: 1.35rem 1.6rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #0284c7; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 2px 4px rgba(2,132,199,0.3);">
                    🚑
                </div>
                <div>
                    <div style="color: #0284c7; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                        3. การให้บริการด้านการแพทย์และสาธารณสุข (สถานบริการบันทึก)
                    </div>
                    <h3 style="margin: 0.15rem 0 0.35rem 0; font-size: 1.2rem; font-weight: 700; color: #0f2c4c;">
                        แบบบันทึกการออกหน่วย & บริการแพทย์ (ส่วนที่ 3)
                    </h3>
                    <p style="margin: 0; font-size: 0.95rem; color: #334155; line-height: 1.5;">
                        บันทึกแยกรายจุดออกหน่วย: ทีมปฏิบัติการ (หน่วยแพทย์เคลื่อนที่, MCATT, SRRT, ShERT), การให้บริการในพื้นที่, การคัดกรองสุขภาพจิต, โรคพบบ่อย, เวชภัณฑ์ และศูนย์พักพิง
                    </p>
                </div>
            </div>
            <div>
                <a href="medical_services.php?report_date=<?= urlencode($selectedDate) ?>" id="btn_goto_section_3" onclick="goToSection3(event)" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1.05rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; white-space: nowrap; cursor: pointer;">
                    <span>ไปยังแบบบันทึกส่วนที่ 3</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
        </div>
    </div>

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
.village-input {
    width: 100%;
    min-width: 75px;
    padding: 0.45rem 0.5rem;
    font-size: 1.05rem;
    font-weight: 600;
    text-align: center;
    border: 1.5px solid #cbd5e1;
    border-radius: var(--radius-sm);
    outline: none;
    transition: all 0.15s ease;
    height: 42px;
}
.village-input:focus {
    border-color: #2563eb;
    background-color: #eff6ff;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}
.village-input.flooded {
    border-color: #f87171;
    background-color: #fef2f2;
    color: #b91c1c;
    font-weight: 700;
}
.village-input.subdistrict-input {
    min-width: 165px;
    text-align: left;
    padding: 0.45rem 0.65rem;
    font-weight: 600;
    color: #1e293b;
}
.btn-delete-row {
    background: #fee2e2;
    border: 1.5px solid #fca5a5;
    color: #b91c1c;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
}
.btn-delete-row:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    transform: scale(1.06);
    box-shadow: 0 2px 5px rgba(220, 38, 38, 0.25);
}
.col-header-blue {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    font-weight: 700;
    font-size: 0.95rem;
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
    const allTotalFields = ['bedridden_total', 'dialysis_total', 'psychiatric_total', 'elderly_total', 'disabled_total', 'ncd_total', 'pregnant_total', 'children_total'];

    // Definition of the 8 Topic Blocks (ล็อก 1 - ล็อก 8)
    const topicBlocks = {
        'bedridden': {
            id: 'bedridden',
            title: '1. ผู้ป่วยติดเตียง',
            subtitle: 'การดูแลผู้ป่วยติดเตียงในพื้นที่รับผิดชอบและพื้นที่น้ำท่วม',
            headerColor: '#fef08a', // Light gold/yellow matching Image 1
            headerTextColor: '#854d0e',
            columns: [
                { key: 'bedridden_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'bedridden_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'bedridden_shelter', label: 'การช่วยเหลือ:<br>ศูนย์พักพิง (ราย)', width: '16%' },
                { key: 'bedridden_hospital', label: 'ส่งต่อ รพ.<br>(ราย)', width: '16%' }
            ]
        },
        'dialysis': {
            id: 'dialysis',
            title: '2. ผู้ป่วยฟอกไต',
            subtitle: 'การดูแลผู้ป่วยฟอกไตและปัญหาการขาดการฟอกไตในพื้นที่น้ำท่วม',
            headerColor: '#e9d5ff', // Light purple/lilac matching Image 2
            headerTextColor: '#581c87',
            columns: [
                { key: 'dialysis_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'dialysis_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'dialysis_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'dialysis_missed', label: 'ขาดการฟอกไต<br>(ราย)', width: '16%', isAlert: true }
            ]
        },
        'psychiatric': {
            id: 'psychiatric',
            title: '3. จิตเวช (ที่ต้องรับยา)',
            subtitle: 'การดูแลผู้ป่วยจิตเวชในพื้นที่น้ำท่วมและการส่งต่อยาต่อเนื่อง',
            headerColor: '#fed7aa',
            headerTextColor: '#7c2d12',
            columns: [
                { key: 'psychiatric_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'psychiatric_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'psychiatric_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'psychiatric_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '16%', isAlert: true }
            ]
        },
        'elderly': {
            id: 'elderly',
            title: '4. ผู้สูงอายุ (60 ปีขึ้นไป)',
            subtitle: 'การดูแลผู้สูงอายุที่มีโรคประจำตัวในพื้นที่น้ำท่วมและการสนับสนุนยา',
            headerColor: '#dbeafe',
            headerTextColor: '#1e3a8a',
            columns: [
                { key: 'elderly_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'elderly_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'elderly_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'elderly_out_of_meds', label: 'มีโรคประจำตัว/<br>ขาดยา (ราย)', width: '16%', isAlert: true }
            ]
        },
        'disabled': {
            id: 'disabled',
            title: '5. ผู้พิการ',
            subtitle: 'การดูแลผู้พิการในพื้นที่น้ำท่วม การอพยพ และสุขภาวะ',
            headerColor: '#ccfbf1',
            headerTextColor: '#115e59',
            columns: [
                { key: 'disabled_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'disabled_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'disabled_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'disabled_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '16%', isAlert: true }
            ]
        },
        'ncd': {
            id: 'ncd',
            title: '6. ผู้ป่วยโรคเรื้อรัง (NCD)',
            subtitle: 'ผู้ป่วยเบาหวาน/ความดันโลหิตสูงในพื้นที่น้ำท่วม และปัญหาการขาดยา',
            headerColor: '#fecdd3',
            headerTextColor: '#881337',
            columns: [
                { key: 'ncd_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'ncd_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'ncd_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'ncd_out_of_meds', label: 'ขาดยาประจำตัว<br>(ราย)', width: '16%', isAlert: true }
            ]
        },
        'pregnant': {
            id: 'pregnant',
            title: '7. หญิงตั้งครรภ์',
            subtitle: 'การดูแลหญิงตั้งครรภ์ การเฝ้าระวังวันใกล้คลอดในพื้นที่น้ำท่วม',
            headerColor: '#fae8ff',
            headerTextColor: '#701a75',
            columns: [
                { key: 'pregnant_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'pregnant_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'pregnant_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'pregnant_health_issue', label: 'พบปัญหา/เสี่ยง<br>ใกล้คลอด (ราย)', width: '16%', isAlert: true }
            ]
        },
        'children': {
            id: 'children',
            title: '8. เด็กอายุ (0-5 ปี)',
            subtitle: 'การดูแลเด็กเล็กและสุขอนามัยในพื้นที่ประสบอุทกภัย',
            headerColor: '#e0e7ff',
            headerTextColor: '#312e81',
            columns: [
                { key: 'children_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '16%', isFlood: true },
                { key: 'children_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '16%' },
                { key: 'children_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '16%' },
                { key: 'children_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '16%', isAlert: true }
            ]
        }
    };

    const blockKeys = Object.keys(topicBlocks);

    // Helper to get block icon
    function getBlockEmoji(blk) {
        const emojis = {
            'bedridden': '🛏️',
            'dialysis': '🩺',
            'psychiatric': '🧠',
            'elderly': '👵',
            'disabled': '♿',
            'ncd': '💊',
            'pregnant': '🤰',
            'children': '👶'
        };
        return emojis[blk] || '📋';
    }

    const prevBtn = document.getElementById('btn_prev_block');
    const nextBtn = document.getElementById('btn_next_block');

    function updateNavBtnStates() {
        const curIdx = blockKeys.indexOf(currentBlock);
        if (prevBtn) {
            prevBtn.disabled = (curIdx <= 0);
            prevBtn.style.opacity = (curIdx <= 0) ? '0.4' : '1';
            prevBtn.style.cursor = (curIdx <= 0) ? 'not-allowed' : 'pointer';
        }
        if (nextBtn) {
            nextBtn.disabled = (curIdx >= blockKeys.length - 1);
            nextBtn.style.opacity = (curIdx >= blockKeys.length - 1) ? '0.4' : '1';
            nextBtn.style.cursor = (curIdx >= blockKeys.length - 1) ? 'not-allowed' : 'pointer';
        }
    }

    // Block selector cards click
    document.querySelectorAll('.block-select-card').forEach(btn => {
        btn.addEventListener('click', function () {
            const blk = this.getAttribute('data-block');
            switchBlock(blk);
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            const curIdx = blockKeys.indexOf(currentBlock);
            if (curIdx > 0) switchBlock(blockKeys[curIdx - 1]);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            const curIdx = blockKeys.indexOf(currentBlock);
            if (curIdx < blockKeys.length - 1) switchBlock(blockKeys[curIdx + 1]);
        });
    }

    function switchBlock(blk) {
        if (!topicBlocks[blk]) return;
        currentBlock = blk;
        document.querySelectorAll('.block-select-card').forEach(b => {
            b.classList.remove('active');
            if (b.getAttribute('data-block') === blk) b.classList.add('active');
        });

        renderVillageTableForBlock(blk);
        updateNavBtnStates();
    }

    // Render Table Headers, Rows, and Footers for a specific Block
    function renderVillageTableForBlock(blockKey) {
        const blk = topicBlocks[blockKey];
        if (!blk) return;

        document.getElementById('block_title_display').innerHTML = `
            <span style="font-size: 1.4rem; vertical-align: middle;">${getBlockEmoji(blockKey)}</span>
            <span style="font-weight: 700; color: #0f2c4c; font-size: 1.2rem;">${blk.title}</span> 
            <span style="font-size: 0.95rem; font-weight: normal; color: #64748b; margin-left: 0.4rem;">— ${blk.subtitle}</span>
        `;

        const thead = document.getElementById('village_thead');
        const tbody = document.getElementById('village_tbody');
        const tfoot = document.getElementById('village_tfoot');

        // 1. Two-tier Table Header exactly like Excel
        let headerHtml = `
            <tr style="text-align: center;">
                <th rowspan="2" class="col-header-blue" style="width: 48px; min-width: 48px; vertical-align: middle;">ที่</th>
                <th rowspan="2" class="col-header-blue" style="width: 180px; min-width: 175px; vertical-align: middle;">ตำบล</th>
                <th rowspan="2" class="col-header-blue" style="width: 65px; min-width: 65px; vertical-align: middle;">หมู่</th>
                <th rowspan="2" class="col-header-blue" style="width: 145px; min-width: 135px; vertical-align: middle; background: #1e3a8a; border-right: 2px solid #3b82f6;">จำนวนคนทั้งหมด<br>ในหมู่ (ราย)</th>
                <th colspan="${blk.columns.length}" style="background-color: ${blk.headerColor}; color: ${blk.headerTextColor}; font-size: 1.05rem; padding: 0.55rem; font-weight: 700;">
                    ${blk.title}
                </th>
                <th rowspan="2" style="width: 55px; min-width: 55px; vertical-align: middle; background: #fee2e2; color: #991b1b; font-weight: 700;">ลบ</th>
            </tr>
            <tr style="text-align: center;">
        `;

        blk.columns.forEach(col => {
            const isFlood = col.isFlood;
            headerHtml += `
                <th style="min-width: 105px; width: ${col.width}; background-color: ${isFlood ? '#fee2e2' : '#f8fafc'}; color: ${isFlood ? '#991b1b' : '#334155'}; font-size: 0.85rem; padding: 0.5rem; font-weight: 600;">
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
                    <td style="font-weight: 600; color: #64748b; font-size: 0.95rem; vertical-align: middle;">${idx + 1}</td>
                    <td style="text-align: left; vertical-align: middle;">
                        <input type="text" class="village-input subdistrict-input" value="${escapeHtml(v.subdistrict || defaultSubdistrict)}" onchange="updateVillageField(${idx}, 'subdistrict', this.value)" placeholder="ระบุตำบล">
                    </td>
                    <td style="vertical-align: middle;">
                        <input type="number" min="1" class="village-input" style="font-weight: 700; color: #0f2c4c;" value="${v.village_no || (idx + 1)}" onchange="updateVillageField(${idx}, 'village_no', this.value)">
                    </td>
                    <td style="background: #eff6ff; border-right: 2px solid #bfdbfe; vertical-align: middle;">
                        <input type="number" min="0" class="village-input" style="font-weight: 700; color: #1e3a8a; font-size: 1.05rem; background: #ffffff;" value="${v.village_total || 0}" oninput="updateVillageTotal(${idx}, this.value)">
                    </td>
            `;

            blk.columns.forEach(col => {
                const val = v[col.key] !== undefined ? v[col.key] : 0;
                const isFlood = col.isFlood;
                html += `
                    <td style="vertical-align: middle;">
                        <input type="number" min="0" 
                            class="village-input ${isFlood ? 'flooded' : ''}" 
                            value="${val}" 
                            oninput="updateVillageNumber(${idx}, '${col.key}', this.value, '${blockKey}')"
                        >
                    </td>
                `;
            });

            html += `
                <td style="vertical-align: middle; text-align: center;">
                    <button type="button" class="btn-delete-row" onclick="removeVillageRow(${idx})" title="ลบหมู่ที่ ${v.village_no || (idx + 1)}" aria-label="ลบหมู่ที่ ${v.village_no || (idx + 1)}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
    }

    function renderVillageFooter(blockKey) {
        const blk = topicBlocks[blockKey];
        const tfoot = document.getElementById('village_tfoot');

        let totPop = 0;
        villageList.forEach(v => {
            totPop += parseInt(v.village_total || 0, 10);
        });

        let footHtml = `
            <tr style="text-align: center; border-top: 2px solid #cbd5e1;">
                <td colspan="3" style="text-align: right; padding-right: 1rem; color: #0f2c4c; font-size: 0.95rem; font-weight: 700;">
                    รวมทั้งตำบล (${villageList.length} หมู่บ้าน):
                </td>
                <td id="foot_sum_village_total" style="font-size: 1.05rem; font-weight: 700; color: #1e3a8a; background: #dbeafe; border-right: 2px solid #93c5fd;">
                    ${totPop.toLocaleString()}
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
    window.updateVillageTotal = function (idx, val) {
        if (!villageList[idx]) return;
        const num = Math.max(0, parseInt(val || 0, 10));
        villageList[idx].village_total = num;
        allTotalFields.forEach(f => {
            villageList[idx][f] = num;
        });

        // live update column sum in footer
        let sum = 0;
        villageList.forEach(v => {
            sum += parseInt(v.village_total || 0, 10);
        });
        const footCell = document.getElementById('foot_sum_village_total');
        if (footCell) footCell.textContent = sum.toLocaleString();
    };

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
            village_name: `หมู่ที่ ${nextMoo}`,
            village_total: 0
        };

        allTotalFields.forEach(f => { newRow[f] = 0; });

        // initialize all keys
        for (const [bk, blk] of Object.entries(topicBlocks)) {
            blk.columns.forEach(col => { newRow[col.key] = 0; });
        }

        villageList.push(newRow);
        renderVillageRows(currentBlock);
        renderVillageFooter(currentBlock);
        showToast('success', `เพิ่ม หมู่ที่ ${nextMoo} เรียบร้อย`);
    };

    window.removeVillageRow = function (idx) {
        if (confirm(`ยืนยันการลบหมู่ที่ ${villageList[idx].village_no} ออกจากตารางหรือไม่?`)) {
            villageList.splice(idx, 1);
            renderVillageRows(currentBlock);
            renderVillageFooter(currentBlock);
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
                    villageList.forEach(v => {
                        let synced = 0;
                        for (const f of allTotalFields) {
                            if (v[f] && parseInt(v[f], 10) > 0) {
                                synced = parseInt(v[f], 10);
                                break;
                            }
                        }
                        v.village_total = synced;
                        allTotalFields.forEach(f => {
                            v[f] = synced;
                        });
                    });

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

                        // Notes and requests
                        if (document.getElementById('notes')) {
                            document.getElementById('notes').value = r.notes || '';
                        }

                        statusBanner.className = 'gov-alert success';
                        statusBanner.innerHTML = `
                            <div class="gov-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                            <div>
                                <strong>หน่วยงานของท่านได้บันทึกรายงานสำหรับวันที่ ${dateStr} แล้ว (ส่วนที่ 1 และส่วนที่ 2)</strong><br>
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
                        
                        if (document.getElementById('notes')) {
                            document.getElementById('notes').value = '';
                        }

                        statusBanner.className = 'gov-alert warning';
                        statusBanner.innerHTML = `
                            <div class="gov-alert-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                            <div>
                                <strong>ยังไม่มีการส่งรายงานสำหรับวันที่ ${dateStr}</strong><br>
                                <span style="font-size: 0.85rem; opacity: 0.9;">กรุณาบันทึกข้อมูลส่วนที่ 1 สถานะบริการ และส่วนที่ 2 กลุ่มเปราะบางรายหมู่บ้าน</span>
                            </div>
                        `;
                        statusBanner.style.display = 'flex';
                    }

                    // Render active block table & update nav buttons
                    renderVillageTableForBlock(currentBlock);
                    updateNavBtnStates();
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
        setLoading(saveBtn, true, 'กำลังบันทึกข้อมูลส่วนที่ 1 - 2...');

        const fd = new FormData(form);
        // append villages JSON
        fd.append('villages', JSON.stringify(villageList));

        fetch('api/flood_report.php?action=save_report', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                setLoading(saveBtn, false, 'บันทึกส่งรายงานประจำวัน (ส่วนที่ 1 - 2)');
                if (res.status === 'success') {
                    showToast('success', res.message);
                    loadReportForDate(dateInput.value);
                    loadHistory();
                } else {
                    showToast('error', res.message || 'บันทึกไม่สำเร็จ');
                }
            })
            .catch(err => {
                setLoading(saveBtn, false, 'บันทึกส่งรายงานประจำวัน (ส่วนที่ 1 - 2)');
                showToast('error', 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ');
            });
    });

    // Date change
    dateInput.addEventListener('change', function () {
        formDate.value = this.value;
        updateSection3Links(this.value);
        loadReportForDate(this.value);
    });

    window.goToSection3 = function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const d = (dateInput && dateInput.value) ? dateInput.value : '<?= $selectedDate ?>';
        window.location.href = 'medical_services.php?report_date=' + encodeURIComponent(d);
    };

    function updateSection3Links(d) {
        if (!d) return;
        document.querySelectorAll('a[href*="medical_services.php"]').forEach(a => {
            a.href = 'medical_services.php?report_date=' + encodeURIComponent(d);
        });
    }

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
        const toggle = document.getElementById('facility_impact_toggle');
        const isCollapsed = b.hidden;
        b.hidden = !isCollapsed;
        toggle.setAttribute('aria-expanded', String(isCollapsed));
    };

    document.getElementById('facility_impact_toggle').addEventListener('click', window.toggleFacilityImpact);

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
    updateSection3Links(dateInput.value);
    loadReportForDate(dateInput.value);
    loadHistory();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
