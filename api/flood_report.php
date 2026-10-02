<?php
// api/flood_report.php - Flood reporting, Village-by-village data & Provincial Overview API
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนดำเนินการ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = current_user();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// 8 Vulnerable Groups Integer fields list
$vulnerableFields = [
    'bedridden_total', 'bedridden_flooded', 'bedridden_home', 'bedridden_shelter', 'bedridden_hospital',
    'dialysis_total', 'dialysis_flooded', 'dialysis_home', 'dialysis_shelter', 'dialysis_missed',
    'psychiatric_total', 'psychiatric_flooded', 'psychiatric_home', 'psychiatric_shelter', 'psychiatric_health_issue',
    'elderly_total', 'elderly_flooded', 'elderly_home', 'elderly_shelter', 'elderly_out_of_meds',
    'disabled_total', 'disabled_flooded', 'disabled_home', 'disabled_shelter', 'disabled_health_issue',
    'ncd_total', 'ncd_flooded', 'ncd_home', 'ncd_shelter', 'ncd_out_of_meds',
    'pregnant_total', 'pregnant_flooded', 'pregnant_home', 'pregnant_shelter', 'pregnant_health_issue',
    'children_total', 'children_flooded', 'children_home', 'children_shelter', 'children_health_issue'
];

$serviceFields = [
    'mobile_clinic_visits', 'mcatt_visits', 'srrt_visits', 'people_served', 'home_visits', 'med_distribution', 'pfa_support', 'doctor_consults'
];

$allIntFields = array_merge($vulnerableFields, $serviceFields);

// Helper to determine subdistrict and default village count
function get_subdistrict_defaults($orgName) {
    $defaults = [
        'มงคลธรรมนิมิต' => 8,
        'สามโก้' => 10,
        'ราษฎรพัฒนา' => 6,
        'ราษฏรพัฒนา' => 6,
        'โพธิ์ม่วงพันธ์' => 7,
        'อบทม' => 6,
        'จรเข้ร้อง' => 7,
        'ไชยภูมิ' => 8,
        'ชัยฤทธิ์' => 6,
        'หลักแก้ว' => 6,
        'มหานาม' => 7,
        'บางเสด็จ' => 6,
        'โผงเผง' => 7,
        'บางปลากด' => 6,
        'นรสิงห์' => 7,
        'อ่างแก้ว' => 7,
        'อินทประมูล' => 7,
        'บางพลับ' => 6,
        'รำมะสัก' => 12,
        'บ้านพราน' => 10,
        'วังน้ำเย็น' => 7,
        'สีบัวทอง' => 9,
        'จำลอง' => 7,
        'ศาลเจ้าโรงทอง' => 11,
        'ไผ่จำศีล' => 7,
        'หัวตะพาน' => 7,
        'สาวร้องไห้' => 8,
        'คลองขนาก' => 9,
        'บ้านอิฐ' => 11,
        'ศาลาแดง' => 8,
        'จำปาหล่อ' => 7,
        'โพสะ' => 7
    ];

    $matchedSubdistrict = '';
    $count = 6;

    foreach ($defaults as $sub => $mooCount) {
        if (mb_strpos($orgName, $sub) !== false) {
            $matchedSubdistrict = $sub;
            $count = $mooCount;
            break;
        }
    }

    if (empty($matchedSubdistrict)) {
        // clean "รพ.สต." from orgName
        $clean = str_replace(['รพ.สต.', 'โรงพยาบาลส่งเสริมสุขภาพตำบล', 'บ้าน', 'สำนักงานสาธารณสุขอำเภอ', 'โรงพยาบาล'], '', $orgName);
        $matchedSubdistrict = trim(explode(' ', $clean)[0]);
        if (empty($matchedSubdistrict)) $matchedSubdistrict = 'ตำบลในพื้นที่';
    }

    return ['subdistrict' => $matchedSubdistrict, 'count' => $count];
}

