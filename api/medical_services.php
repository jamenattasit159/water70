<?php
// api/medical_services.php - API for Section 3: Medical & Public Health Outreach Services
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

switch ($action) {
    case 'list_services':
        $reportDate = trim($_GET['report_date'] ?? date('Y-m-d'));
        $orgId = intval($_GET['organization_id'] ?? 0);

        // If not admin, restrict to own organization
        if (!in_array($currentUser['role'], ['admin', 'superadmin'])) {
            $orgId = $currentUser['organization_id'];
        }

        $params = [$reportDate];
        $sql = "SELECT * FROM flood_medical_services WHERE report_date = ?";
        if ($orgId > 0) {
            $sql .= " AND organization_id = ?";
            $params[] = $orgId;
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        resp('success', 'รายการบริการทางการแพทย์', [
            'services' => $services,
            'report_date' => $reportDate,
            'count' => count($services)
        ]);
        break;

    case 'save_service':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            resp('error', 'Method not allowed');
        }

        $id = intval($_POST['id'] ?? 0);
        $reportDate = trim($_POST['report_date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
            resp('error', 'รูปแบบวันที่ไม่ถูกต้อง (YYYY-MM-DD)');
        }

        $orgId = $currentUser['organization_id'];
        $districtId = $currentUser['district_id'];

        if (in_array($currentUser['role'], ['admin', 'superadmin']) && !empty($_POST['organization_id'])) {
            $orgId = intval($_POST['organization_id']);
        }

        if (!$orgId) {
            resp('error', 'ไม่พบข้อมูลหน่วยงานสังกัด');
        }

        // Get Org & District details
        $stmtOrg = $pdo->prepare("
            SELECT o.id, o.name_th as org_name, d.id as district_id, d.name_th as district_name 
            FROM organizations o 
            JOIN districts d ON o.district_id = d.id 
            WHERE o.id = ?
        ");
        $stmtOrg->execute([$orgId]);
        $orgInfo = $stmtOrg->fetch(PDO::FETCH_ASSOC);

        if (!$orgInfo) {
            resp('error', 'ไม่พบข้อมูลหน่วยงานในระบบ');
        }

        $districtId = $orgInfo['district_id'];
        $districtName = $orgInfo['district_name'];
        $orgName = $orgInfo['org_name'];

        $outreachLocation = trim($_POST['outreach_location'] ?? '');
        $villageNo = !empty($_POST['village_no']) ? intval($_POST['village_no']) : null;
        $villageName = trim($_POST['village_name'] ?? '');
        $peopleServed = max(0, intval($_POST['people_served'] ?? 0));

        // Teams
        $teamMobile = max(0, intval($_POST['team_mobile_clinic'] ?? 0));
        $teamMcatt = max(0, intval($_POST['team_mcatt'] ?? 0));
        $teamSrrt = max(0, intval($_POST['team_srrt'] ?? 0));
        $teamShert = max(0, intval($_POST['team_shert'] ?? 0));

        // Medical Services
        $servChronic = max(0, intval($_POST['service_chronic_meds'] ?? 0));
        $servHome = max(0, intval($_POST['service_home_visit'] ?? 0));
        $servDispense = max(0, intval($_POST['service_med_dispense'] ?? 0));
        $servEdu = max(0, intval($_POST['service_health_edu'] ?? 0));
        $servTreat = max(0, intval($_POST['service_treatment'] ?? 0));
        $servRefer = max(0, intval($_POST['service_referral'] ?? 0));
        $servOther = max(0, intval($_POST['service_other'] ?? 0));

        // Mental Health
        $mScreen = max(0, intval($_POST['mental_screened'] ?? 0));
        $mStress = max(0, intval($_POST['mental_high_stress'] ?? 0));
        $mDepress = max(0, intval($_POST['mental_depression'] ?? 0));
        $mSuicide = max(0, intval($_POST['mental_suicide_risk'] ?? 0));
        $mPfa = max(0, intval($_POST['mental_help_pfa'] ?? 0));
        $mDoctor = max(0, intval($_POST['mental_help_doctor'] ?? 0));

        // Diseases
        $dResp = max(0, intval($_POST['disease_respiratory'] ?? 0));
        $dGi = max(0, intval($_POST['disease_gastrointestinal'] ?? 0));
        $dCirc = max(0, intval($_POST['disease_circulatory'] ?? 0));
        $dEye = max(0, intval($_POST['disease_eye_infection'] ?? 0));
        $dSkin = max(0, intval($_POST['disease_skin'] ?? 0));
        $dFoot = max(0, intval($_POST['disease_athletes_foot'] ?? 0));
        $dMuscle = max(0, intval($_POST['disease_muscle_pain'] ?? 0));
        $dHead = max(0, intval($_POST['disease_headache'] ?? 0));
        $dDengue = max(0, intval($_POST['disease_dengue'] ?? 0));
        $dDia = max(0, intval($_POST['disease_diarrhea'] ?? 0));
        $dFatigue = max(0, intval($_POST['disease_fatigue'] ?? 0));
        $dViral = max(0, intval($_POST['disease_viral'] ?? 0));
        $dOther = max(0, intval($_POST['disease_other'] ?? 0));
        $dOtherText = trim($_POST['disease_other_text'] ?? '');

        // Water Accidents
        $accDrown = max(0, intval($_POST['accident_drowning'] ?? 0));
        $accShock = max(0, intval($_POST['accident_electrocution'] ?? 0));

        // Resources
        $rRelief = max(0, intval($_POST['resource_relief_kit'] ?? 0));
        $rHome = max(0, intval($_POST['resource_home_medicine'] ?? 0));
        $rFoot = max(0, intval($_POST['resource_foot_cream'] ?? 0));
        $rAnti = max(0, intval($_POST['resource_antifungal'] ?? 0));
        $rBoots = max(0, intval($_POST['resource_boots'] ?? 0));
        $rRepel = max(0, intval($_POST['resource_mosquito_repellent'] ?? 0));
        $rBag = max(0, intval($_POST['resource_garbage_bag'] ?? 0));
        $rAlum = max(0, intval($_POST['resource_alum'] ?? 0));
        $rChlorine = max(0, intval($_POST['resource_chlorine'] ?? 0));
        $rEm = max(0, intval($_POST['resource_em'] ?? 0));
        $rOther = max(0, intval($_POST['resource_other'] ?? 0));

        // Shelters & Reporter
        $shelterCount = max(0, intval($_POST['shelter_count'] ?? 0));
        $shelterCap = max(0, intval($_POST['shelter_capacity'] ?? 0));
        $shelterOcc = max(0, intval($_POST['shelter_occupants'] ?? 0));
        $repName = trim($_POST['reporter_name'] ?? $currentUser['fullname']);
        $repPhone = trim($_POST['reporter_phone'] ?? $currentUser['phone']);
        $notes = trim($_POST['notes'] ?? '');

        try {
            if ($id > 0) {
                // Update
                $stmtCheck = $pdo->prepare("SELECT organization_id FROM flood_medical_services WHERE id = ?");
                $stmtCheck->execute([$id]);
                $existing = $stmtCheck->fetch();

                if (!$existing) {
                    resp('error', 'ไม่พบข้อมูลรายการที่ต้องการแก้ไข');
                }

                if (!in_array($currentUser['role'], ['admin', 'superadmin']) && $existing['organization_id'] != $currentUser['organization_id']) {
                    resp('error', 'คุณไม่มีสิทธิ์แก้ไขข้อมูลของหน่วยงานอื่น');
                }

                $sqlUpdate = "UPDATE flood_medical_services SET
                    report_date = ?, outreach_location = ?, village_no = ?, village_name = ?, people_served = ?,
                    team_mobile_clinic = ?, team_mcatt = ?, team_srrt = ?, team_shert = ?,
                    service_chronic_meds = ?, service_home_visit = ?, service_med_dispense = ?, service_health_edu = ?, service_treatment = ?, service_referral = ?, service_other = ?,
                    mental_screened = ?, mental_high_stress = ?, mental_depression = ?, mental_suicide_risk = ?, mental_help_pfa = ?, mental_help_doctor = ?,
                    disease_respiratory = ?, disease_gastrointestinal = ?, disease_circulatory = ?, disease_eye_infection = ?, disease_skin = ?, disease_athletes_foot = ?, disease_muscle_pain = ?, disease_headache = ?, disease_dengue = ?, disease_diarrhea = ?, disease_fatigue = ?, disease_viral = ?, disease_other = ?, disease_other_text = ?,
                    accident_drowning = ?, accident_electrocution = ?,
                    resource_relief_kit = ?, resource_home_medicine = ?, resource_foot_cream = ?, resource_antifungal = ?, resource_boots = ?, resource_mosquito_repellent = ?, resource_garbage_bag = ?, resource_alum = ?, resource_chlorine = ?, resource_em = ?, resource_other = ?,
                    shelter_count = ?, shelter_capacity = ?, shelter_occupants = ?, reporter_name = ?, reporter_phone = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?";

                $pdo->prepare($sqlUpdate)->execute([
                    $reportDate, $outreachLocation, $villageNo, $villageName, $peopleServed,
                    $teamMobile, $teamMcatt, $teamSrrt, $teamShert,
                    $servChronic, $servHome, $servDispense, $servEdu, $servTreat, $servRefer, $servOther,
                    $mScreen, $mStress, $mDepress, $mSuicide, $mPfa, $mDoctor,
                    $dResp, $dGi, $dCirc, $dEye, $dSkin, $dFoot, $dMuscle, $dHead, $dDengue, $dDia, $dFatigue, $dViral, $dOther, $dOtherText,
                    $accDrown, $accShock,
                    $rRelief, $rHome, $rFoot, $rAnti, $rBoots, $rRepel, $rBag, $rAlum, $rChlorine, $rEm, $rOther,
                    $shelterCount, $shelterCap, $shelterOcc, $repName, $repPhone, $notes,
                    $id
                ]);

                log_audit(
                    $pdo, $currentUser['id'], 'update_medical_service',
                    "แก้ไขบันทึกบริการแพทย์ & สาธารณสุข: {$orgName} - {$outreachLocation} (ปชช. {$peopleServed} คน)",
                    $reportDate
                );

                resp('success', 'บันทึกการแก้ไขข้อมูลบริการทางการแพทย์และสาธารณสุขเรียบร้อยแล้ว', ['id' => $id]);

            } else {
                // Insert
                $sqlInsert = "INSERT INTO flood_medical_services (
                    report_date, district_id, district_name, organization_id, organization_name, user_id, user_name,
                    outreach_location, village_no, village_name, people_served,
                    team_mobile_clinic, team_mcatt, team_srrt, team_shert,
                    service_chronic_meds, service_home_visit, service_med_dispense, service_health_edu, service_treatment, service_referral, service_other,
                    mental_screened, mental_high_stress, mental_depression, mental_suicide_risk, mental_help_pfa, mental_help_doctor,
                    disease_respiratory, disease_gastrointestinal, disease_circulatory, disease_eye_infection, disease_skin, disease_athletes_foot, disease_muscle_pain, disease_headache, disease_dengue, disease_diarrhea, disease_fatigue, disease_viral, disease_other, disease_other_text,
                    accident_drowning, accident_electrocution,
                    resource_relief_kit, resource_home_medicine, resource_foot_cream, resource_antifungal, resource_boots, resource_mosquito_repellent, resource_garbage_bag, resource_alum, resource_chlorine, resource_em, resource_other,
                    shelter_count, shelter_capacity, shelter_occupants, reporter_name, reporter_phone, notes, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";

                $pdo->prepare($sqlInsert)->execute([
                    $reportDate, $districtId, $districtName, $orgId, $orgName, $currentUser['id'], $currentUser['fullname'],
                    $outreachLocation, $villageNo, $villageName, $peopleServed,
                    $teamMobile, $teamMcatt, $teamSrrt, $teamShert,
                    $servChronic, $servHome, $servDispense, $servEdu, $servTreat, $servRefer, $servOther,
                    $mScreen, $mStress, $mDepress, $mSuicide, $mPfa, $mDoctor,
                    $dResp, $dGi, $dCirc, $dEye, $dSkin, $dFoot, $dMuscle, $dHead, $dDengue, $dDia, $dFatigue, $dViral, $dOther, $dOtherText,
                    $accDrown, $accShock,
                    $rRelief, $rHome, $rFoot, $rAnti, $rBoots, $rRepel, $rBag, $rAlum, $rChlorine, $rEm, $rOther,
                    $shelterCount, $shelterCap, $shelterOcc, $repName, $repPhone, $notes
                ]);

                $newId = $pdo->lastInsertId();

                log_audit(
                    $pdo, $currentUser['id'], 'submit_medical_service',
                    "เพิ่มบันทึกบริการแพทย์ & สาธารณสุข: {$orgName} - {$outreachLocation} (ปชช. {$peopleServed} คน)",
                    $reportDate
                );

                resp('success', 'บันทึกข้อมูลบริการทางการแพทย์และสาธารณสุขเรียบร้อยแล้ว', ['id' => $newId]);
            }

        } catch (PDOException $e) {
            resp('error', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage());
        }
        break;

    case 'delete_service':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            resp('error', 'Method not allowed');
        }

        $id = intval($_POST['id'] ?? 0);
        if (!$id) resp('error', 'ไม่พบรหัสรายการ');

        $stmt = $pdo->prepare("SELECT * FROM flood_medical_services WHERE id = ?");
        $stmt->execute([$id]);
        $serv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$serv) resp('error', 'ไม่พบรายการที่ต้องการลบ');

        if (!in_array($currentUser['role'], ['admin', 'superadmin']) && $serv['organization_id'] != $currentUser['organization_id']) {
            resp('error', 'คุณไม่มีสิทธิ์ลบข้อมูลของหน่วยงานอื่น');
        }

        $pdo->prepare("DELETE FROM flood_medical_services WHERE id = ?")->execute([$id]);

        log_audit(
            $pdo, $currentUser['id'], 'delete_medical_service',
            "ลบรายการบริการทางการแพทย์: {$serv['organization_name']} - {$serv['outreach_location']}",
            $serv['report_date']
        );

        resp('success', 'ลบรายการเรียบร้อยแล้ว');
        break;

    case 'get_summary':
        $reportDate = trim($_GET['report_date'] ?? date('Y-m-d'));
        $districtId = intval($_GET['district_id'] ?? 0);

        $where = "report_date = ?";
        $params = [$reportDate];
        if ($districtId > 0) {
            $where .= " AND district_id = ?";
            $params[] = $districtId;
        }

        $stmt = $pdo->prepare("
            SELECT 
                COUNT(id) as total_outreaches,
                COALESCE(SUM(people_served), 0) as people_served,
                COALESCE(SUM(team_mobile_clinic), 0) as team_mobile_clinic,
                COALESCE(SUM(team_mcatt), 0) as team_mcatt,
                COALESCE(SUM(team_srrt), 0) as team_srrt,
                COALESCE(SUM(team_shert), 0) as team_shert,
                COALESCE(SUM(service_chronic_meds), 0) as service_chronic_meds,
                COALESCE(SUM(service_home_visit), 0) as service_home_visit,
                COALESCE(SUM(service_med_dispense), 0) as service_med_dispense,
                COALESCE(SUM(service_health_edu), 0) as service_health_edu,
                COALESCE(SUM(service_treatment), 0) as service_treatment,
                COALESCE(SUM(service_referral), 0) as service_referral,
                COALESCE(SUM(service_other), 0) as service_other,
                COALESCE(SUM(mental_screened), 0) as mental_screened,
                COALESCE(SUM(mental_high_stress), 0) as mental_high_stress,
                COALESCE(SUM(mental_depression), 0) as mental_depression,
                COALESCE(SUM(mental_suicide_risk), 0) as mental_suicide_risk,
                COALESCE(SUM(mental_help_pfa), 0) as mental_help_pfa,
                COALESCE(SUM(mental_help_doctor), 0) as mental_help_doctor,
                COALESCE(SUM(disease_respiratory), 0) as disease_respiratory,
                COALESCE(SUM(disease_gastrointestinal), 0) as disease_gastrointestinal,
                COALESCE(SUM(disease_circulatory), 0) as disease_circulatory,
                COALESCE(SUM(disease_eye_infection), 0) as disease_eye_infection,
                COALESCE(SUM(disease_skin), 0) as disease_skin,
                COALESCE(SUM(disease_athletes_foot), 0) as disease_athletes_foot,
                COALESCE(SUM(disease_muscle_pain), 0) as disease_muscle_pain,
                COALESCE(SUM(disease_headache), 0) as disease_headache,
                COALESCE(SUM(disease_dengue), 0) as disease_dengue,
                COALESCE(SUM(disease_diarrhea), 0) as disease_diarrhea,
                COALESCE(SUM(disease_fatigue), 0) as disease_fatigue,
                COALESCE(SUM(disease_viral), 0) as disease_viral,
                COALESCE(SUM(disease_other), 0) as disease_other,
                COALESCE(SUM(accident_drowning), 0) as accident_drowning,
                COALESCE(SUM(accident_electrocution), 0) as accident_electrocution,
                COALESCE(SUM(resource_relief_kit), 0) as resource_relief_kit,
                COALESCE(SUM(resource_home_medicine), 0) as resource_home_medicine,
                COALESCE(SUM(resource_foot_cream), 0) as resource_foot_cream,
                COALESCE(SUM(resource_antifungal), 0) as resource_antifungal,
                COALESCE(SUM(resource_boots), 0) as resource_boots,
                COALESCE(SUM(resource_mosquito_repellent), 0) as resource_mosquito_repellent,
                COALESCE(SUM(resource_garbage_bag), 0) as resource_garbage_bag,
                COALESCE(SUM(resource_alum), 0) as resource_alum,
                COALESCE(SUM(resource_chlorine), 0) as resource_chlorine,
                COALESCE(SUM(resource_em), 0) as resource_em,
                COALESCE(SUM(resource_other), 0) as resource_other,
                COALESCE(SUM(shelter_count), 0) as shelter_count,
                COALESCE(SUM(shelter_capacity), 0) as shelter_capacity,
                COALESCE(SUM(shelter_occupants), 0) as shelter_occupants
            FROM flood_medical_services
            WHERE {$where}
        ");
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        resp('success', 'สรุปยอดบริการทางการแพทย์', [
            'report_date' => $reportDate,
            'summary' => $summary
        ]);
        break;

    default:
        resp('error', 'Invalid action');
        break;
}
