<?php
// provincial_overview.php - Ang Thong Provincial Flood & Health Overview (Admin & Super Admin)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login('admin'); // Allows 'admin' and 'superadmin'
$user = current_user();

// Districts for filter
$districts = $pdo->query("SELECT id, name_th FROM districts ORDER BY id ASC")->fetchAll();

$todayDate = date('Y-m-d');
$pageTitle = 'ภาพรวมสถานการณ์อุทกภัยระดับจังหวัด - จังหวัดอ่างทอง';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Header & Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.85rem; color: var(--slate-500); margin-bottom: 0.25rem;">
                ส่วนงานบริหารยุทธศาสตร์ &gt; สสจ.อ่างทอง &gt; ศูนย์บริหารจัดการสถานการณ์อุทกภัย
            </div>
            <h1 style="font-size: 1.75rem; color: var(--navy-900);">
                สรุปยอดสถานการณ์อุทกภัยและสาธารณสุข ระดับจังหวัดอ่างทอง
            </h1>
            <div style="color: var(--slate-600); font-size: 0.95rem;">
                ศูนย์ข้อมูลกลางติดตามสถานบริการสาธารณสุขและกลุ่มเปราะบาง 7 อำเภอ (พ.ศ. 2569)
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <?php if ($user['role'] === 'superadmin'): ?>
                <a href="admin.php" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span>จัดการผู้ใช้ (CRUD)</span>
                </a>
            <?php endif; ?>
            <a href="report_entry.php" class="btn btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>บันทึกรายงานหน่วยงาน</span>
            </a>
            <button type="button" id="btn_refresh_overview" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <span>รีเฟรชข้อมูล</span>
            </button>
        </div>
    </div>

    <!-- Date Picker & District Filter Toolbar -->
    <div class="gov-card" style="margin-bottom: 2rem; border-top: 3px solid var(--navy-800);">
        <div style="padding: 1.15rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <!-- Date Filter -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <label for="overview_date" style="font-weight: 600; color: var(--navy-900); font-size: 0.95rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -3px; margin-right: 2px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    วันที่ของรายงาน:
                </label>
                <input type="date" id="overview_date" value="<?= $todayDate ?>" class="form-control" style="width: auto; font-weight: 600; padding: 0.45rem 0.85rem;">
                <button type="button" class="btn btn-sm btn-outline" onclick="setDateToday()">วันนี้</button>
            </div>

            <!-- District Filter -->
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <label for="overview_district" style="font-weight: 500; color: var(--slate-700); font-size: 0.9rem;">
                    พื้นที่อำเภอ:
                </label>
                <select id="overview_district" class="form-select" style="width: auto; min-width: 200px;">
                    <option value="0">-- ทุกอำเภอ (7 อำเภอ) --</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name_th']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards (Today's totals) -->
    <div class="stats-grid" id="kpi_cards_container">
        <!-- 1. Reports count -->
        <div class="stat-card">
            <div class="stat-icon primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="kpi_reports_count">- / -</div>
                <div class="stat-label">ส่งรายงานแล้ววันนี้ (แห่ง)</div>
            </div>
        </div>

        <!-- 2. Facilities affected -->
        <div class="stat-card" style="border-left: 4px solid var(--rose-600);">
            <div class="stat-icon rose">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="kpi_facilities_affected" style="color: var(--rose-700);">-</div>
                <div class="stat-label">สถานบริการได้รับผลกระทบ</div>
            </div>
        </div>

        <!-- 3. Total Vulnerable Flooded -->
        <div class="stat-card" style="border-left: 4px solid var(--gold-600); background: linear-gradient(to bottom, #ffffff, #fffbeb);">
            <div class="stat-icon gold">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="kpi_vulnerable_flooded" style="color: var(--gold-700);">-</div>
                <div class="stat-label">กลุ่มเปราะบางในพื้นที่น้ำท่วม (ราย)</div>
            </div>
        </div>

        <!-- 4. People served by mobile teams -->
        <div class="stat-card">
            <div class="stat-icon emerald">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            </div>
            <div class="stat-details">
                <div class="stat-value" id="kpi_people_served" style="color: var(--emerald-600);">-</div>
                <div class="stat-label">ประชาชนที่ได้รับบริการ (คน)</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Views -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--slate-200); padding-bottom: 2px;">
        <button type="button" class="tab-btn active" data-tab="tab_districts" style="font-family: var(--font-heading); font-weight: 600; padding: 0.65rem 1.25rem; background: none; border: none; border-bottom: 3px solid var(--navy-800); color: var(--navy-900); cursor: pointer; font-size: 0.95rem;">
            ภาพรวมแยก 7 อำเภอ
        </button>
        <button type="button" class="tab-btn" data-tab="tab_vulnerable" style="font-family: var(--font-heading); font-weight: 600; padding: 0.65rem 1.25rem; background: none; border: none; border-bottom: 3px solid transparent; color: var(--slate-500); cursor: pointer; font-size: 0.95rem;">
            สรุปยอด 8 กลุ่มเปราะบาง
        </button>
        <button type="button" class="tab-btn" data-tab="tab_facilities" style="font-family: var(--font-heading); font-weight: 600; padding: 0.65rem 1.25rem; background: none; border: none; border-bottom: 3px solid transparent; color: var(--slate-500); cursor: pointer; font-size: 0.95rem;">
            รายชื่อสถานบริการและสถานะการส่ง
        </button>
        <button type="button" class="tab-btn" data-tab="tab_logs" style="font-family: var(--font-heading); font-weight: 600; padding: 0.65rem 1.25rem; background: none; border: none; border-bottom: 3px solid transparent; color: var(--slate-500); cursor: pointer; font-size: 0.95rem;">
            ประวัติการปฏิบัติงานในระบบ (Audit Logs)
        </button>
    </div>

    <!-- TAB 1: สรุปภาพรวมแยก 7 อำเภอ -->
    <div id="tab_districts" class="tab-pane active">
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <span>ตารางสรุปสถานการณ์อุทกภัยจำแนกรายอำเภอ จังหวัดอ่างทอง</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="gov-table" style="text-align: center;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">อำเภอ</th>
                            <th>สถานบริการทั้งหมด</th>
                            <th>รายงานแล้ว (แห่ง)</th>
                            <th>ได้รับผลกระทบ</th>
                            <th>เปิดบางส่วน</th>
                            <th>ปิดบริการ</th>
                            <th style="color: var(--rose-700);">กลุ่มเปราะบางน้ำท่วม</th>
                            <th>ประชาชนรับบริการ</th>
                        </tr>
                    </thead>
                    <tbody id="district_breakdown_tbody">
                        <tr><td colspan="8" style="padding: 2rem;">กำลังโหลดข้อมูลสรุปรายอำเภอ...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: สรุปยอดกลุ่มเปราะบาง 8 กลุ่ม -->
    <div id="tab_vulnerable" class="tab-pane" style="display: none;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;" id="vulnerable_cards_grid">
            <!-- Populated via JS -->
        </div>

        <!-- Medical and Mobile teams card -->
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    <span>สรุปการออกปฏิบัติการด้านการแพทย์และสาธารณสุขทั้งจังหวัด</span>
                </div>
            </div>
            <div class="gov-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; text-align: center;">
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy-900);" id="med_mobile_clinics">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">หน่วยแพทย์เคลื่อนที่ (ครั้ง)</div>
                    </div>
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy-900);" id="med_mcatt">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">ทีมเยียวยาจิตใจ MCATT (ครั้ง)</div>
                    </div>
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy-900);" id="med_srrt">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">ทีมสอบสวนโรค SRRT (ครั้ง)</div>
                    </div>
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--emerald-600);" id="med_home_visits">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">ออกเยี่ยมบ้าน (หลัง)</div>
                    </div>
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy-900);" id="med_distribution">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">แจกจ่ายยา/เวชภัณฑ์ (ชุด)</div>
                    </div>
                    <div style="background: var(--slate-50); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--navy-900);" id="med_pfa">0</div>
                        <div style="font-size: 0.85rem; color: var(--slate-500);">ปฐมพยาบาลจิตใจ PFA (ราย)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: รายชื่อสถานบริการและสถานะการส่ง -->
    <div id="tab_facilities" class="tab-pane" style="display: none;">
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                    <span>สถานะการส่งรายงานของสถานบริการสาธารณสุขทั้งหมด (59 แห่ง)</span>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="facility_search" placeholder="ค้นหาชื่อสถานบริการ..." class="form-control" style="width: 240px; font-size: 0.85rem; padding: 0.4rem 0.75rem;">
                </div>
            </div>
            <div class="table-responsive">
                <table class="gov-table" id="facility_table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>สถานบริการ</th>
                            <th>อำเภอ</th>
                            <th>สถานะการส่งรายงาน</th>
                            <th>ผลกระทบ / สถานะเปิดบริการ</th>
                            <th>กลุ่มเปราะบางน้ำท่วม</th>
                            <th>เวลาบันทึก (NOW)</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody id="facilities_tbody">
                        <tr><td colspan="8" style="padding: 2rem; text-align: center;">กำลังโหลดข้อมูลสถานบริการ...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: ประวัติการปฏิบัติงานในระบบ (Audit Logs: Who, Where, What, NOW()) -->
    <div id="tab_logs" class="tab-pane" style="display: none;">
        <div class="gov-card">
            <div class="gov-card-header">
                <div class="gov-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>บันทึกประวัติการปฏิบัติงานในระบบ (Audit Logs: ใคร ที่ไหน ทำอะไร เวลาใด)</span>
                </div>
                <div>
                    <input type="text" id="log_search" placeholder="ค้นหาผู้ใช้, หน่วยงาน, หรือคำค้น..." class="form-control" style="width: 260px; font-size: 0.85rem; padding: 0.4rem 0.75rem;">
                </div>
            </div>
            <div class="table-responsive">
                <table class="gov-table" style="font-size: 0.875rem;">
                    <thead>
                        <tr>
                            <th style="width: 16%;">วันเวลาดำเนินการ (NOW)</th>
                            <th style="width: 20%;">ใคร (ผู้ใช้งาน)</th>
                            <th style="width: 22%;">ที่ไหน (อำเภอ / หน่วยงาน / IP)</th>
                            <th style="width: 14%;">วันที่ของรายงาน</th>
                            <th style="width: 28%;">การกระทำและรายละเอียด (What)</th>
                        </tr>
                    </thead>
                    <tbody id="audit_logs_tbody">
                        <tr><td colspan="5" style="text-align: center; padding: 2rem;">กำลังโหลดประวัติการปฏิบัติงาน...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: Facility Report Full View (พร้อมแจกแจงรายหมู่บ้านและล็อกหัวข้อ)
     ========================================== -->