switch ($action) {
    case 'save_report':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            resp('error', 'Method not allowed');
        }

        $reportDate = trim($_POST['report_date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
            resp('error', 'รูปแบบวันที่ของรายงานไม่ถูกต้อง (ต้องเป็น YYYY-MM-DD)');
        }

        // Determine facility
        $orgId = $currentUser['organization_id'];
        $districtId = $currentUser['district_id'];

        if (in_array($currentUser['role'], ['admin', 'superadmin']) && !empty($_POST['organization_id'])) {
            $orgId = intval($_POST['organization_id']);
        }

        if (!$orgId) {
            resp('error', 'ไม่พบข้อมูลสังกัดหน่วยงานของผู้ใช้งาน');
        }

        // Fetch official org & district details
        $stmtOrg = $pdo->prepare("
            SELECT o.id, o.name_th as org_name, d.id as district_id, d.name_th as district_name 
            FROM organizations o 
            JOIN districts d ON o.district_id = d.id 
            WHERE o.id = ?
        ");
        $stmtOrg->execute([$orgId]);
        $orgData = $stmtOrg->fetch();

        if (!$orgData) {
            resp('error', 'หน่วยงานที่เลือกไม่มีอยู่ในระบบ');
        }

        $districtId = $orgData['district_id'];
        $districtName = $orgData['district_name'];
        $orgName = $orgData['org_name'];

        // Facility Impact
        $impactStatus = in_array($_POST['impact_status'] ?? '', ['normal', 'affected']) ? $_POST['impact_status'] : 'normal';
        $serviceStatus = in_array($_POST['service_status'] ?? '', ['normal', 'partial', 'closed']) ? $_POST['service_status'] : 'normal';
        $impactDetails = trim($_POST['impact_details'] ?? '');
        $substituteLocation = trim($_POST['substitute_location'] ?? '');

        // Services
        $vData = [];
        foreach ($allIntFields as $f) {
            $vData[$f] = max(0, intval($_POST[$f] ?? 0));
        }

        // Villages array
        $villagesRaw = $_POST['villages'] ?? $_POST['villages_json'] ?? '';
        if (is_string($villagesRaw) && !empty($villagesRaw)) {
            $villages = json_decode($villagesRaw, true);
        } elseif (is_array($villagesRaw)) {
            $villages = $villagesRaw;
        } else {
            $villages = [];
        }
        if (!is_array($villages)) {
            $villages = [];
        }

        $notes = trim($_POST['notes'] ?? '');

        try {
            $pdo->beginTransaction();

            // 1. Check if report already exists for this org and date
            $checkStmt = $pdo->prepare("SELECT id FROM flood_reports WHERE organization_id = ? AND report_date = ?");
            $checkStmt->execute([$orgId, $reportDate]);
            $existing = $checkStmt->fetch();

            $reportId = 0;

            if ($existing) {
                $reportId = $existing['id'];
                // Update Base Report
                $sql = "UPDATE flood_reports SET 
                    user_id = ?, user_name = ?, district_id = ?, district_name = ?, organization_id = ?, organization_name = ?,
                    impact_status = ?, service_status = ?, impact_details = ?, substitute_location = ?,
                    mobile_clinic_visits = ?, mcatt_visits = ?, srrt_visits = ?, people_served = ?, home_visits = ?, med_distribution = ?, pfa_support = ?, doctor_consults = ?,
                    notes = ?, updated_at = NOW()
                    WHERE id = ?";

                $params = [
                    $currentUser['id'], $currentUser['fullname'], $districtId, $districtName, $orgId, $orgName,
                    $impactStatus, $serviceStatus, $impactDetails, $substituteLocation,
                    $vData['mobile_clinic_visits'], $vData['mcatt_visits'], $vData['srrt_visits'], $vData['people_served'],
                    $vData['home_visits'], $vData['med_distribution'], $vData['pfa_support'], $vData['doctor_consults'],
                    $notes, $reportId
                ];
                $pdo->prepare($sql)->execute($params);

            } else {
                // Insert New Base Report
                $columns = "report_date, user_id, user_name, district_id, district_name, organization_id, organization_name, impact_status, service_status, impact_details, substitute_location, " . implode(', ', $serviceFields) . ", notes, created_at, updated_at";
                $placeholders = str_repeat('?, ', count($serviceFields) + 12) . 'NOW(), NOW()';

                $sql = "INSERT INTO flood_reports ({$columns}) VALUES ({$placeholders})";
                $params = [
                    $reportDate, $currentUser['id'], $currentUser['fullname'], $districtId, $districtName, $orgId, $orgName,
                    $impactStatus, $serviceStatus, $impactDetails, $substituteLocation
                ];
                foreach ($serviceFields as $sf) { $params[] = $vData[$sf]; }
                $params[] = $notes;

                $pdo->prepare($sql)->execute($params);
                $reportId = $pdo->lastInsertId();
            }

            // 2. Process Village Breakdown
            if (!empty($villages) && is_array($villages)) {
                // Delete existing villages for this report
                $pdo->prepare("DELETE FROM flood_report_villages WHERE report_id = ?")->execute([$reportId]);

                $stmtVillage = $pdo->prepare("
                    INSERT INTO flood_report_villages (
                        report_id, report_date, organization_id, subdistrict, village_no, village_name,
                        bedridden_total, bedridden_flooded, bedridden_home, bedridden_shelter, bedridden_hospital,
                        dialysis_total, dialysis_flooded, dialysis_home, dialysis_shelter, dialysis_missed,
                        psychiatric_total, psychiatric_flooded, psychiatric_home, psychiatric_shelter, psychiatric_health_issue,
                        elderly_total, elderly_flooded, elderly_home, elderly_shelter, elderly_out_of_meds,
                        disabled_total, disabled_flooded, disabled_home, disabled_shelter, disabled_health_issue,
                        ncd_total, ncd_flooded, ncd_home, ncd_shelter, ncd_out_of_meds,
                        pregnant_total, pregnant_flooded, pregnant_home, pregnant_shelter, pregnant_health_issue,
                        children_total, children_flooded, children_home, children_shelter, children_health_issue
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?
                    )
                ");

                // Initialize rollups
                $rollups = array_fill_keys($vulnerableFields, 0);

                foreach ($villages as $v) {
                    $subdistrict = trim($v['subdistrict'] ?? '');
                    $vNo = intval($v['village_no'] ?? 1);
                    $vName = trim($v['village_name'] ?? '');

                    $rowVals = [$reportId, $reportDate, $orgId, $subdistrict, $vNo, $vName];

                    foreach ($vulnerableFields as $vf) {
                        $val = max(0, intval($v[$vf] ?? 0));
                        $rowVals[] = $val;
                        $rollups[$vf] += $val;
                    }

                    $stmtVillage->execute($rowVals);
                }

                // Update the rollup totals in flood_reports
                $setParts = [];
                $setValues = [];
                foreach ($vulnerableFields as $vf) {
                    $setParts[] = "`{$vf}` = ?";
                    $setValues[] = $rollups[$vf];
                }
                $setValues[] = $reportId;
                $sqlRollup = "UPDATE flood_reports SET " . implode(', ', $setParts) . " WHERE id = ?";
                $pdo->prepare($sqlRollup)->execute($setValues);

                $totalFlooded = $rollups['bedridden_flooded'] + $rollups['dialysis_flooded'] + $rollups['psychiatric_flooded'] + 
                                $rollups['elderly_flooded'] + $rollups['disabled_flooded'] + $rollups['ncd_flooded'] + 
                                $rollups['pregnant_flooded'] + $rollups['children_flooded'];
            } else {
                $totalFlooded = 0;
            }

            $pdo->commit();

            log_audit(
                $pdo, $currentUser['id'], $existing ? 'update_flood_report' : 'submit_flood_report',
                "บันทึกรายงานสถานการณ์อุทกภัยประจำวัน: {$orgName} (จำนวน " . count($villages) . " หมู่บ้าน, สถานะ: {$serviceStatus}, กลุ่มเปราะบางน้ำท่วม: {$totalFlooded} ราย)",
                $reportDate
            );

            resp('success', "บันทึกข้อมูลรายงานสถานการณ์อุทกภัย (รายหมู่บ้าน) ประจำวันที่ {$reportDate} เรียบร้อยแล้ว", [
                'report_id' => $reportId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            resp('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
        break;

    case 'get_report':
        $reportDate = trim($_GET['report_date'] ?? date('Y-m-d'));
        $orgId = intval($_GET['organization_id'] ?? $currentUser['organization_id']);

        if (!$orgId) {
            resp('error', 'ไม่พบรหัสหน่วยงาน');
        }

        // Get organization info
        $stmtOrg = $pdo->prepare("SELECT o.id, o.code, o.name_th, o.subdistrict, d.name_th as district_name FROM organizations o JOIN districts d ON o.district_id = d.id WHERE o.id = ?");
        $stmtOrg->execute([$orgId]);
        $orgInfo = $stmtOrg->fetch();

        $subDefaults = get_subdistrict_defaults($orgInfo ? $orgInfo['name_th'] : '');
        if (!empty($orgInfo['subdistrict'])) {
            $subDefaults['subdistrict'] = $orgInfo['subdistrict'];
        }

        $stmt = $pdo->prepare("SELECT * FROM flood_reports WHERE organization_id = ? AND report_date = ?");
        $stmt->execute([$orgId, $reportDate]);
        $report = $stmt->fetch();

        $villages = [];
        if ($report) {
            $stmtV = $pdo->prepare("SELECT * FROM flood_report_villages WHERE report_id = ? ORDER BY village_no ASC");
            $stmtV->execute([$report['id']]);
            $villages = $stmtV->fetchAll();
        }

        // If no villages saved yet, load official villages from master `villages` table
        if (empty($villages)) {
            $villages = [];
            $stmtMaster = $pdo->prepare("SELECT village_no, village_name, subdistrict FROM villages WHERE organization_id = ? ORDER BY village_no ASC");
            $stmtMaster->execute([$orgId]);
            $masterVillages = $stmtMaster->fetchAll();

            if (!empty($masterVillages)) {
                $subDefaults['subdistrict'] = $masterVillages[0]['subdistrict'];
                foreach ($masterVillages as $mv) {
                    $row = [
                        'subdistrict' => $mv['subdistrict'],
                        'village_no' => intval($mv['village_no']),
                        'village_name' => $mv['village_name']
                    ];
                    foreach ($vulnerableFields as $vf) {
                        $row[$vf] = 0;
                    }
                    $villages[] = $row;
                }
            } else {
                for ($i = 1; $i <= $subDefaults['count']; $i++) {
                    $row = [
                        'subdistrict' => $subDefaults['subdistrict'],
                        'village_no' => $i,
                        'village_name' => "หมู่ที่ {$i}"
                    ];
                    foreach ($vulnerableFields as $vf) {
                        $row[$vf] = 0;
                    }
                    $villages[] = $row;
                }
            }
        }

        resp('success', 'ข้อมูลรายงาน', [
            'report' => $report,
            'villages' => $villages,
            'is_new' => !$report,
            'report_date' => $reportDate,
            'org_info' => $orgInfo,
            'subdistrict_default' => $subDefaults['subdistrict']
        ]);
        break;

    case 'my_reports':
        $orgId = $currentUser['organization_id'];
        $stmt = $pdo->prepare("SELECT id, report_date, impact_status, service_status, bedridden_flooded, elderly_flooded, disabled_flooded, people_served, created_at, updated_at FROM flood_reports WHERE organization_id = ? ORDER BY report_date DESC LIMIT 30");
        $stmt->execute([$orgId]);
        $list = $stmt->fetchAll();
        resp('success', 'ประวัติการรายงาน', ['reports' => $list]);
        break;

    case 'provincial_summary':
        if (!in_array($currentUser['role'], ['admin', 'superadmin'])) {
            http_response_code(403);
            resp('error', 'ไม่มีสิทธิ์เข้าถึง (สำหรับ Admin และ Super Admin เท่านั้น)');
        }

        $reportDate = trim($_GET['report_date'] ?? date('Y-m-d'));
        $districtFilter = intval($_GET['district_id'] ?? 0);

        try {
            $totalFacilities = $pdo->query("SELECT COUNT(*) FROM organizations WHERE type IN ('hospital', 'health_office', 'health_center', 'provincial_office')")->fetchColumn();

            $baseSql = "SELECT 
                COUNT(*) as total_reports,
                SUM(CASE WHEN impact_status = 'affected' THEN 1 ELSE 0 END) as count_affected,
                SUM(CASE WHEN service_status = 'normal' THEN 1 ELSE 0 END) as count_normal,
                SUM(CASE WHEN service_status = 'partial' THEN 1 ELSE 0 END) as count_partial,
                SUM(CASE WHEN service_status = 'closed' THEN 1 ELSE 0 END) as count_closed,

                SUM(bedridden_total + dialysis_total + psychiatric_total + elderly_total + disabled_total + ncd_total + pregnant_total + children_total) as grand_vulnerable_total,
                SUM(bedridden_flooded + dialysis_flooded + psychiatric_flooded + elderly_flooded + disabled_flooded + ncd_flooded + pregnant_flooded + children_flooded) as grand_vulnerable_flooded,
                SUM(bedridden_home + dialysis_home + psychiatric_home + elderly_home + disabled_home + ncd_home + pregnant_home + children_home) as grand_home,
                SUM(bedridden_shelter + dialysis_shelter + psychiatric_shelter + elderly_shelter + disabled_shelter + ncd_shelter + pregnant_shelter + children_shelter) as grand_shelter,
                SUM(bedridden_hospital) as grand_hospital_referral,

                SUM(bedridden_total) as bedridden_total, SUM(bedridden_flooded) as bedridden_flooded, SUM(bedridden_home) as bedridden_home, SUM(bedridden_shelter) as bedridden_shelter, SUM(bedridden_hospital) as bedridden_hospital,
                SUM(dialysis_total) as dialysis_total, SUM(dialysis_flooded) as dialysis_flooded, SUM(dialysis_home) as dialysis_home, SUM(dialysis_shelter) as dialysis_shelter, SUM(dialysis_missed) as dialysis_missed,
                SUM(psychiatric_total) as psychiatric_total, SUM(psychiatric_flooded) as psychiatric_flooded, SUM(psychiatric_home) as psychiatric_home, SUM(psychiatric_shelter) as psychiatric_shelter, SUM(psychiatric_health_issue) as psychiatric_health_issue,
                SUM(elderly_total) as elderly_total, SUM(elderly_flooded) as elderly_flooded, SUM(elderly_home) as elderly_home, SUM(elderly_shelter) as elderly_shelter, SUM(elderly_out_of_meds) as elderly_out_of_meds,
                SUM(disabled_total) as disabled_total, SUM(disabled_flooded) as disabled_flooded, SUM(disabled_home) as disabled_home, SUM(disabled_shelter) as disabled_shelter, SUM(disabled_health_issue) as disabled_health_issue,
                SUM(ncd_total) as ncd_total, SUM(ncd_flooded) as ncd_flooded, SUM(ncd_home) as ncd_home, SUM(ncd_shelter) as ncd_shelter, SUM(ncd_out_of_meds) as ncd_out_of_meds,
                SUM(pregnant_total) as pregnant_total, SUM(pregnant_flooded) as pregnant_flooded, SUM(pregnant_home) as pregnant_home, SUM(pregnant_shelter) as pregnant_shelter, SUM(pregnant_health_issue) as pregnant_health_issue,
                SUM(children_total) as children_total, SUM(children_flooded) as children_flooded, SUM(children_home) as children_home, SUM(children_shelter) as children_shelter, SUM(children_health_issue) as children_health_issue,

                SUM(mobile_clinic_visits) as sum_mobile_clinics,
                SUM(mcatt_visits) as sum_mcatt,
                SUM(srrt_visits) as sum_srrt,
                SUM(people_served) as sum_people_served,
                SUM(home_visits) as sum_home_visits,
                SUM(med_distribution) as sum_med_distribution,
                SUM(pfa_support) as sum_pfa,
                SUM(doctor_consults) as sum_doctor_consults

                FROM flood_reports WHERE report_date = ?";

            $params = [$reportDate];
            if ($districtFilter > 0) {
                $baseSql .= " AND district_id = ?";
                $params[] = $districtFilter;
            }

            $stmtSummary = $pdo->prepare($baseSql);
            $stmtSummary->execute($params);
            $summary = $stmtSummary->fetch();

            // District breakdown
            $districtBreakdownSql = "
                SELECT 
                    d.id as district_id,
                    d.name_th as district_name,
                    COUNT(DISTINCT o.id) as total_facilities,
                    COUNT(DISTINCT r.id) as reported_count,
                    SUM(CASE WHEN r.impact_status = 'affected' THEN 1 ELSE 0 END) as affected_facilities,
                    SUM(CASE WHEN r.service_status = 'partial' THEN 1 ELSE 0 END) as partial_services,
                    SUM(CASE WHEN r.service_status = 'closed' THEN 1 ELSE 0 END) as closed_services,
                    COALESCE(SUM(r.bedridden_flooded + r.dialysis_flooded + r.psychiatric_flooded + r.elderly_flooded + r.disabled_flooded + r.ncd_flooded + r.pregnant_flooded + r.children_flooded), 0) as flooded_vulnerable,
                    COALESCE(SUM(r.people_served), 0) as people_served
                FROM districts d
                LEFT JOIN organizations o ON o.district_id = d.id AND o.type IN ('hospital', 'health_office', 'health_center', 'provincial_office')
                LEFT JOIN flood_reports r ON r.organization_id = o.id AND r.report_date = ?
                GROUP BY d.id, d.name_th
                ORDER BY d.id ASC
            ";
            $stmtDist = $pdo->prepare($districtBreakdownSql);
            $stmtDist->execute([$reportDate]);
            $districtsSummary = $stmtDist->fetchAll();

            // Facilities list
            $facilitiesListSql = "
                SELECT 
                    o.id as org_id,
                    o.name_th as org_name,
                    o.type as org_type,
                    d.id as district_id,
                    d.name_th as district_name,
                    r.id as report_id,
                    r.report_date,
                    r.user_name,
                    r.impact_status,
                    r.service_status,
                    r.impact_details,
                    r.substitute_location,
                    r.created_at,
                    r.updated_at,
                    COALESCE(r.bedridden_flooded + r.dialysis_flooded + r.psychiatric_flooded + r.elderly_flooded + r.disabled_flooded + r.ncd_flooded + r.pregnant_flooded + r.children_flooded, 0) as total_flooded,
                    r.people_served,
                    r.home_visits
                FROM organizations o
                JOIN districts d ON o.district_id = d.id
                LEFT JOIN flood_reports r ON r.organization_id = o.id AND r.report_date = ?
                WHERE o.type IN ('hospital', 'health_office', 'health_center', 'provincial_office')
            ";
            $facParams = [$reportDate];
            if ($districtFilter > 0) {
                $facilitiesListSql .= " AND d.id = ?";
                $facParams[] = $districtFilter;
            }
            $facilitiesListSql .= " ORDER BY (r.id IS NOT NULL) DESC, (r.impact_status = 'affected') DESC, d.id ASC, o.id ASC";

            $stmtFac = $pdo->prepare($facilitiesListSql);
            $stmtFac->execute($facParams);
            $facilityRows = $stmtFac->fetchAll();

            resp('success', 'สรุปภาพรวมจังหวัดสำเร็จ', [
                'report_date' => $reportDate,
                'total_facilities' => (int)$totalFacilities,
                'summary' => $summary,
                'districts_summary' => $districtsSummary,
                'facility_reports' => $facilityRows
            ]);

        } catch (PDOException $e) {
            resp('error', 'เกิดข้อผิดพลาดในการคำนวณข้อมูลภาพรวม: ' . $e->getMessage());
        }
        break;

    case 'get_audit_logs':
        if (!in_array($currentUser['role'], ['admin', 'superadmin'])) {
            http_response_code(403);
            resp('error', 'ไม่มีสิทธิ์เข้าถึงประวัติการปฏิบัติงาน');
        }

        $search = trim($_GET['search'] ?? '');
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));

        $sql = "SELECT id, user_id, username, fullname, district_name, organization_name, action, details, report_date, ip_address, created_at FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (fullname LIKE ? OR username LIKE ? OR organization_name LIKE ? OR action LIKE ? OR details LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like, $like, $like];
        }

        $sql .= " ORDER BY created_at DESC LIMIT {$limit}";

        try {
            $stmtLogs = $pdo->prepare($sql);
            $stmtLogs->execute($params);
            $logs = $stmtLogs->fetchAll();

            resp('success', 'ประวัติการปฏิบัติงาน (Logs)', [
                'count' => count($logs),
                'logs' => $logs
            ]);
        } catch (PDOException $e) {
            resp('error', 'เกิดข้อผิดพลาดในการโหลด Logs: ' . $e->getMessage());
        }
        break;

    default:
        resp('error', 'Invalid action');
}
