<?php
// config/db.php - Database connection & Auto-initialization for Provincial Health & Flood System
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1';
$port = '3306';
$db_user = 'root';
$db_pass = '';
$db_name = 'water_db';

try {
    // 1. Connect without db to ensure database exists
    $pdo_init = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 2. Connect to the database
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // 3. Ensure tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `districts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name_th` VARCHAR(100) NOT NULL UNIQUE,
            `code` VARCHAR(20) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `organizations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(20) DEFAULT NULL,
            `district_id` INT NOT NULL,
            `name_th` VARCHAR(255) NOT NULL,
            `subdistrict` VARCHAR(100) DEFAULT NULL,
            `type` VARCHAR(50) NOT NULL DEFAULT 'health_center',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_district` (`district_id`),
            INDEX `idx_code` (`code`),
            INDEX `idx_type` (`type`),
            CONSTRAINT `fk_org_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `villages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `organization_id` INT NOT NULL,
            `hospcode` VARCHAR(20) NOT NULL,
            `moo_code` VARCHAR(20) NOT NULL,
            `village_no` INT NOT NULL,
            `village_name` VARCHAR(150) NOT NULL,
            `subdistrict` VARCHAR(100) NOT NULL,
            `district_id` INT NOT NULL,
            `district_name` VARCHAR(100) NOT NULL,
            `province_name` VARCHAR(100) NOT NULL DEFAULT 'อ่างทอง',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_org` (`organization_id`),
            INDEX `idx_hospcode` (`hospcode`),
            INDEX `idx_district` (`district_id`),
            INDEX `idx_subdistrict` (`subdistrict`),
            UNIQUE KEY `uq_org_moo` (`organization_id`, `moo_code`),
            CONSTRAINT `fk_villages_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(60) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `fullname` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(25) NOT NULL,
            `district_id` INT DEFAULT NULL,
            `district_name` VARCHAR(100) DEFAULT NULL,
            `organization_id` INT DEFAULT NULL,
            `organization_name` VARCHAR(255) DEFAULT NULL,
            `role` ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
            `status` ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
            `rejection_reason` TEXT DEFAULT NULL,
            `approved_by` INT DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_status` (`status`),
            INDEX `idx_role` (`role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `flood_reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `report_date` DATE NOT NULL,
            `user_id` INT NOT NULL,
            `user_name` VARCHAR(150) DEFAULT NULL,
            `district_id` INT NOT NULL,
            `district_name` VARCHAR(100) NOT NULL,
            `organization_id` INT NOT NULL,
            `organization_name` VARCHAR(255) NOT NULL,
            
            -- 1. Facility Impact & Service
            `impact_status` ENUM('normal', 'affected') NOT NULL DEFAULT 'normal',
            `service_status` ENUM('normal', 'partial', 'closed') NOT NULL DEFAULT 'normal',
            `impact_details` TEXT DEFAULT NULL,
            `substitute_location` VARCHAR(255) DEFAULT NULL,

            -- 2. Vulnerable Groups
            -- Bedridden (ผู้ป่วยติดเตียง)
            `bedridden_total` INT DEFAULT 0,
            `bedridden_flooded` INT DEFAULT 0,
            `bedridden_home` INT DEFAULT 0,
            `bedridden_shelter` INT DEFAULT 0,
            `bedridden_hospital` INT DEFAULT 0,

            -- Dialysis (ผู้ป่วยฟอกไต)
            `dialysis_total` INT DEFAULT 0,
            `dialysis_flooded` INT DEFAULT 0,
            `dialysis_home` INT DEFAULT 0,
            `dialysis_shelter` INT DEFAULT 0,
            `dialysis_missed` INT DEFAULT 0,

            -- Psychiatric (จิตเวชที่ต้องรับยา)
            `psychiatric_total` INT DEFAULT 0,
            `psychiatric_flooded` INT DEFAULT 0,
            `psychiatric_home` INT DEFAULT 0,
            `psychiatric_shelter` INT DEFAULT 0,
            `psychiatric_health_issue` INT DEFAULT 0,

            -- Elderly (ผู้สูงอายุ)
            `elderly_total` INT DEFAULT 0,
            `elderly_flooded` INT DEFAULT 0,
            `elderly_home` INT DEFAULT 0,
            `elderly_shelter` INT DEFAULT 0,
            `elderly_out_of_meds` INT DEFAULT 0,

            -- Disabled (ผู้พิการ)
            `disabled_total` INT DEFAULT 0,
            `disabled_flooded` INT DEFAULT 0,
            `disabled_home` INT DEFAULT 0,
            `disabled_shelter` INT DEFAULT 0,
            `disabled_health_issue` INT DEFAULT 0,

            -- Chronic NCD (ผู้ป่วยโรคเรื้อรัง)
            `ncd_total` INT DEFAULT 0,
            `ncd_flooded` INT DEFAULT 0,
            `ncd_home` INT DEFAULT 0,
            `ncd_shelter` INT DEFAULT 0,
            `ncd_out_of_meds` INT DEFAULT 0,

            -- Pregnant Women (หญิงตั้งครรภ์)
            `pregnant_total` INT DEFAULT 0,
            `pregnant_flooded` INT DEFAULT 0,
            `pregnant_home` INT DEFAULT 0,
            `pregnant_shelter` INT DEFAULT 0,
            `pregnant_health_issue` INT DEFAULT 0,

            -- Children 0-5 (เด็กอายุ 0-5 ปี)
            `children_total` INT DEFAULT 0,
            `children_flooded` INT DEFAULT 0,
            `children_home` INT DEFAULT 0,
            `children_shelter` INT DEFAULT 0,
            `children_health_issue` INT DEFAULT 0,

            -- 3. Medical & Mobile Health Services
            `mobile_clinic_visits` INT DEFAULT 0,
            `mcatt_visits` INT DEFAULT 0,
            `srrt_visits` INT DEFAULT 0,
            `people_served` INT DEFAULT 0,
            `home_visits` INT DEFAULT 0,
            `med_distribution` INT DEFAULT 0,
            `pfa_support` INT DEFAULT 0,
            `doctor_consults` INT DEFAULT 0,

            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY `unique_facility_date` (`organization_id`, `report_date`),
            INDEX `idx_report_date` (`report_date`),
            INDEX `idx_district` (`district_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `flood_report_villages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `report_id` INT NOT NULL,
            `report_date` DATE NOT NULL,
            `organization_id` INT NOT NULL,
            `subdistrict` VARCHAR(100) NOT NULL,
            `village_no` INT NOT NULL,
            `village_name` VARCHAR(150) DEFAULT NULL,
            
            -- 1. Bedridden (ผู้ป่วยติดเตียง)
            `bedridden_total` INT DEFAULT 0,
            `bedridden_flooded` INT DEFAULT 0,
            `bedridden_home` INT DEFAULT 0,
            `bedridden_shelter` INT DEFAULT 0,
            `bedridden_hospital` INT DEFAULT 0,
            
            -- 2. Dialysis (ผู้ป่วยฟอกไต)
            `dialysis_total` INT DEFAULT 0,
            `dialysis_flooded` INT DEFAULT 0,
            `dialysis_home` INT DEFAULT 0,
            `dialysis_shelter` INT DEFAULT 0,
            `dialysis_missed` INT DEFAULT 0,
            
            -- 3. Psychiatric (จิตเวชที่ต้องรับยา)
            `psychiatric_total` INT DEFAULT 0,
            `psychiatric_flooded` INT DEFAULT 0,
            `psychiatric_home` INT DEFAULT 0,
            `psychiatric_shelter` INT DEFAULT 0,
            `psychiatric_health_issue` INT DEFAULT 0,
            
            -- 4. Elderly (ผู้สูงอายุ)
            `elderly_total` INT DEFAULT 0,
            `elderly_flooded` INT DEFAULT 0,
            `elderly_home` INT DEFAULT 0,
            `elderly_shelter` INT DEFAULT 0,
            `elderly_out_of_meds` INT DEFAULT 0,
            
            -- 5. Disabled (ผู้พิการ)
            `disabled_total` INT DEFAULT 0,
            `disabled_flooded` INT DEFAULT 0,
            `disabled_home` INT DEFAULT 0,
            `disabled_shelter` INT DEFAULT 0,
            `disabled_health_issue` INT DEFAULT 0,
            
            -- 6. Chronic NCD (ผู้ป่วยโรคเรื้อรัง)
            `ncd_total` INT DEFAULT 0,
            `ncd_flooded` INT DEFAULT 0,
            `ncd_home` INT DEFAULT 0,
            `ncd_shelter` INT DEFAULT 0,
            `ncd_out_of_meds` INT DEFAULT 0,
            
            -- 7. Pregnant (หญิงตั้งครรภ์)
            `pregnant_total` INT DEFAULT 0,
            `pregnant_flooded` INT DEFAULT 0,
            `pregnant_home` INT DEFAULT 0,
            `pregnant_shelter` INT DEFAULT 0,
            `pregnant_health_issue` INT DEFAULT 0,
            
            -- 8. Children 0-5 (เด็กอายุ 0-5 ปี)
            `children_total` INT DEFAULT 0,
            `children_flooded` INT DEFAULT 0,
            `children_home` INT DEFAULT 0,
            `children_shelter` INT DEFAULT 0,
            `children_health_issue` INT DEFAULT 0,
            
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_report_village` (`report_id`, `subdistrict`, `village_no`),
            INDEX `idx_report` (`report_id`),
            INDEX `idx_date_org` (`report_date`, `organization_id`),
            CONSTRAINT `fk_villages_report` FOREIGN KEY (`report_id`) REFERENCES `flood_reports` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT NULL,
            `username` VARCHAR(60) DEFAULT NULL,
            `fullname` VARCHAR(150) DEFAULT NULL,
            `district_name` VARCHAR(100) DEFAULT NULL,
            `organization_name` VARCHAR(255) DEFAULT NULL,
            `action` VARCHAR(100) NOT NULL,
            `details` TEXT DEFAULT NULL,
            `report_date` DATE DEFAULT NULL,
            `ip_address` VARCHAR(50) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- 3. Medical & Public Health Outreach Services (ส่วนที่ 3 ตามแบบฟอร์มไฟล์ราชการ)
        CREATE TABLE IF NOT EXISTS `flood_medical_services` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `report_date` DATE NOT NULL,
            `district_id` INT NOT NULL,
            `district_name` VARCHAR(100) NOT NULL,
            `organization_id` INT NOT NULL,
            `organization_name` VARCHAR(255) NOT NULL,
            `user_id` INT NOT NULL,
            `user_name` VARCHAR(150) NOT NULL,
            `outreach_location` VARCHAR(255) DEFAULT NULL,
            `village_no` INT DEFAULT NULL,
            `village_name` VARCHAR(150) DEFAULT NULL,
            `people_served` INT DEFAULT 0,

            -- 1. Operational Teams (จำนวนครั้ง)
            `team_mobile_clinic` INT DEFAULT 0,
            `team_mcatt` INT DEFAULT 0,
            `team_srrt` INT DEFAULT 0,
            `team_shert` INT DEFAULT 0,

            -- 2. Medical Services (ราย)
            `service_chronic_meds` INT DEFAULT 0,
            `service_home_visit` INT DEFAULT 0,
            `service_med_dispense` INT DEFAULT 0,
            `service_health_edu` INT DEFAULT 0,
            `service_treatment` INT DEFAULT 0,
            `service_referral` INT DEFAULT 0,
            `service_other` INT DEFAULT 0,

            -- 3. Mental Health Screening (ราย)
            `mental_screened` INT DEFAULT 0,
            `mental_high_stress` INT DEFAULT 0,
            `mental_depression` INT DEFAULT 0,
            `mental_suicide_risk` INT DEFAULT 0,
            `mental_help_pfa` INT DEFAULT 0,
            `mental_help_doctor` INT DEFAULT 0,

            -- 4. Common Diseases (ราย)
            `disease_respiratory` INT DEFAULT 0,
            `disease_gastrointestinal` INT DEFAULT 0,
            `disease_circulatory` INT DEFAULT 0,
            `disease_eye_infection` INT DEFAULT 0,
            `disease_skin` INT DEFAULT 0,
            `disease_athletes_foot` INT DEFAULT 0,
            `disease_muscle_pain` INT DEFAULT 0,
            `disease_headache` INT DEFAULT 0,
            `disease_dengue` INT DEFAULT 0,
            `disease_diarrhea` INT DEFAULT 0,
            `disease_fatigue` INT DEFAULT 0,
            `disease_viral` INT DEFAULT 0,
            `disease_other` INT DEFAULT 0,
            `disease_other_text` VARCHAR(255) DEFAULT NULL,

            -- 5. Water Accidents (ราย)
            `accident_drowning` INT DEFAULT 0,
            `accident_electrocution` INT DEFAULT 0,

            -- 6. Resource Support
            `resource_relief_kit` INT DEFAULT 0,
            `resource_home_medicine` INT DEFAULT 0,
            `resource_foot_cream` INT DEFAULT 0,
            `resource_antifungal` INT DEFAULT 0,
            `resource_boots` INT DEFAULT 0,
            `resource_mosquito_repellent` INT DEFAULT 0,
            `resource_garbage_bag` INT DEFAULT 0,
            `resource_alum` INT DEFAULT 0,
            `resource_chlorine` INT DEFAULT 0,
            `resource_em` INT DEFAULT 0,
            `resource_other` INT DEFAULT 0,

            -- 7. Shelters & Reporter
            `shelter_count` INT DEFAULT 0,
            `shelter_capacity` INT DEFAULT 0,
            `shelter_occupants` INT DEFAULT 0,
            `reporter_name` VARCHAR(150) DEFAULT NULL,
            `reporter_phone` VARCHAR(50) DEFAULT NULL,

            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_date` (`report_date`),
            INDEX `idx_org` (`organization_id`),
            INDEX `idx_district` (`district_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- 4. Provincial Flood Situation Parameters (สำหรับ OnePage Dashboard)
        CREATE TABLE IF NOT EXISTS `flood_provincial_situation` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `report_date` DATE NOT NULL UNIQUE,
            `rain_situation` VARCHAR(255) DEFAULT 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง',
            `water_discharge` VARCHAR(100) DEFAULT '8.62 (+0.47) (จุดหน้าศาลากลาง)',
            `shelter_total_sites` INT DEFAULT 2,
            `shelter_capacity` INT DEFAULT 300,
            `shelter_occupants` INT DEFAULT 0,
            `deaths` INT DEFAULT 0,
            `injuries` INT DEFAULT 0,
            `missing` INT DEFAULT 0,
            `data_source` VARCHAR(255) DEFAULT 'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง',
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        -- 5. District Flood Affected Stats (สำหรับตารางพื้นที่และแผนที่ OnePage)
        CREATE TABLE IF NOT EXISTS `district_flood_stats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `report_date` DATE NOT NULL,
            `district_id` INT NOT NULL,
            `district_name` VARCHAR(100) NOT NULL,
            `subdistricts_affected` INT DEFAULT 0,
            `villages_affected` INT DEFAULT 0,
            `communities_affected` INT DEFAULT 0,
            `households_affected` INT DEFAULT 0,
            `is_flooded` TINYINT(1) DEFAULT 0,
            UNIQUE KEY `uq_dist_date` (`report_date`, `district_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Ensure audit_logs columns exist if table already existed prior
    $cols = $pdo->query("SHOW COLUMNS FROM `audit_logs` LIKE 'username'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("
            ALTER TABLE `audit_logs`
            ADD COLUMN `username` VARCHAR(60) DEFAULT NULL AFTER `user_id`,
            ADD COLUMN `fullname` VARCHAR(150) DEFAULT NULL AFTER `username`,
            ADD COLUMN `district_name` VARCHAR(100) DEFAULT NULL AFTER `fullname`,
            ADD COLUMN `organization_name` VARCHAR(255) DEFAULT NULL AFTER `district_name`,
            ADD COLUMN `report_date` DATE DEFAULT NULL AFTER `details`;
        ");
    }

    // 4. Check & Re-seed strictly Health-only organizations: รพ.สต., สสอ., โรงพยาบาล
    // If table contains non-health orgs (like local_gov or district_office), reset & re-seed
    $hasNonHealth = $pdo->query("SELECT COUNT(*) FROM `organizations` WHERE `type` IN ('local_gov', 'district_office', 'irrigation', 'general')")->fetchColumn();
    $orgCount = $pdo->query("SELECT COUNT(*) FROM `organizations`")->fetchColumn();

    if ($hasNonHealth > 0 || $orgCount == 0) {
        // Clear non-health agencies
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `organizations`;");
        $pdo->exec("TRUNCATE TABLE `districts`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Health-only master dataset for Ang Thong Province (7 Districts)
        $healthDistricts = [
            'อำเภอเมืองอ่างทอง' => [
                'code' => '1501',
                'orgs' => [
                    ['สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)', 'provincial_office'],
                    ['โรงพยาบาลอ่างทอง (รพท.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอเมืองอ่างทอง (สสอ.เมืองอ่างทอง)', 'health_office'],
                    ['รพ.สต.บ้านอิฐ', 'health_center'],
                    ['รพ.สต.ศาลาแดง', 'health_center'],
                    ['รพ.สต.จำปาหล่อ', 'health_center'],
                    ['รพ.สต.โพสะ', 'health_center'],
                    ['รพ.สต.มหาดไทย', 'health_center'],
                    ['รพ.สต.ป่างิ้ว', 'health_center'],
                    ['รพ.สต.หัวไผ่', 'health_center'],
                    ['รพ.สต.คลองวัว', 'health_center']
                ]
            ],
            'อำเภอไชโย' => [
                'code' => '1502',
                'orgs' => [
                    ['โรงพยาบาลไชโย (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอไชโย (สสอ.ไชโย)', 'health_office'],
                    ['รพ.สต.จรเข้ร้อง', 'health_center'],
                    ['รพ.สต.ไชยภูมิ', 'health_center'],
                    ['รพ.สต.ชัยฤทธิ์', 'health_center'],
                    ['รพ.สต.หลักแก้ว', 'health_center'],
                    ['รพ.สต.มหานาม', 'health_center'],
                    ['รพ.สต.ราชสถิตย์', 'health_center']
                ]
            ],
            'อำเภอป่าโมก' => [
                'code' => '1503',
                'orgs' => [
                    ['โรงพยาบาลป่าโมก (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอป่าโมก (สสอ.ป่าโมก)', 'health_office'],
                    ['รพ.สต.บางเสด็จ', 'health_center'],
                    ['รพ.สต.โผงเผง', 'health_center'],
                    ['รพ.สต.บางปลากด', 'health_center'],
                    ['รพ.สต.นรสิงห์', 'health_center'],
                    ['รพ.สต.โรงช้าง', 'health_center'],
                    ['รพ.สต.สายทอง', 'health_center']
                ]
            ],
            'อำเภอโพธิ์ทอง' => [
                'code' => '1504',
                'orgs' => [
                    ['โรงพยาบาลโพธิ์ทอง (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอโพธิ์ทอง (สสอ.โพธิ์ทอง)', 'health_office'],
                    ['รพ.สต.อ่างแก้ว', 'health_center'],
                    ['รพ.สต.อินทประมูล', 'health_center'],
                    ['รพ.สต.บางพลับ', 'health_center'],
                    ['รพ.สต.รำมะสัก', 'health_center'],
                    ['รพ.สต.โคกพุทรา', 'health_center'],
                    ['รพ.สต.สามง่าม', 'health_center'],
                    ['รพ.สต.บ่อแร่', 'health_center']
                ]
            ],
            'อำเภอแสวงหา' => [
                'code' => '1505',
                'orgs' => [
                    ['โรงพยาบาลแสวงหา (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอแสวงหา (สสอ.แสวงหา)', 'health_office'],
                    ['รพ.สต.บ้านพราน', 'health_center'],
                    ['รพ.สต.วังน้ำเย็น', 'health_center'],
                    ['รพ.สต.สีบัวทอง', 'health_center'],
                    ['รพ.สต.จำลอง', 'health_center'],
                    ['รพ.สต.ห้วยไผ่', 'health_center']
                ]
            ],
            'อำเภอวิเศษชัยชาญ' => [
                'code' => '1506',
                'orgs' => [
                    ['โรงพยาบาลวิเศษชัยชาญ (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอวิเศษชัยชาญ (สสอ.วิเศษชัยชาญ)', 'health_office'],
                    ['รพ.สต.ศาลเจ้าโรงทอง', 'health_center'],
                    ['รพ.สต.ไผ่จำศีล', 'health_center'],
                    ['รพ.สต.หัวตะพาน', 'health_center'],
                    ['รพ.สต.สาวร้องไห้', 'health_center'],
                    ['รพ.สต.คลองขนาก', 'health_center'],
                    ['รพ.สต.ไผ่ดำพัฒนา', 'health_center'],
                    ['รพ.สต.ยี่ล้น', 'health_center']
                ]
            ],
            'อำเภอสามโก้' => [
                'code' => '1507',
                'orgs' => [
                    ['โรงพยาบาลสามโก้ (รพช.)', 'hospital'],
                    ['สำนักงานสาธารณสุขอำเภอสามโก้ (สสอ.สามโก้)', 'health_office'],
                    ['รพ.สต.บ้านสามโก้', 'health_center'],
                    ['รพ.สต.มงคลธรรมนิมิต', 'health_center'],
                    ['รพ.สต.ราษฎรพัฒนา', 'health_center'],
                    ['รพ.สต.อบทม', 'health_center'],
                    ['รพ.สต.โพธิ์ม่วงพันธ์', 'health_center']
                ]
            ]
        ];

        $stmtDistrict = $pdo->prepare("INSERT INTO `districts` (`name_th`, `code`) VALUES (?, ?)");
        $stmtOrg = $pdo->prepare("INSERT INTO `organizations` (`district_id`, `name_th`, `type`) VALUES (?, ?, ?)");

        foreach ($healthDistricts as $dName => $dData) {
            $stmtDistrict->execute([$dName, $dData['code']]);
            $districtId = $pdo->lastInsertId();

            foreach ($dData['orgs'] as $org) {
                $stmtOrg->execute([$districtId, $org[0], $org[1]]);
            }
        }
    }

    // 5. Seed / Update Default Accounts: Superadmin, Admin (ภาพรวมจังหวัด), User
    $adminPass = password_hash('admin1234', PASSWORD_DEFAULT);
    $userPass = password_hash('user1234', PASSWORD_DEFAULT);

    // Super Admin Account
    $hasSuperAdmin = $pdo->query("SELECT id FROM `users` WHERE `username` = 'superadmin'")->fetch();
    if (!$hasSuperAdmin) {
        $pdo->prepare("
            INSERT INTO `users` (
                `username`, `password`, `fullname`, `phone`, 
                `district_id`, `district_name`, `organization_id`, `organization_name`, 
                `role`, `status`, `approved_at`
            ) VALUES (
                'superadmin', ?, 'ผู้ดูแลระบบระดับสูง (Super Admin)', '035-611234',
                1, 'อำเภอเมืองอ่างทอง', 1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)',
                'superadmin', 'approved', NOW()
            )
        ")->execute([$adminPass]);
    }

    // Admin Account (สำหรับผู้บริหารดูภาพรวมจังหวัดและยอดประจำวัน)
    $hasAdmin = $pdo->query("SELECT id FROM `users` WHERE `username` = 'admin.angthong'")->fetch();
    if (!$hasAdmin) {
        $pdo->prepare("
            INSERT INTO `users` (
                `username`, `password`, `fullname`, `phone`, 
                `district_id`, `district_name`, `organization_id`, `organization_name`, 
                `role`, `status`, `approved_at`
            ) VALUES (
                'admin.angthong', ?, 'นพ.สสจ.อ่างทอง (Admin ภาพรวมจังหวัด)', '035-611234 ต่อ 101',
                1, 'อำเภอเมืองอ่างทอง', 1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)',
                'admin', 'approved', NOW()
            )
        ")->execute([$adminPass]);
    }

    // Sample Pending User: รพ.สต.บ้านสามโก้
    $hasSomchai = $pdo->query("SELECT id FROM `users` WHERE `username` = 'somchai.s'")->fetch();
    $orgSamko = $pdo->query("SELECT id, district_id FROM `organizations` WHERE `name_th` LIKE '%รพ.สต.บ้านสามโก้%' LIMIT 1")->fetch();
    if (!$hasSomchai && $orgSamko) {
        $pdo->prepare("
            INSERT INTO `users` (
                `username`, `password`, `fullname`, `phone`, 
                `district_id`, `district_name`, `organization_id`, `organization_name`, 
                `role`, `status`
            ) VALUES (
                'somchai.s', ?, 'นายสมชาย สุขเกษม', '089-1234567',
                ?, 'อำเภอสามโก้', ?, 'รพ.สต.บ้านสามโก้',
                'user', 'pending'
            )
        ")->execute([$userPass, $orgSamko['district_id'], $orgSamko['id']]);
    }

    // Sample Approved User: รพ.สต.มงคลธรรมนิมิต
    $hasSarawut = $pdo->query("SELECT id FROM `users` WHERE `username` = 'sarawut.m'")->fetch();
    $orgMongkol = $pdo->query("SELECT id, district_id FROM `organizations` WHERE `name_th` LIKE '%รพ.สต.มงคลธรรมนิมิต%' LIMIT 1")->fetch();
    if (!$hasSarawut && $orgMongkol) {
        $pdo->prepare("
            INSERT INTO `users` (
                `username`, `password`, `fullname`, `phone`, 
                `district_id`, `district_name`, `organization_id`, `organization_name`, 
                `role`, `status`, `approved_at`
            ) VALUES (
                'sarawut.m', ?, 'นายศราวุธ มงคลดี', '086-5554321',
                ?, 'อำเภอสามโก้', ?, 'รพ.สต.มงคลธรรมนิมิต',
                'user', 'approved', NOW()
            )
        ")->execute([$userPass, $orgMongkol['district_id'], $orgMongkol['id']]);
    }

    // Sample Approved User: โรงพยาบาลป่าโมก
    $hasKanokwan = $pdo->query("SELECT id FROM `users` WHERE `username` = 'kanokwan.p'")->fetch();
    $orgPamok = $pdo->query("SELECT id, district_id FROM `organizations` WHERE `name_th` LIKE '%โรงพยาบาลป่าโมก%' LIMIT 1")->fetch();
    if (!$hasKanokwan && $orgPamok) {
        $pdo->prepare("
            INSERT INTO `users` (
                `username`, `password`, `fullname`, `phone`, 
                `district_id`, `district_name`, `organization_id`, `organization_name`, 
                `role`, `status`, `approved_at`
            ) VALUES (
                'kanokwan.p', ?, 'นางสาวกนกวรรณ พัฒนกิจ', '081-9876543',
                ?, 'อำเภอป่าโมก', ?, 'โรงพยาบาลป่าโมก (รพช.)',
                'user', 'approved', NOW()
            )
        ")->execute([$userPass, $orgPamok['district_id'], $orgPamok['id']]);
    }

    // 6. Pre-seed authentic sample flood report for today from Excel sheet
    $todayDate = date('Y-m-d');
    $hasReportToday = $pdo->query("SELECT COUNT(*) FROM `flood_reports` WHERE `report_date` = '{$todayDate}'")->fetchColumn();
    if ($hasReportToday == 0 && $orgMongkol) {
        $stmtReportSeed = $pdo->prepare("
            INSERT INTO `flood_reports` (
                `report_date`, `user_id`, `user_name`, `district_id`, `district_name`, 
                `organization_id`, `organization_name`, `impact_status`, `service_status`, 
                `impact_details`, `bedridden_total`, `bedridden_flooded`, `bedridden_home`, 
                `dialysis_total`, `dialysis_flooded`, `dialysis_home`, 
                `psychiatric_total`, `psychiatric_flooded`, `psychiatric_home`, `psychiatric_health_issue`, 
                `elderly_total`, `elderly_flooded`, `elderly_home`, `elderly_out_of_meds`, 
                `disabled_total`, `disabled_flooded`, `disabled_home`, 
                `ncd_total`, `ncd_flooded`, `ncd_home`, `ncd_out_of_meds`, 
                `pregnant_total`, `pregnant_flooded`, `pregnant_home`, 
                `children_total`, `children_flooded`, `children_home`, 
                `people_served`, `home_visits`, `med_distribution`, `notes`, `created_at`
            ) VALUES (
                ?, 3, 'นายศราวุธ มงคลดี', ?, 'อำเภอสามโก้', 
                ?, 'รพ.สต.มงคลธรรมนิมิต', 'affected', 'partial', 
                'ระดับน้ำท่วมขังบริเวณทางเข้าและลานจอดรถด้านหน้า 20-30 ซม. อาคารบริการชั้น 1 ยังปลอดภัย เปิดให้บริการตรวจรักษาบางส่วน',
                12, 3, 3, 
                2, 0, 0, 
                20, 2, 2, 0, 
                146, 18, 18, 2, 
                24, 4, 4, 
                65, 8, 8, 1, 
                5, 1, 1, 
                16, 2, 2, 
                20, 16, 5, 
                'จัดทีมปฏิบัติการเยี่ยมบ้านผู้สูงอายุและผู้ป่วยติดเตียง แจกจ่ายยาจำเป็นประจำวัน',
                NOW()
            )
        ");
        $stmtReportSeed->execute([$todayDate, $orgMongkol['district_id'], $orgMongkol['id']]);

        // Add log
        $pdo->prepare("
            INSERT INTO `audit_logs` (
                `user_id`, `username`, `fullname`, `district_name`, `organization_name`, 
                `action`, `details`, `report_date`, `ip_address`, `created_at`
            ) VALUES (
                3, 'sarawut.m', 'นายศราวุธ มงคลดี', 'อำเภอสามโก้', 'รพ.สต.มงคลธรรมนิมิต',
                'submit_flood_report', 'บันทึกรายงานสถานการณ์อุทกภัยประจำวัน: เปิดบางส่วน, ผู้ป่วยน้ำท่วม 38 ราย, ออกเยี่ยมบ้าน 16 ราย', ?, '127.0.0.1', NOW()
            )
        ")->execute([$todayDate]);
    }

    // Seed district_flood_stats for $todayDate if empty
    $hasDistStats = $pdo->prepare("SELECT COUNT(*) FROM district_flood_stats WHERE report_date = ?");
    $hasDistStats->execute([$todayDate]);
    if ($hasDistStats->fetchColumn() == 0) {
        $distData = [
            ['district_id' => 1, 'district_name' => 'อำเภอเมืองอ่างทอง', 'sub' => 9, 'vil' => 23, 'com' => 14, 'hh' => 285, 'flood' => 1],
            ['district_id' => 3, 'district_name' => 'อำเภอป่าโมก', 'sub' => 3, 'vil' => 11, 'com' => 0, 'hh' => 248, 'flood' => 1],
            ['district_id' => 2, 'district_name' => 'อำเภอไชโย', 'sub' => 6, 'vil' => 17, 'com' => 0, 'hh' => 58, 'flood' => 1],
            ['district_id' => 6, 'district_name' => 'อำเภอวิเศษชัยชาญ', 'sub' => 10, 'vil' => 61, 'com' => 0, 'hh' => 1081, 'flood' => 1],
            ['district_id' => 7, 'district_name' => 'อำเภอสามโก้', 'sub' => 5, 'vil' => 34, 'com' => 0, 'hh' => 337, 'flood' => 1],
            ['district_id' => 4, 'district_name' => 'อำเภอโพธิ์ทอง', 'sub' => 0, 'vil' => 0, 'com' => 0, 'hh' => 0, 'flood' => 0],
            ['district_id' => 5, 'district_name' => 'อำเภอแสวงหา', 'sub' => 0, 'vil' => 0, 'com' => 0, 'hh' => 0, 'flood' => 0],
        ];
        $stmtDist = $pdo->prepare("
            INSERT INTO district_flood_stats 
            (report_date, district_id, district_name, subdistricts_affected, villages_affected, communities_affected, households_affected, is_flooded)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($distData as $d) {
            $stmtDist->execute([$todayDate, $d['district_id'], $d['district_name'], $d['sub'], $d['vil'], $d['com'], $d['hh'], $d['flood']]);
        }
    }

    // Seed flood_provincial_situation for $todayDate if empty
    $hasSit = $pdo->prepare("SELECT COUNT(*) FROM flood_provincial_situation WHERE report_date = ?");
    $hasSit->execute([$todayDate]);
    if ($hasSit->fetchColumn() == 0) {
        $pdo->prepare("
            INSERT INTO flood_provincial_situation 
            (report_date, rain_situation, water_discharge, shelter_total_sites, shelter_capacity, shelter_occupants, deaths, injuries, missing, data_source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $todayDate,
            'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง',
            '8.62 (+0.47) (จุดหน้าศาลากลาง)',
            2,
            300,
            0,
            0,
            0,
            0,
            'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง'
        ]);
    }

    // Seed flood_medical_services for $todayDate if empty
    $hasMedServ = $pdo->prepare("SELECT COUNT(*) FROM flood_medical_services WHERE report_date = ?");
    $hasMedServ->execute([$todayDate]);
    if ($hasMedServ->fetchColumn() == 0) {
        $medSeeds = [
            [
                'district_id' => 7, 'district_name' => 'อำเภอสามโก้', 'org_id' => 56, 'org_name' => 'รพ.สต.มงคลธรรมนิมิต',
                'user_id' => 6, 'user_name' => 'นายสราวุฒิ มงคลธรรม', 'loc' => 'ต.มงคลธรรมนิมิต', 'v_no' => 2, 'v_name' => 'หนองถ้ำ',
                'people' => 16, 'mobile' => 1, 'mcatt' => 1, 'srrt' => 0, 'shert' => 0,
                'chronic_meds' => 5, 'home_visit' => 16, 'dispense' => 5, 'edu' => 16, 'treat' => 0, 'refer' => 0, 'other_serv' => 0,
                'screen' => 16, 'stress' => 0, 'depress' => 0, 'suicide' => 0, 'pfa' => 5, 'doc' => 0,
                'd_resp' => 0, 'd_gi' => 0, 'd_circ' => 0, 'd_eye' => 0, 'd_skin' => 3, 'd_foot' => 10, 'd_muscle' => 3, 'd_head' => 0, 'd_dengue' => 0, 'd_dia' => 0, 'd_fatigue' => 0, 'd_viral' => 0, 'd_other' => 0,
                'drown' => 0, 'shock' => 0,
                'r_relief' => 5, 'r_home' => 5, 'r_foot' => 10, 'r_anti' => 0, 'r_boots' => 0, 'r_repel' => 5, 'r_bag' => 10, 'r_alum' => 0, 'r_chlorine' => 0, 'r_em' => 0, 'r_other' => 0,
                'shelter_count' => 0, 'shelter_cap' => 0, 'shelter_occ' => 0, 'rep_name' => 'นายสราวุฒิ มงคลธรรม', 'rep_phone' => '089-1234567'
            ],
            [
                'district_id' => 7, 'district_name' => 'อำเภอสามโก้', 'org_id' => 56, 'org_name' => 'รพ.สต.มงคลธรรมนิมิต',
                'user_id' => 6, 'user_name' => 'นายสราวุฒิ มงคลธรรม', 'loc' => 'ต.มงคลธรรมนิมิต', 'v_no' => 6, 'v_name' => 'บ่อกลางเมือง',
                'people' => 4, 'mobile' => 1, 'mcatt' => 0, 'srrt' => 0, 'shert' => 0,
                'chronic_meds' => 2, 'home_visit' => 4, 'dispense' => 2, 'edu' => 4, 'treat' => 0, 'refer' => 0, 'other_serv' => 0,
                'screen' => 4, 'stress' => 0, 'depress' => 0, 'suicide' => 0, 'pfa' => 2, 'doc' => 0,
                'd_resp' => 0, 'd_gi' => 0, 'd_circ' => 0, 'd_eye' => 0, 'd_skin' => 0, 'd_foot' => 2, 'd_muscle' => 0, 'd_head' => 1, 'd_dengue' => 0, 'd_dia' => 0, 'd_fatigue' => 0, 'd_viral' => 0, 'd_other' => 0,
                'drown' => 0, 'shock' => 0,
                'r_relief' => 2, 'r_home' => 0, 'r_foot' => 2, 'r_anti' => 0, 'r_boots' => 0, 'r_repel' => 2, 'r_bag' => 4, 'r_alum' => 0, 'r_chlorine' => 0, 'r_em' => 0, 'r_other' => 0,
                'shelter_count' => 0, 'shelter_cap' => 0, 'shelter_occ' => 0, 'rep_name' => 'นายสราวุฒิ มงคลธรรม', 'rep_phone' => '089-1234567'
            ],
            [
                'district_id' => 6, 'district_name' => 'อำเภอวิเศษชัยชาญ', 'org_id' => 48, 'org_name' => 'รพ.สต.หัวตะพาน',
                'user_id' => 1, 'user_name' => 'ทีม MCATT สสจ.อ่างทอง', 'loc' => 'ต.หัวตะพาน', 'v_no' => 3, 'v_name' => 'บ้านหัวตะพาน',
                'people' => 320, 'mobile' => 2, 'mcatt' => 2, 'srrt' => 1, 'shert' => 0,
                'chronic_meds' => 40, 'home_visit' => 310, 'dispense' => 300, 'edu' => 300, 'treat' => 0, 'refer' => 0, 'other_serv' => 0,
                'screen' => 70, 'stress' => 0, 'depress' => 0, 'suicide' => 0, 'pfa' => 15, 'doc' => 0,
                'd_resp' => 0, 'd_gi' => 0, 'd_circ' => 0, 'd_eye' => 3, 'd_skin' => 22, 'd_foot' => 210, 'd_muscle' => 14, 'd_head' => 4, 'd_dengue' => 1, 'd_dia' => 0, 'd_fatigue' => 0, 'd_viral' => 0, 'd_other' => 0,
                'drown' => 0, 'shock' => 0,
                'r_relief' => 45, 'r_home' => 0, 'r_foot' => 150, 'r_anti' => 0, 'r_boots' => 0, 'r_repel' => 20, 'r_bag' => 25, 'r_alum' => 0, 'r_chlorine' => 0, 'r_em' => 0, 'r_other' => 0,
                'shelter_count' => 1, 'shelter_cap' => 150, 'shelter_occ' => 0, 'rep_name' => 'พยาบาลวิชาชีพ รพ.สต.หัวตะพาน', 'rep_phone' => '035-631234'
            ],
            [
                'district_id' => 3, 'district_name' => 'อำเภอป่าโมก', 'org_id' => 22, 'org_name' => 'โรงพยาบาลป่าโมก (รพช.)',
                'user_id' => 3, 'user_name' => 'น.ส.กนกวรรณ ป่าโมกข์', 'loc' => 'ต.โผงเผง', 'v_no' => 1, 'v_name' => 'หมู่ 1 โผงเผง',
                'people' => 264, 'mobile' => 2, 'mcatt' => 1, 'srrt' => 0, 'shert' => 0,
                'chronic_meds' => 23, 'home_visit' => 254, 'dispense' => 201, 'edu' => 249, 'treat' => 0, 'refer' => 0, 'other_serv' => 0,
                'screen' => 36, 'stress' => 0, 'depress' => 0, 'suicide' => 0, 'pfa' => 6, 'doc' => 0,
                'd_resp' => 0, 'd_gi' => 0, 'd_circ' => 0, 'd_eye' => 2, 'd_skin' => 11, 'd_foot' => 134, 'd_muscle' => 8, 'd_head' => 3, 'd_dengue' => 0, 'd_dia' => 0, 'd_fatigue' => 0, 'd_viral' => 0, 'd_other' => 0,
                'drown' => 0, 'shock' => 0,
                'r_relief' => 27, 'r_home' => 0, 'r_foot' => 88, 'r_anti' => 0, 'r_boots' => 0, 'r_repel' => 9, 'r_bag' => 15, 'r_alum' => 0, 'r_chlorine' => 0, 'r_em' => 0, 'r_other' => 0,
                'shelter_count' => 1, 'shelter_cap' => 150, 'shelter_occ' => 0, 'rep_name' => 'น.ส.กนกวรรณ ป่าโมกข์', 'rep_phone' => '035-661234'
            ]
        ];

        $stmtMedIns = $pdo->prepare("
            INSERT INTO flood_medical_services (
                report_date, district_id, district_name, organization_id, organization_name, user_id, user_name,
                outreach_location, village_no, village_name, people_served,
                team_mobile_clinic, team_mcatt, team_srrt, team_shert,
                service_chronic_meds, service_home_visit, service_med_dispense, service_health_edu, service_treatment, service_referral, service_other,
                mental_screened, mental_high_stress, mental_depression, mental_suicide_risk, mental_help_pfa, mental_help_doctor,
                disease_respiratory, disease_gastrointestinal, disease_circulatory, disease_eye_infection, disease_skin, disease_athletes_foot, disease_muscle_pain, disease_headache, disease_dengue, disease_diarrhea, disease_fatigue, disease_viral, disease_other,
                accident_drowning, accident_electrocution,
                resource_relief_kit, resource_home_medicine, resource_foot_cream, resource_antifungal, resource_boots, resource_mosquito_repellent, resource_garbage_bag, resource_alum, resource_chlorine, resource_em, resource_other,
                shelter_count, shelter_capacity, shelter_occupants, reporter_name, reporter_phone
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        foreach ($medSeeds as $m) {
            $stmtMedIns->execute([
                $todayDate, $m['district_id'], $m['district_name'], $m['org_id'], $m['org_name'], $m['user_id'], $m['user_name'],
                $m['loc'], $m['v_no'], $m['v_name'], $m['people'],
                $m['mobile'], $m['mcatt'], $m['srrt'], $m['shert'],
                $m['chronic_meds'], $m['home_visit'], $m['dispense'], $m['edu'], $m['treat'], $m['refer'], $m['other_serv'],
                $m['screen'], $m['stress'], $m['depress'], $m['suicide'], $m['pfa'], $m['doc'],
                $m['d_resp'], $m['d_gi'], $m['d_circ'], $m['d_eye'], $m['d_skin'], $m['d_foot'], $m['d_muscle'], $m['d_head'], $m['d_dengue'], $m['d_dia'], $m['d_fatigue'], $m['d_viral'], $m['d_other'],
                $m['drown'], $m['shock'],
                $m['r_relief'], $m['r_home'], $m['r_foot'], $m['r_anti'], $m['r_boots'], $m['r_repel'], $m['r_bag'], $m['r_alum'], $m['r_chlorine'], $m['r_em'], $m['r_other'],
                $m['shelter_count'], $m['shelter_cap'], $m['shelter_occ'], $m['rep_name'], $m['rep_phone']
            ]);
        }
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
}

// CSRF Helpers
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Audit Logger Helper (Now stores Who, Where, What, Report Date, and NOW())
function log_audit($pdo, $userId, $action, $details = null, $reportDate = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $username = $_SESSION['username'] ?? null;
        $fullname = $_SESSION['fullname'] ?? null;
        $districtName = $_SESSION['district_name'] ?? null;
        $orgName = $_SESSION['organization_name'] ?? null;

        // If not in session, lookup user
        if ($userId && (!$username || !$fullname)) {
            $u = $pdo->query("SELECT username, fullname, district_name, organization_name FROM users WHERE id = " . intval($userId))->fetch();
            if ($u) {
                $username = $u['username'];
                $fullname = $u['fullname'];
                $districtName = $u['district_name'];
                $orgName = $u['organization_name'];
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO `audit_logs` (
                `user_id`, `username`, `fullname`, `district_name`, `organization_name`, 
                `action`, `details`, `report_date`, `ip_address`, `created_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId, $username, $fullname, $districtName, $orgName,
            $action, $details, $reportDate, $ip
        ]);
    } catch (Exception $e) {
        // Silently continue if audit log fails
    }
}
