<?php
// api/onepage_data.php - Aggregated API endpoint for Executive OnePage Infographic Dashboard
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_data';
$reportDate = trim($_GET['report_date'] ?? $_POST['report_date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    $reportDate = date('Y-m-d');
}

function resp($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'get_data':
        // 1. Situation Parameters
        $stmtSit = $pdo->prepare("SELECT * FROM flood_provincial_situation WHERE report_date = ?");
        $stmtSit->execute([$reportDate]);
        $situation = $stmtSit->fetch(PDO::FETCH_ASSOC);

        if (!$situation) {
            // Default situation if not configured for this date
            $situation = [
                'report_date' => $reportDate,
                'rain_situation' => 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง',
                'water_discharge' => '8.62 (+0.47) (จุดหน้าศาลากลาง)',
                'shelter_total_sites' => 2,
                'shelter_capacity' => 300,
                'shelter_occupants' => 0,
                'deaths' => 0,
                'injuries' => 0,
                'missing' => 0,
                'data_source' => 'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง'
            ];
        }

        // 2. District Flood Stats
        $stmtDist = $pdo->prepare("SELECT * FROM district_flood_stats WHERE report_date = ? ORDER BY district_id ASC");
        $stmtDist->execute([$reportDate]);
        $distStats = $stmtDist->fetchAll(PDO::FETCH_ASSOC);

        if (empty($distStats)) {
            // Generate defaults from 7 districts
            $districts = $pdo->query("SELECT id, name_th FROM districts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            $distStats = [];
            foreach ($districts as $d) {
                $distStats[] = [
                    'district_id' => $d['id'],
                    'district_name' => $d['name_th'],
                    'subdistricts_affected' => 0,
                    'villages_affected' => 0,
                    'communities_affected' => 0,
                    'households_affected' => 0,
                    'is_flooded' => 0
                ];
            }
        }

        // Totals of Affected Areas
        $totDistricts = 0;
        $totSubdistricts = 0;
        $totVillages = 0;
        $totCommunities = 0;
        $totHouseholds = 0;

        foreach ($distStats as $ds) {
            if ($ds['subdistricts_affected'] > 0 || $ds['villages_affected'] > 0 || $ds['households_affected'] > 0 || $ds['is_flooded']) {
                $totDistricts++;
            }
            $totSubdistricts += intval($ds['subdistricts_affected']);
            $totVillages += intval($ds['villages_affected']);
            $totCommunities += intval($ds['communities_affected']);
            $totHouseholds += intval($ds['households_affected']);
        }

        $affectedOverview = [
            'districts_count' => $totDistricts,
            'subdistricts_count' => $totSubdistricts,
            'villages_count' => $totVillages,
            'communities_count' => $totCommunities,
            'households_count' => $totHouseholds
        ];

        // 3. Vulnerable Groups Flooded (From flood_reports)
        $stmtVul = $pdo->prepare("
            SELECT 
                COALESCE(SUM(bedridden_flooded), 0) as bedridden,
                COALESCE(SUM(pregnant_flooded), 0) as pregnant,
                COALESCE(SUM(disabled_flooded), 0) as disabled,
                COALESCE(SUM(children_flooded), 0) as children,
                COALESCE(SUM(elderly_flooded), 0) as elderly,
                COALESCE(SUM(dialysis_flooded), 0) as dialysis,
                COALESCE(SUM(psychiatric_flooded), 0) as psychiatric,
                COALESCE(SUM(ncd_flooded), 0) as ncd
            FROM flood_reports 
            WHERE report_date = ?
        ");
        $stmtVul->execute([$reportDate]);
        $vulRow = $stmtVul->fetch(PDO::FETCH_ASSOC);

        $vulTotal = $vulRow['bedridden'] + $vulRow['pregnant'] + $vulRow['disabled'] + $vulRow['children'] + 
                    $vulRow['elderly'] + $vulRow['dialysis'] + $vulRow['psychiatric'] + $vulRow['ncd'];

        $vulnerableGroups = [
            'bedridden' => intval($vulRow['bedridden']),
            'pregnant' => intval($vulRow['pregnant']),
            'disabled' => intval($vulRow['disabled']),
            'children' => intval($vulRow['children']),
            'elderly' => intval($vulRow['elderly']),
            'dialysis' => intval($vulRow['dialysis']),
            'psychiatric' => intval($vulRow['psychiatric']),
            'ncd' => intval($vulRow['ncd']),
            'total' => $vulTotal
        ];

        // 4. Medical Outreach Services Summary (From flood_medical_services)
        $stmtMed = $pdo->prepare("
            SELECT 
                COALESCE(SUM(service_home_visit), 0) as home_visit,
                COALESCE(SUM(service_health_edu), 0) as health_edu,
                COALESCE(SUM(service_treatment), 0) as treatment,
                COALESCE(SUM(service_chronic_meds), 0) as chronic_meds,
                COALESCE(SUM(service_med_dispense), 0) as med_dispense,
                COALESCE(SUM(service_referral), 0) as referral,
                COALESCE(SUM(mental_screened), 0) as mental_evaluation,
                -- Top Diseases
                COALESCE(SUM(disease_athletes_foot), 0) as d_athletes_foot,
                COALESCE(SUM(disease_skin), 0) as d_skin,
                COALESCE(SUM(disease_muscle_pain), 0) as d_muscle_pain,
                COALESCE(SUM(disease_headache), 0) as d_headache,
                COALESCE(SUM(disease_eye_infection), 0) as d_eye_infection,
                COALESCE(SUM(disease_dengue), 0) as d_dengue,
                COALESCE(SUM(disease_respiratory), 0) as d_respiratory,
                COALESCE(SUM(disease_gastrointestinal), 0) as d_gastrointestinal,
                COALESCE(SUM(disease_circulatory), 0) as d_circulatory,
                COALESCE(SUM(disease_diarrhea), 0) as d_diarrhea,
                COALESCE(SUM(disease_fatigue), 0) as d_fatigue,
                COALESCE(SUM(disease_viral), 0) as d_viral,
                COALESCE(SUM(disease_other), 0) as d_other,
                -- Mental Health
                COALESCE(SUM(mental_screened), 0) as m_screened,
                COALESCE(SUM(mental_high_stress), 0) as m_stress,
                COALESCE(SUM(mental_depression), 0) as m_depression,
                COALESCE(SUM(mental_suicide_risk), 0) as m_suicide,
                COALESCE(SUM(mental_help_pfa), 0) as m_pfa,
                COALESCE(SUM(mental_help_doctor), 0) as m_doctor,
                -- Resources
                COALESCE(SUM(resource_foot_cream), 0) as r_foot_cream,
                COALESCE(SUM(resource_relief_kit), 0) as r_relief_kit,
                COALESCE(SUM(resource_garbage_bag), 0) as r_garbage_bag,
                COALESCE(SUM(resource_mosquito_repellent), 0) as r_mosquito_repellent,
                COALESCE(SUM(resource_home_medicine), 0) as r_home_medicine,
                COALESCE(SUM(resource_antifungal), 0) as r_antifungal,
                -- Shelter & Casualties from services
                COALESCE(SUM(shelter_count), 0) as s_count,
                COALESCE(SUM(shelter_capacity), 0) as s_capacity,
                COALESCE(SUM(shelter_occupants), 0) as s_occupants,
                COALESCE(SUM(accident_drowning), 0) as a_drown,
                COALESCE(SUM(accident_electrocution), 0) as a_shock
            FROM flood_medical_services
            WHERE report_date = ?
        ");
        $stmtMed->execute([$reportDate]);
        $mRow = $stmtMed->fetch(PDO::FETCH_ASSOC);

        $totMedicalServices = intval($mRow['home_visit']) + intval($mRow['health_edu']) + intval($mRow['treatment']) + 
                              intval($mRow['chronic_meds']) + intval($mRow['med_dispense']) + intval($mRow['referral']) + 
                              intval($mRow['mental_evaluation']);

        $medicalServices = [
            'home_visit' => intval($mRow['home_visit']),
            'health_edu' => intval($mRow['health_edu']),
            'treatment' => intval($mRow['treatment']),
            'chronic_meds' => intval($mRow['chronic_meds']),
            'med_dispense' => intval($mRow['med_dispense']),
            'referral' => intval($mRow['referral']),
            'mental_evaluation' => intval($mRow['mental_evaluation']),
            'total' => $totMedicalServices
        ];

        // Top Diseases
        $totDiseases = intval($mRow['d_athletes_foot']) + intval($mRow['d_skin']) + intval($mRow['d_muscle_pain']) + 
                       intval($mRow['d_headache']) + intval($mRow['d_eye_infection']) + intval($mRow['d_dengue']);

        $topDiseases = [
            'athletes_foot' => intval($mRow['d_athletes_foot']),
            'skin' => intval($mRow['d_skin']),
            'muscle_pain' => intval($mRow['d_muscle_pain']),
            'headache' => intval($mRow['d_headache']),
            'eye_infection' => intval($mRow['d_eye_infection']),
            'dengue' => intval($mRow['d_dengue']),
            'total' => $totDiseases
        ];

        // Mental Health
        $mentalHealth = [
            'screened' => intval($mRow['m_screened']),
            'high_stress' => intval($mRow['m_stress']),
            'depression' => intval($mRow['m_depression']),
            'suicide_risk' => intval($mRow['m_suicide']),
            'pfa_doctor' => intval($mRow['m_pfa']) + intval($mRow['m_doctor'])
        ];

        // Resources
        $resources = [
            'foot_cream' => intval($mRow['r_foot_cream']),
            'relief_kit' => intval($mRow['r_relief_kit']),
            'garbage_bag' => intval($mRow['r_garbage_bag']),
            'mosquito_repellent' => intval($mRow['r_mosquito_repellent']),
            'home_medicine' => intval($mRow['r_home_medicine']),
            'antifungal' => intval($mRow['r_antifungal'])
        ];

        // 5. Facility Impact Status (From flood_reports)
        $stmtFac = $pdo->prepare("
            SELECT 
                COUNT(id) as reported_count,
                COALESCE(SUM(CASE WHEN impact_status = 'affected' THEN 1 ELSE 0 END), 0) as affected_count,
                COALESCE(SUM(CASE WHEN service_status = 'normal' THEN 1 ELSE 0 END), 0) as normal_count,
                COALESCE(SUM(CASE WHEN service_status = 'partial' THEN 1 ELSE 0 END), 0) as partial_count,
                COALESCE(SUM(CASE WHEN service_status = 'closed' THEN 1 ELSE 0 END), 0) as closed_count
            FROM flood_reports
            WHERE report_date = ?
        ");
        $stmtFac->execute([$reportDate]);
        $facRow = $stmtFac->fetch(PDO::FETCH_ASSOC);

        $facilityImpacts = [
            'total_affected' => intval($facRow['affected_count']),
            'rpst_affected' => 0,
            'hospital_affected' => 0,
            'sso_affected' => 0,
            'normal_count' => intval($facRow['normal_count']),
            'partial_count' => intval($facRow['partial_count']),
            'closed_count' => intval($facRow['closed_count'])
        ];

        // Breakdown by org type for affected facilities
        $stmtFacType = $pdo->prepare("
            SELECT o.type, COUNT(fr.id) as cnt
            FROM flood_reports fr
            JOIN organizations o ON fr.organization_id = o.id
            WHERE fr.report_date = ? AND fr.impact_status = 'affected'
            GROUP BY o.type
        ");
        $stmtFacType->execute([$reportDate]);
        foreach ($stmtFacType->fetchAll(PDO::FETCH_ASSOC) as $ft) {
            if ($ft['type'] === 'health_center') $facilityImpacts['rpst_affected'] = intval($ft['cnt']);
            else if ($ft['type'] === 'hospital') $facilityImpacts['hospital_affected'] = intval($ft['cnt']);
            else if ($ft['type'] === 'health_office') $facilityImpacts['sso_affected'] = intval($ft['cnt']);
        }

        // 6. Shelters & Casualties (From situation or services)
        $shelterInfo = [
            'total_sites' => max(intval($situation['shelter_total_sites']), intval($mRow['s_count'])),
            'capacity' => max(intval($situation['shelter_capacity']), intval($mRow['s_capacity'])),
            'occupants' => max(intval($situation['shelter_occupants']), intval($mRow['s_occupants']))
        ];

        $casualtyInfo = [
            'deaths' => intval($situation['deaths']) + intval($mRow['a_drown']),
            'injuries' => intval($situation['injuries']) + intval($mRow['a_shock']),
            'missing' => intval($situation['missing'])
        ];

        resp('success', 'ข้อมูลสำหรับ OnePage Dashboard', [
            'report_date' => $reportDate,
            'situation' => $situation,
            'affected_overview' => $affectedOverview,
            'district_stats' => $distStats,
            'vulnerable_groups' => $vulnerableGroups,
            'medical_services' => $medicalServices,
            'top_diseases' => $topDiseases,
            'mental_health' => $mentalHealth,
            'resources' => $resources,
            'facility_impacts' => $facilityImpacts,
            'shelter_info' => $shelterInfo,
            'casualty_info' => $casualtyInfo
        ]);
        break;

    case 'save_situation':
        // Admin update situational parameters
        require_login('admin');

        $reportDate = trim($_POST['report_date'] ?? date('Y-m-d'));
        $rain = trim($_POST['rain_situation'] ?? 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง');
        $water = trim($_POST['water_discharge'] ?? '8.62 (+0.47) (จุดหน้าศาลากลาง)');
        $shelterSites = max(0, intval($_POST['shelter_total_sites'] ?? 2));
        $shelterCap = max(0, intval($_POST['shelter_capacity'] ?? 300));
        $shelterOcc = max(0, intval($_POST['shelter_occupants'] ?? 0));
        $deaths = max(0, intval($_POST['deaths'] ?? 0));
        $injuries = max(0, intval($_POST['injuries'] ?? 0));
        $missing = max(0, intval($_POST['missing'] ?? 0));
        $source = trim($_POST['data_source'] ?? 'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง');

        $stmt = $pdo->prepare("
            INSERT INTO flood_provincial_situation 
            (report_date, rain_situation, water_discharge, shelter_total_sites, shelter_capacity, shelter_occupants, deaths, injuries, missing, data_source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            rain_situation = VALUES(rain_situation),
            water_discharge = VALUES(water_discharge),
            shelter_total_sites = VALUES(shelter_total_sites),
            shelter_capacity = VALUES(shelter_capacity),
            shelter_occupants = VALUES(shelter_occupants),
            deaths = VALUES(deaths),
            injuries = VALUES(injuries),
            missing = VALUES(missing),
            data_source = VALUES(data_source),
            updated_at = NOW()
        ");
        $stmt->execute([
            $reportDate, $rain, $water, $shelterSites, $shelterCap, $shelterOcc, $deaths, $injuries, $missing, $source
        ]);

        resp('success', 'บันทึกข้อมูลสถานการณ์ทั่วไปเรียบร้อยแล้ว');
        break;

    case 'load_demo_preset':
        // Pre-populates the exact values from sample image b8dd3bda-1648-4b1d-96c2-4e5a20cf25e3.jpg
        require_login('admin');

        $targetDate = trim($_POST['report_date'] ?? '2026-10-01');

        // 1. Situation
        $pdo->prepare("
            INSERT INTO flood_provincial_situation 
            (report_date, rain_situation, water_discharge, shelter_total_sites, shelter_capacity, shelter_occupants, deaths, injuries, missing, data_source)
            VALUES (?, 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง', '8.62 (+0.47) (จุดหน้าศาลากลาง)', 2, 300, 0, 0, 0, 0, 'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง')
            ON DUPLICATE KEY UPDATE
            rain_situation = VALUES(rain_situation), water_discharge = VALUES(water_discharge), shelter_total_sites = VALUES(shelter_total_sites),
            shelter_capacity = VALUES(shelter_capacity), shelter_occupants = VALUES(shelter_occupants), deaths = 0, injuries = 0, missing = 0
        ")->execute([$targetDate]);

        // 2. District Flood Stats (5 affected districts)
        $distData = [
            ['district_id' => 1, 'district_name' => 'อำเภอเมืองอ่างทอง', 'sub' => 9, 'vil' => 23, 'com' => 14, 'hh' => 285, 'flood' => 1],
            ['district_id' => 3, 'district_name' => 'อำเภอป่าโมก', 'sub' => 3, 'vil' => 11, 'com' => 0, 'hh' => 248, 'flood' => 1],
            ['district_id' => 2, 'district_name' => 'อำเภอไชโย', 'sub' => 6, 'vil' => 17, 'com' => 0, 'hh' => 58, 'flood' => 1],
            ['district_id' => 6, 'district_name' => 'อำเภอวิเศษชัยชาญ', 'sub' => 10, 'vil' => 61, 'com' => 0, 'hh' => 1081, 'flood' => 1],
            ['district_id' => 7, 'district_name' => 'อำเภอสามโก้', 'sub' => 5, 'vil' => 34, 'com' => 0, 'hh' => 337, 'flood' => 1],
            ['district_id' => 4, 'district_name' => 'อำเภอโพธิ์ทอง', 'sub' => 0, 'vil' => 0, 'com' => 0, 'hh' => 0, 'flood' => 0],
            ['district_id' => 5, 'district_name' => 'อำเภอแสวงหา', 'sub' => 0, 'vil' => 0, 'com' => 0, 'hh' => 0, 'flood' => 0]
        ];

        $stmtD = $pdo->prepare("
            INSERT INTO district_flood_stats 
            (report_date, district_id, district_name, subdistricts_affected, villages_affected, communities_affected, households_affected, is_flooded)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            subdistricts_affected = VALUES(subdistricts_affected), villages_affected = VALUES(villages_affected),
            communities_affected = VALUES(communities_affected), households_affected = VALUES(households_affected), is_flooded = VALUES(is_flooded)
        ");
        foreach ($distData as $d) {
            $stmtD->execute([$targetDate, $d['district_id'], $d['district_name'], $d['sub'], $d['vil'], $d['com'], $d['hh'], $d['flood']]);
        }

        // 3. Ensure flood_reports contains exactly 1,087 vulnerable groups sum
        // Set sample values on flood_reports for org 56 (Samko) and org 48 (Wisetchai)
        $pdo->prepare("
            INSERT INTO flood_reports (
                organization_id, organization_name, district_id, district_name, user_id, user_name, report_date,
                impact_status, service_status,
                bedridden_total, bedridden_flooded, bedridden_home,
                pregnant_total, pregnant_flooded, pregnant_home,
                disabled_total, disabled_flooded, disabled_home,
                children_total, children_flooded, children_home,
                elderly_total, elderly_flooded, elderly_home,
                dialysis_total, dialysis_flooded, dialysis_home,
                psychiatric_total, psychiatric_flooded, psychiatric_home,
                ncd_total, ncd_flooded, ncd_home,
                mobile_clinic_visits, mcatt_visits, srrt_visits, people_served, home_visits, med_distribution, pfa_support
            ) VALUES (
                1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)', 1, 'อำเภอเมืองอ่างทอง', 1, 'Super Admin', ?,
                'normal', 'normal',
                111, 111, 111,
                15, 15, 15,
                39, 39, 39,
                33, 33, 33,
                332, 332, 332,
                6, 6, 6,
                298, 298, 298,
                253, 253, 253,
                4, 2, 1, 431, 584, 508, 28
            ) ON DUPLICATE KEY UPDATE
            bedridden_flooded = 111, pregnant_flooded = 15, disabled_flooded = 39, children_flooded = 33,
            elderly_flooded = 332, dialysis_flooded = 6, psychiatric_flooded = 298, ncd_flooded = 253,
            home_visits = 584, people_served = 431
        ")->execute([$targetDate]);

        // 4. Ensure flood_medical_services sums to exactly 1,857 services and 431 disease cases
        $pdo->prepare("DELETE FROM flood_medical_services WHERE report_date = ?")->execute([$targetDate]);
        $pdo->prepare("
            INSERT INTO flood_medical_services (
                report_date, district_id, district_name, organization_id, organization_name, user_id, user_name,
                outreach_location, village_no, village_name, people_served,
                team_mobile_clinic, team_mcatt, team_srrt, team_shert,
                service_home_visit, service_health_edu, service_treatment, service_chronic_meds, service_med_dispense, service_referral, service_other,
                mental_screened, mental_high_stress, mental_depression, mental_suicide_risk, mental_help_pfa, mental_help_doctor,
                disease_athletes_foot, disease_skin, disease_muscle_pain, disease_headache, disease_eye_infection, disease_dengue,
                resource_foot_cream, resource_relief_kit, resource_garbage_bag, resource_mosquito_repellent, resource_home_medicine, resource_antifungal,
                shelter_count, shelter_capacity, shelter_occupants, reporter_name, reporter_phone
            ) VALUES (
                ?, 1, 'อำเภอเมืองอ่างทอง', 1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)', 1, 'กลุ่มงานควบคุมโรค สสจ.อ่างทอง',
                'จุดตรวจพื้นที่ลุ่มน้ำเจ้าพระยาและแม่น้ำน้อย', 1, 'ชุมชนริมน้ำ', 431,
                8, 4, 2, 0,
                584, 569, 0, 70, 508, 0, 0,
                126, 0, 0, 0, 28, 0,
                356, 36, 25, 8, 5, 1,
                250, 79, 54, 36, 5, 0,
                2, 300, 0, 'กลุ่มภารกิจตระหนักรู้สถานการณ์ สสจ.อ่างทอง', '035-611234'
            )
        ")->execute([$targetDate]);

        resp('success', "โหลดข้อมูลตัวอย่างตามแบบฟอร์มประจำวันที่ {$targetDate} เรียบร้อยแล้ว");
        break;

    default:
        resp('error', 'Invalid action');
        break;
}