<style>
.modal-tab-btn {
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.65rem 1.15rem;
    border: none;
    background: none;
    color: var(--slate-600);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.modal-tab-btn:hover {
    color: var(--navy-900);
    background: #f8fafc;
}
.modal-tab-btn.active {
    color: var(--navy-900);
    border-bottom-color: var(--gold-500);
    background: #f1f5f9;
}
.modal-block-btn {
    font-family: var(--font-heading);
    font-size: 0.825rem;
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--slate-300);
    background: #ffffff;
    color: var(--slate-700);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
}
.modal-block-btn:hover {
    background: #e2e8f0;
}
.modal-block-btn.active {
    background: #0f2c4c;
    color: #ffffff;
    border-color: #0f2c4c;
    font-weight: 600;
}
.col-header-blue {
    background-color: #60a5fa !important;
    color: #ffffff !important;
    font-weight: 600;
}
@media print {
    body * {
        visibility: hidden;
    }
    #report_detail_modal, #report_detail_modal * {
        visibility: visible;
    }
    #report_detail_modal {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
        background: none;
        padding: 0;
    }
    #report_detail_modal .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        box-shadow: none !important;
        border: none !important;
        transform: none !important;
    }
    #report_detail_modal .modal-footer, #report_detail_modal .modal-close {
        display: none !important;
    }
}
</style>

<div class="modal-overlay" id="report_detail_modal">
    <div class="modal-dialog" style="max-width: 1100px; width: 96vw; max-height: 92vh;">
        <div class="modal-header" style="background: linear-gradient(135deg, #0f2c4c 0%, #1e3a8a 100%); color: #ffffff;">
            <h3 class="modal-title" id="modal_report_title" style="color: #ffffff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <span>รายละเอียดรายงานสถานการณ์อุทกภัยประจำวัน</span>
            </h3>
            <button type="button" class="modal-close" style="color: #ffffff; opacity: 0.85;">&times;</button>
        </div>
        <div class="modal-body" id="modal_report_body" style="padding: 1.25rem 1.5rem; overflow-y: auto;">
            กำลังโหลดข้อมูล...
        </div>
        <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 0.85rem 1.5rem;">
            <button type="button" class="btn btn-outline" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>พิมพ์รายงาน / บันทึก PDF</span>
            </button>
            <button type="button" class="btn btn-primary btn-modal-cancel">ปิดหน้าต่าง</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('overview_date');
    const districtSelect = document.getElementById('overview_district');
    const refreshBtn = document.getElementById('btn_refresh_overview');

    // Tabs logic
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.borderBottomColor = 'transparent';
                b.style.color = 'var(--slate-500)';
            });
            tabPanes.forEach(p => p.style.display = 'none');

            this.classList.add('active');
            this.style.borderBottomColor = 'var(--navy-800)';
            this.style.color = 'var(--navy-900)';

            const targetId = this.getAttribute('data-tab');
            const targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.style.display = 'block';

            if (targetId === 'tab_logs') {
                loadAuditLogs();
            }
        });
    });

    dateInput.addEventListener('change', loadSummary);
    districtSelect.addEventListener('change', loadSummary);
    if (refreshBtn) refreshBtn.addEventListener('click', () => { loadSummary(); loadAuditLogs(); });

    let rawFacilityList = [];

    // Filter facility search
    const facSearch = document.getElementById('facility_search');
    if (facSearch) {
        facSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            renderFacilities(rawFacilityList.filter(f => 
                f.org_name.toLowerCase().includes(q) || f.district_name.toLowerCase().includes(q)
            ));
        });
    }

    // Filter log search
    const logSearch = document.getElementById('log_search');
    if (logSearch) {
        let logTimer = null;
        logSearch.addEventListener('input', function () {
            clearTimeout(logTimer);
            logTimer = setTimeout(() => loadAuditLogs(this.value.trim()), 300);
        });
    }

    function loadSummary() {
        const d = dateInput.value;
        const dist = districtSelect.value;

        fetch(`api/flood_report.php?action=provincial_summary&report_date=${d}&district_id=${dist}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    renderOverview(data);
                } else {
                    showToast('error', data.message || 'ไม่สามารถโหลดข้อมูลสรุปได้');
                }
            })
            .catch(err => console.error(err));
    }

    function renderOverview(data) {
        const s = data.summary;
        const totalFac = data.total_facilities || 59;
        const reportedCount = parseInt(s.total_reports || 0, 10);
        const percentReported = Math.round((reportedCount / totalFac) * 100);

        // 1. KPI Cards
        document.getElementById('kpi_reports_count').innerHTML = `${reportedCount} <span style="font-size: 1rem; color: #64748b;">/ ${totalFac} (${percentReported}%)</span>`;
        document.getElementById('kpi_facilities_affected').textContent = `${parseInt(s.count_affected || 0, 10)} แห่ง`;
        document.getElementById('kpi_vulnerable_flooded').textContent = `${parseInt(s.grand_vulnerable_flooded || 0, 10).toLocaleString()} ราย`;
        document.getElementById('kpi_people_served').textContent = `${parseInt(s.sum_people_served || 0, 10).toLocaleString()} คน`;

        // 2. District Breakdown Table
        const distTbody = document.getElementById('district_breakdown_tbody');
        if (data.districts_summary && distTbody) {
            let html = '';
            data.districts_summary.forEach((row, idx) => {
                const rep = parseInt(row.reported_count || 0);
                const tot = parseInt(row.total_facilities || 0);
                const isComplete = rep >= tot && tot > 0;

                html += `
                    <tr>
                        <td style="text-align: left; font-weight: 600; color: #0f2c4c;">${escapeHtml(row.district_name)}</td>
                        <td>${tot} แห่ง</td>
                        <td>
                            <span class="badge ${isComplete ? 'badge-approved' : 'badge-pending'}">
                                ${rep}/${tot} แห่ง
                            </span>
                        </td>
                        <td style="color: ${row.affected_facilities > 0 ? '#dc2626' : '#475569'}; font-weight: 600;">
                            ${row.affected_facilities || 0} แห่ง
                        </td>
                        <td>${row.partial_services || 0}</td>
                        <td>${row.closed_services || 0}</td>
                        <td style="color: ${row.flooded_vulnerable > 0 ? '#b91c1c' : '#334155'}; font-weight: 700; font-size: 1rem;">
                            ${parseInt(row.flooded_vulnerable || 0).toLocaleString()}
                        </td>
                        <td>${parseInt(row.people_served || 0).toLocaleString()}</td>
                    </tr>
                `;
            });
            distTbody.innerHTML = html;
        }

        // 3. Vulnerable 8 Groups Cards
        const vgGrid = document.getElementById('vulnerable_cards_grid');
        if (vgGrid) {
            const groups = [
                { title: '1. ผู้ป่วยติดเตียง', key: 'bedridden', icon: '🛏️', desc: 'Bedridden' },
                { title: '2. ผู้ป่วยฟอกไต', key: 'dialysis', icon: '🩺', desc: 'Hemodialysis' },
                { title: '3. จิตเวช (รับยา)', key: 'psychiatric', icon: '🧠', desc: 'Psychiatric' },
                { title: '4. ผู้สูงอายุ (60+)', key: 'elderly', icon: '👵', desc: 'Elderly' },
                { title: '5. ผู้พิการ', key: 'disabled', icon: '♿', desc: 'Disabled' },
                { title: '6. โรคเรื้อรัง (NCD)', key: 'ncd', icon: '💊', desc: 'Chronic NCD' },
                { title: '7. หญิงตั้งครรภ์', key: 'pregnant', icon: '🤰', desc: 'Pregnant' },
                { title: '8. เด็กอายุ 0-5 ปี', key: 'children', icon: '👶', desc: 'Children 0-5 yrs' }
            ];

            let html = '';
            groups.forEach(g => {
                const total = parseInt(s[`${g.key}_total`] || 0);
                const flooded = parseInt(s[`${g.key}_flooded`] || 0);
                const home = parseInt(s[`${g.key}_home`] || 0);
                const shelter = parseInt(s[`${g.key}_shelter`] || 0);

                html += `
                    <div class="gov-card" style="padding: 1.25rem; border-top: 3px solid ${flooded > 0 ? '#e11d48' : '#2563eb'};">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: #0f2c4c; font-size: 0.95rem;">${g.icon} ${g.title}</span>
                            <span style="font-size: 0.75rem; color: #64748b;">${g.desc}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.75rem;">
                            <div>
                                <span style="font-size: 0.8rem; color: #64748b;">ในพื้นที่น้ำท่วม:</span>
                                <div style="font-size: 1.5rem; font-weight: 700; color: ${flooded > 0 ? '#b91c1c' : '#334155'}; line-height: 1.1;">
                                    ${flooded.toLocaleString()} <span style="font-size: 0.85rem; font-weight: normal; color: #64748b;">ราย</span>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 0.8rem; color: #64748b;">
                                ทั้งหมด: <strong style="color: #0f172a;">${total.toLocaleString()}</strong>
                            </div>
                        </div>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 0.4rem 0.6rem; font-size: 0.775rem; display: flex; justify-content: space-between;">
                            <span>อยู่บ้าน: <strong>${home}</strong></span>
                            <span>อพยพ: <strong>${shelter}</strong></span>
                        </div>
                    </div>
                `;
            });
            vgGrid.innerHTML = html;

            // Medical team stats
            document.getElementById('med_mobile_clinics').textContent = parseInt(s.sum_mobile_clinics || 0).toLocaleString();
            document.getElementById('med_mcatt').textContent = parseInt(s.sum_mcatt || 0).toLocaleString();
            document.getElementById('med_srrt').textContent = parseInt(s.sum_srrt || 0).toLocaleString();
            document.getElementById('med_home_visits').textContent = parseInt(s.sum_home_visits || 0).toLocaleString();
            document.getElementById('med_distribution').textContent = parseInt(s.sum_med_distribution || 0).toLocaleString();
            document.getElementById('med_pfa').textContent = parseInt(s.sum_pfa || 0).toLocaleString();
        }

        // 4. Facility list
        rawFacilityList = data.facility_reports || [];
        renderFacilities(rawFacilityList);
    }

    function renderFacilities(list) {
        const tbody = document.getElementById('facilities_tbody');
        if (!tbody) return;

        if (!list || list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #64748b; padding: 2rem;">ไม่พบข้อมูลสถานบริการตามเงื่อนไข</td></tr>';
            return;
        }

        let html = '';
        list.forEach((f, idx) => {
            const hasReport = !!f.report_id;
            let reportBadge = hasReport 
                ? '<span class="badge badge-approved">ส่งรายงานแล้ว</span>' 
                : '<span class="badge badge-pending">ยังไม่ส่งรายงาน</span>';

            let serviceBadge = '-';
            if (hasReport) {
                if (f.impact_status === 'affected') {
                    if (f.service_status === 'partial') serviceBadge = '<span class="badge badge-pending">เปิดบางส่วน (น้ำท่วม)</span>';
                    else if (f.service_status === 'closed') serviceBadge = '<span class="badge badge-rejected">ปิดบริการ (น้ำท่วม)</span>';
                    else serviceBadge = '<span class="badge badge-approved">เปิดปกติ (มีผลกระทบ)</span>';
                } else {
                    serviceBadge = '<span class="badge badge-approved">เปิดบริการปกติ</span>';
                }
            }

            const floodedCount = parseInt(f.total_flooded || 0);

            html += `
                <tr>
                    <td style="color: #64748b; font-size: 0.85rem; width: 35px;">${idx + 1}</td>
                    <td>
                        <div style="font-weight: 600; color: #0f2c4c;">${escapeHtml(f.org_name)}</div>
                        <div style="font-size: 0.775rem; color: #64748b;">${escapeHtml(f.user_name ? 'ผู้รายงาน: ' + f.user_name : '')}</div>
                    </td>
                    <td>${escapeHtml(f.district_name)}</td>
                    <td>${reportBadge}</td>
                    <td>${serviceBadge}</td>
                    <td style="font-weight: 600; color: ${floodedCount > 0 ? '#b91c1c' : '#334155'};">
                        ${hasReport ? `${floodedCount} ราย` : '-'}
                    </td>
                    <td style="font-size: 0.825rem; color: #64748b;">
                        ${hasReport ? (f.updated_at || f.created_at) : '-'}
                    </td>
                    <td>
                        ${hasReport ? `
                            <button type="button" class="btn btn-sm btn-outline" onclick="viewFacilityReport(${f.org_id}, '${dateInput.value}')">
                                ดูฉบับเต็ม
                            </button>
                        ` : `
                            <span style="font-size: 0.825rem; color: #94a3b8;">รอรายงาน</span>
                        `}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    // Definition of the 8 Topic Blocks for Granular Village Breakdown in Modal
    const modalTopicBlocks = {
        'bedridden': {
            id: 'bedridden',
            title: '1. ผู้ป่วยติดเตียง',
            subtitle: 'การดูแลผู้ป่วยติดเตียงในพื้นที่รับผิดชอบและพื้นที่น้ำท่วม',
            headerColor: '#fef08a',
            headerTextColor: '#854d0e',
            columns: [
                { key: 'bedridden_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'bedridden_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'bedridden_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'bedridden_shelter', label: 'การช่วยเหลือ:<br>ศูนย์พักพิง (ราย)', width: '14%' },
                { key: 'bedridden_hospital', label: 'ส่งต่อ รพ.<br>(ราย)', width: '14%' }
            ]
        },
        'dialysis': {
            id: 'dialysis',
            title: '2. ผู้ป่วยฟอกไต',
            subtitle: 'การดูแลผู้ป่วยฟอกไตและปัญหาการขาดการฟอกไตในพื้นที่น้ำท่วม',
            headerColor: '#e9d5ff',
            headerTextColor: '#581c87',
            columns: [
                { key: 'dialysis_total', label: 'จำนวนทั้งหมด<br>ในหมู่ (ราย)', width: '14%' },
                { key: 'dialysis_flooded', label: 'จำนวนในพื้นที่ที่<br>น้ำท่วม (ราย)', width: '14%', isFlood: true },
                { key: 'dialysis_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'dialysis_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'dialysis_missed', label: 'ขาดการฟอกไต<br>(ราย)', width: '14%', isAlert: true }
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
                { key: 'psychiatric_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'psychiatric_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'psychiatric_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '14%', isAlert: true }
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
                { key: 'elderly_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'elderly_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'elderly_out_of_meds', label: 'มีโรคประจำตัว/<br>ขาดยา (ราย)', width: '14%', isAlert: true }
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
                { key: 'disabled_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'disabled_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'disabled_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '14%', isAlert: true }
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
                { key: 'ncd_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'ncd_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '13%' },
                { key: 'ncd_out_of_meds', label: 'ขาดยาประจำตัว<br>(ราย)', width: '14%', isAlert: true }
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
                { key: 'pregnant_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'pregnant_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'pregnant_health_issue', label: 'พบปัญหา/เสี่ยง<br>ใกล้คลอด (ราย)', width: '14%', isAlert: true }
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
                { key: 'children_home', label: 'การช่วยเหลือ:<br>อยู่บ้าน (ราย)', width: '14%' },
                { key: 'children_shelter', label: 'การช่วยเหลือ:<br>อพยพ (ราย)', width: '14%' },
                { key: 'children_health_issue', label: 'พบปัญหาด้าน<br>สุขภาพ (ราย)', width: '14%', isAlert: true }
            ]
        }
    };

    let currentModalBlock = 'bedridden';
    let currentModalVillages = [];

    window.switchModalTab = function (tabName) {
        document.querySelectorAll('.modal-tab-btn').forEach(btn => btn.classList.remove('active'));
        const btn = document.getElementById('modal_tab_' + tabName);
        if (btn) btn.classList.add('active');

        document.getElementById('modal_view_villages').style.display = (tabName === 'villages') ? 'block' : 'none';
        document.getElementById('modal_view_summary').style.display = (tabName === 'summary') ? 'block' : 'none';
        document.getElementById('modal_view_medical').style.display = (tabName === 'medical') ? 'block' : 'none';
    };

    window.switchModalBlock = function (blockKey) {
        currentModalBlock = blockKey;
        document.querySelectorAll('.modal-block-btn').forEach(btn => {
            if (btn.getAttribute('data-block') === blockKey) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        renderModalBlockTable(blockKey);
    };

    window.renderModalBlockTable = function (blockKey) {
        const block = modalTopicBlocks[blockKey];
        if (!block) return;

        const titleDiv = document.getElementById('modal_block_title_display');
        const thead = document.getElementById('modal_village_thead');
        const tbody = document.getElementById('modal_village_tbody');
        const tfoot = document.getElementById('modal_village_tfoot');

        if (!thead || !tbody || !tfoot) return;

        if (titleDiv) {
            titleDiv.innerHTML = `
                <div>
                    <span style="color: ${block.headerTextColor}; font-size: 1.05rem;">${block.title}</span>
                    <span style="font-size: 0.825rem; color: #64748b; font-weight: normal; margin-left: 0.5rem;">— ${block.subtitle}</span>
                </div>
                <div style="font-size: 0.825rem; color: #64748b; font-weight: normal;">
                    จำนวน <strong>${currentModalVillages.length}</strong> หมู่บ้านในพื้นที่
                </div>
            `;
        }

        // Render Thead
        let theadHtml = `
            <tr>
                <th rowspan="2" class="col-header-blue" style="width: 5%;">ที่</th>
                <th rowspan="2" class="col-header-blue" style="width: 15%;">ตำบล</th>
                <th rowspan="2" class="col-header-blue" style="width: 15%;">หมู่ที่ / ชุมชน</th>
                <th colspan="${block.columns.length}" style="background-color: ${block.headerColor}; color: ${block.headerTextColor}; font-weight: 700; border-bottom: 1px solid rgba(0,0,0,0.1);">
                    ${block.title}
                </th>
            </tr>
            <tr>
        `;
        block.columns.forEach(col => {
            theadHtml += `<th style="background-color: ${block.headerColor}; color: ${block.headerTextColor}; font-size: 0.8rem; width: ${col.width}; border-bottom: 2px solid rgba(0,0,0,0.15);">${col.label}</th>`;
        });
        theadHtml += `</tr>`;
        thead.innerHTML = theadHtml;

        // Render Tbody
        if (currentModalVillages.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${3 + block.columns.length}" style="padding: 2.5rem; color: #94a3b8; text-align: center;">ไม่มีข้อมูลรายชื่อหมู่บ้านในระบบ</td></tr>`;
            tfoot.innerHTML = '';
            return;
        }

        const totals = {};
        block.columns.forEach(col => totals[col.key] = 0);

        let tbodyHtml = '';
        currentModalVillages.forEach((v, idx) => {
            const vNo = v.village_no || (idx + 1);
            const vName = v.village_name || `หมู่ที่ ${vNo}`;
            const subName = v.subdistrict || '-';

            tbodyHtml += `
                <tr>
                    <td style="color: #64748b; font-size: 0.825rem;">${idx + 1}</td>
                    <td style="font-weight: 500;">${escapeHtml(subName)}</td>
                    <td style="font-weight: 600; color: #0f2c4c;">${escapeHtml(vName)}</td>
            `;

            block.columns.forEach(col => {
                const val = parseInt(v[col.key], 10) || 0;
                totals[col.key] += val;

                let cellStyle = 'font-size: 0.9rem;';
                let cellContent = val;
                if (col.isFlood) {
                    if (val > 0) {
                        cellStyle += 'font-weight: 700; color: #b91c1c; background-color: #fef2f2;';
                    } else {
                        cellStyle += 'color: #64748b;';
                    }
                } else if (col.isAlert) {
                    if (val > 0) {
                        cellStyle += 'font-weight: 700; color: #b91c1c; background-color: #fff1f2;';
                    } else {
                        cellStyle += 'color: #64748b;';
                    }
                }

                tbodyHtml += `<td style="${cellStyle}">${cellContent}</td>`;
            });

            tbodyHtml += `</tr>`;
        });
        tbody.innerHTML = tbodyHtml;

        // Render Tfoot
        let tfootHtml = `
            <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
                <td colspan="3" style="text-align: right; font-weight: 700; color: #0f2c4c; padding-right: 1.25rem;">
                    รวมทั้งหน่วยงาน (${currentModalVillages.length} หมู่บ้าน):
                </td>
        `;
        block.columns.forEach(col => {
            const totVal = totals[col.key];
            let footStyle = 'font-weight: 700; font-size: 0.95rem;';
            if (col.isFlood || col.isAlert) {
                footStyle += (totVal > 0) ? 'color: #b91c1c; background-color: #fee2e2;' : 'color: #334155;';
            } else {
                footStyle += 'color: #0f2c4c;';
            }
            tfootHtml += `<td style="${footStyle}">${totVal}</td>`;
        });
        tfootHtml += `</tr>`;
        tfoot.innerHTML = tfootHtml;
    };

    // Modal view full report (Executive summary + Village-by-village Topic Blocks)
    window.viewFacilityReport = function (orgId, dateStr) {
        const modal = document.getElementById('report_detail_modal');
        const mTitle = document.getElementById('modal_report_title');
        const mBody = document.getElementById('modal_report_body');

        mBody.innerHTML = '<div style="text-align: center; padding: 3rem;"><div class="badge badge-pending" style="font-size: 0.95rem; padding: 0.6rem 1.25rem;">กำลังโหลดข้อมูลรายงานและรายชื่อหมู่บ้าน...</div></div>';
        modal.classList.add('active');

        fetch(`api/flood_report.php?action=get_report&organization_id=${orgId}&report_date=${dateStr}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && (res.report || (res.villages && res.villages.length > 0))) {
                    const r = res.report || {};
                    const villages = res.villages || [];
                    const orgName = r.organization_name || (res.org_info ? res.org_info.name_th : 'หน่วยงาน');
                    const distName = r.district_name || (res.org_info ? res.org_info.district_name : 'จังหวัดอ่างทอง');

                    mTitle.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <span>รายงานสถานการณ์อุทกภัย: <strong>${escapeHtml(orgName)}</strong></span>
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff; border: 1px solid rgba(255,255,255,0.3); font-weight: 500;">
                                วันที่ ${escapeHtml(dateStr)}
                            </span>
                        </div>
                    `;

                    // Status Badges
                    let servBadge = '<span class="badge badge-approved">เปิดบริการปกติ</span>';
                    if (r.service_status === 'partial') servBadge = '<span class="badge badge-pending">เปิดบริการบางส่วน</span>';
                    else if (r.service_status === 'closed') servBadge = '<span class="badge badge-rejected">ปิดบริการชั่วคราว</span>';

                    let impBadge = (r.impact_status === 'affected')
                        ? '<span class="badge badge-rejected">สถานบริการได้รับผลกระทบน้ำท่วม</span>'
                        : '<span class="badge badge-approved">สถานบริการปลอดภัย / ไม่กระทบ</span>';

                    mBody.innerHTML = `
                        <!-- Executive Summary Bar -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem; font-size: 0.9rem;">
                                <div><strong>อำเภอ / สังกัด:</strong> ${escapeHtml(distName)} • จ.อ่างทอง</div>
                                <div><strong>สถานะสถานบริการ:</strong> ${servBadge} ${impBadge}</div>
                                <div><strong>ผู้รายงาน:</strong> ${escapeHtml(r.user_name || 'เจ้าหน้าที่')}</div>
                                <div><strong>บันทึกล่าสุด (NOW):</strong> <code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem;">${r.updated_at || r.created_at || '-'}</code></div>
                            </div>
                            ${r.impact_details ? `<div style="margin-top: 0.65rem; color: #991b1b; font-size: 0.85rem; background: #fef2f2; padding: 0.5rem 0.75rem; border-radius: 4px; border-left: 3px solid #ef4444;"><strong>ผลกระทบต่อสถานบริการ:</strong> ${escapeHtml(r.impact_details)}</div>` : ''}
                            ${r.substitute_location ? `<div style="margin-top: 0.35rem; color: #0f2c4c; font-size: 0.85rem; background: #eff6ff; padding: 0.5rem 0.75rem; border-radius: 4px; border-left: 3px solid #3b82f6;"><strong>เปิดจุดบริการทดแทนที่:</strong> ${escapeHtml(r.substitute_location)}</div>` : ''}
                        </div>

                        <!-- Inner Tabs for Modal -->
                        <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.25rem; overflow-x: auto; white-space: nowrap;">
                            <button type="button" class="modal-tab-btn active" id="modal_tab_villages" onclick="switchModalTab('villages')">
                                📋 จำแนกรายหมู่บ้าน (แยกตามล็อกหัวข้อ)
                            </button>
                            <button type="button" class="modal-tab-btn" id="modal_tab_summary" onclick="switchModalTab('summary')">
                                📊 สรุปยอดรวมหน่วยงาน (8 กลุ่มเปราะบาง)
                            </button>
                            <button type="button" class="modal-tab-btn" id="modal_tab_medical" onclick="switchModalTab('medical')">
                                🚑 ทีมปฏิบัติการ & การแพทย์เคลื่อนที่
                            </button>
                        </div>

                        <!-- View 1: Granular Village Breakdown with Topic Block Buttons -->
                        <div id="modal_view_villages">
                            <div style="background: #f1f5f9; border-radius: var(--radius-md); padding: 0.65rem 0.85rem; margin-bottom: 1rem; display: flex; gap: 0.4rem; overflow-x: auto; white-space: nowrap;">
                                <button type="button" class="modal-block-btn active" data-block="bedridden" onclick="switchModalBlock('bedridden')">🛏️ ล็อก 1: ติดเตียง</button>
                                <button type="button" class="modal-block-btn" data-block="dialysis" onclick="switchModalBlock('dialysis')">🩺 ล็อก 2: ฟอกไต</button>
                                <button type="button" class="modal-block-btn" data-block="psychiatric" onclick="switchModalBlock('psychiatric')">🧠 ล็อก 3: จิตเวช</button>
                                <button type="button" class="modal-block-btn" data-block="elderly" onclick="switchModalBlock('elderly')">👵 ล็อก 4: ผู้สูงอายุ</button>
                                <button type="button" class="modal-block-btn" data-block="disabled" onclick="switchModalBlock('disabled')">♿ ล็อก 5: ผู้พิการ</button>
                                <button type="button" class="modal-block-btn" data-block="ncd" onclick="switchModalBlock('ncd')">💊 ล็อก 6: NCD</button>
                                <button type="button" class="modal-block-btn" data-block="pregnant" onclick="switchModalBlock('pregnant')">🤰 ล็อก 7: หญิงตั้งครรภ์</button>
                                <button type="button" class="modal-block-btn" data-block="children" onclick="switchModalBlock('children')">👶 ล็อก 8: เด็ก 0-5 ปี</button>
                            </div>

                            <div id="modal_block_title_display" style="font-weight: 700; color: #0f2c4c; font-size: 1.05rem; margin-bottom: 0.65rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                <!-- dynamically set -->
                            </div>

                            <div class="table-responsive" style="max-height: 48vh; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: var(--radius-md);">
                                <table class="gov-table" style="text-align: center; border-collapse: separate; width: 100%;">
                                    <thead id="modal_village_thead" style="position: sticky; top: 0; z-index: 5;">
                                        <!-- dynamic columns -->
                                    </thead>
                                    <tbody id="modal_village_tbody">
                                        <!-- village rows -->
                                    </tbody>
                                    <tfoot id="modal_village_tfoot" style="position: sticky; bottom: 0; z-index: 4; background: #f8fafc; font-weight: 700;">
                                        <!-- summary total row -->
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- View 2: Rollup Summary -->
                        <div id="modal_view_summary" style="display: none;">
                            <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: #0f2c4c;">สรุปยอดรวมกลุ่มเปราะบางทั้งหมดของสถานบริการ</h4>
                            <div class="table-responsive" style="margin-bottom: 1.25rem;">
                                <table class="gov-table" style="font-size: 0.875rem; text-align: center;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left;">กลุ่มเปราะบาง</th>
                                            <th>จำนวนทั้งหมด</th>
                                            <th style="color: #b91c1c;">ในพื้นที่น้ำท่วม</th>
                                            <th>การช่วยเหลือ: อยู่บ้าน</th>
                                            <th>การช่วยเหลือ: ศูนย์อพยพ</th>
                                            <th>ส่งต่อ รพ. / ปัญหาเฉพาะ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td style="text-align: left; font-weight: 600;">1. ผู้ป่วยติดเตียง</td><td>${r.bedridden_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.bedridden_flooded || 0}</td><td>${r.bedridden_home || 0}</td><td>${r.bedridden_shelter || 0}</td><td>${r.bedridden_hospital || 0} (ส่งต่อ รพ.)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">2. ผู้ป่วยฟอกไต</td><td>${r.dialysis_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.dialysis_flooded || 0}</td><td>${r.dialysis_home || 0}</td><td>${r.dialysis_shelter || 0}</td><td style="color:${(r.dialysis_missed || 0) > 0 ? '#b91c1c' : 'inherit'}; font-weight:${(r.dialysis_missed || 0) > 0 ? '700' : 'normal'};">${r.dialysis_missed || 0} (ไม่ได้ฟอกไต)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">3. จิตเวช (รับยา)</td><td>${r.psychiatric_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.psychiatric_flooded || 0}</td><td>${r.psychiatric_home || 0}</td><td>${r.psychiatric_shelter || 0}</td><td style="color:${(r.psychiatric_health_issue || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.psychiatric_health_issue || 0} (มีปัญหา)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">4. ผู้สูงอายุ (60+)</td><td>${r.elderly_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.elderly_flooded || 0}</td><td>${r.elderly_home || 0}</td><td>${r.elderly_shelter || 0}</td><td style="color:${(r.elderly_out_of_meds || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.elderly_out_of_meds || 0} (ขาดยา)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">5. ผู้พิการ</td><td>${r.disabled_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.disabled_flooded || 0}</td><td>${r.disabled_home || 0}</td><td>${r.disabled_shelter || 0}</td><td style="color:${(r.disabled_health_issue || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.disabled_health_issue || 0} (มีปัญหา)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">6. โรคเรื้อรัง (NCD)</td><td>${r.ncd_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.ncd_flooded || 0}</td><td>${r.ncd_home || 0}</td><td>${r.ncd_shelter || 0}</td><td style="color:${(r.ncd_out_of_meds || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.ncd_out_of_meds || 0} (ขาดยา)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">7. หญิงตั้งครรภ์</td><td>${r.pregnant_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.pregnant_flooded || 0}</td><td>${r.pregnant_home || 0}</td><td>${r.pregnant_shelter || 0}</td><td style="color:${(r.pregnant_health_issue || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.pregnant_health_issue || 0} (เสี่ยงใกล้คลอด)</td></tr>
                                        <tr><td style="text-align: left; font-weight: 600;">8. เด็กอายุ 0-5 ปี</td><td>${r.children_total || 0}</td><td style="font-weight:700; color:#b91c1c;">${r.children_flooded || 0}</td><td>${r.children_home || 0}</td><td>${r.children_shelter || 0}</td><td style="color:${(r.children_health_issue || 0) > 0 ? '#b91c1c' : 'inherit'};">${r.children_health_issue || 0} (เจ็บป่วย)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            ${r.notes ? `<div style="background: #fffbeb; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #fde68a; font-size: 0.9rem; line-height: 1.6;"><strong>📝 ข้อเสนอแนะ / ความต้องการสนับสนุน:</strong><br>${escapeHtml(r.notes)}</div>` : ''}
                        </div>

                        <!-- View 3: Medical Outreach -->
                        <div id="modal_view_medical" style="display: none;">
                            <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: #0f2c4c;">การให้บริการด้านการแพทย์และสาธารณสุขประจำวัน</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                                <div style="background: #eff6ff; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #bfdbfe; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #1e40af; font-weight: 600;">หน่วยแพทย์เคลื่อนที่</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #1e3a8a;">${r.mobile_clinic_visits || 0} <span style="font-size: 0.85rem; font-weight: normal;">ครั้ง</span></div>
                                </div>
                                <div style="background: #fdf2f8; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #fbcfe8; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #9d174d; font-weight: 600;">ทีม MCATT เยียวยาจิตใจ</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #831843;">${r.mcatt_visits || 0} <span style="font-size: 0.85rem; font-weight: normal;">ครั้ง</span></div>
                                </div>
                                <div style="background: #ecfdf5; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #a7f3d0; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #065f46; font-weight: 600;">ทีมสอบสวนโรค SRRT</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #064e3b;">${r.srrt_visits || 0} <span style="font-size: 0.85rem; font-weight: normal;">ครั้ง</span></div>
                                </div>
                                <div style="background: #fefce8; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #fef08a; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #854d0e; font-weight: 600;">ประชาชนรับบริการ</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #713f12;">${r.people_served || 0} <span style="font-size: 0.85rem; font-weight: normal;">คน</span></div>
                                </div>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #475569; font-weight: 600;">ออกเยี่ยมบ้าน</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0f172a;">${r.home_visits || 0} <span style="font-size: 0.85rem; font-weight: normal;">หลัง/ราย</span></div>
                                </div>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #475569; font-weight: 600;">แจกยา/เวชภัณฑ์</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0f172a;">${r.med_distribution || 0} <span style="font-size: 0.85rem; font-weight: normal;">ชุด</span></div>
                                </div>
                                <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid #e2e8f0; text-align: center;">
                                    <div style="font-size: 0.8rem; color: #475569; font-weight: 600;">ปฐมพยาบาลทางใจ PFA</div>
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0f172a;">${r.pfa_support || 0} <span style="font-size: 0.85rem; font-weight: normal;">ราย</span></div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Store current modal villages in memory
                    currentModalVillages = villages;
                    renderModalBlockTable('bedridden');

                } else {
                    mBody.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 3rem;">ไม่พบข้อมูลรายงานของหน่วยงานนี้สำหรับวันที่ระบุ</div>';
                }
            })
            .catch(err => {
                mBody.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 3rem;">เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + escapeHtml(err.message) + '</div>';
            });
    };

    // Load Audit Logs (Who, Where, What, NOW())
    function loadAuditLogs(searchQuery = '') {
        const tbody = document.getElementById('audit_logs_tbody');
        if (!tbody) return;

        fetch(`api/flood_report.php?action=get_audit_logs&search=${encodeURIComponent(searchQuery)}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.logs) {
                    if (data.logs.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 2rem;">ไม่พบประวัติการปฏิบัติงาน</td></tr>';
                        return;
                    }

                    let html = '';
                    data.logs.forEach(l => {
                        let actionLabel = l.action;
                        if (l.action === 'submit_flood_report') actionLabel = '<span class="badge badge-approved">บันทึกรายงานใหม่</span>';
                        else if (l.action === 'update_flood_report') actionLabel = '<span class="badge badge-pending">อัปเดตรายงาน</span>';
                        else if (l.action === 'login_success') actionLabel = '<span class="badge" style="background:#eff6ff; color:#1d4ed8;">เข้าสู่ระบบ</span>';
                        else if (l.action === 'approve_user') actionLabel = '<span class="badge badge-approved">อนุมัติผู้ใช้</span>';
                        else if (l.action === 'reject_user') actionLabel = '<span class="badge badge-rejected">ปฏิเสธผู้ใช้</span>';
                        else if (l.action === 'update_user') actionLabel = '<span class="badge badge-role-user">แก้ไขข้อมูลผู้ใช้</span>';

                        html += `
                            <tr>
                                <td style="font-weight: 600; color: #0f2c4c; font-size: 0.825rem; white-space: nowrap;">
                                    ${l.created_at}
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">${escapeHtml(l.fullname || l.username || 'System')}</div>
                                    <div style="font-size: 0.775rem; color: #64748b;">@${escapeHtml(l.username || '-')}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">${escapeHtml(l.organization_name || '-')}</div>
                                    <div style="font-size: 0.775rem; color: #64748b;">
                                        ${escapeHtml(l.district_name || '')} ${l.ip_address ? `• IP: ${l.ip_address}` : ''}
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 500;">
                                    ${l.report_date ? `<code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">${l.report_date}</code>` : '-'}
                                </td>
                                <td>
                                    <div style="margin-bottom: 2px;">${actionLabel}</div>
                                    <div style="font-size: 0.825rem; color: #475569;">${escapeHtml(l.details || '')}</div>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
            });
    }

    window.setDateToday = function () {
        dateInput.value = '<?= $todayDate ?>';
        loadSummary();
    };

    // Initial load
    loadSummary();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
