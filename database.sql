-- Database SQL Dump for Provincial Health & Flood Situation Management System
-- จังหวัดอ่างทอง (Ang Thong Provincial Health Flood Reporting System)
-- Database: water_db

CREATE DATABASE IF NOT EXISTS `water_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `water_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ========================================================
-- 1. Districts Table (ตารางอำเภอในจังหวัดอ่างทอง 7 อำเภอ)
-- ========================================================
DROP TABLE IF EXISTS `districts`;
CREATE TABLE `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_th` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 2. Organizations Table (หน่วยงานสาธารณสุข: รพ.สต., สสอ., โรงพยาบาล, สสจ.)
-- ========================================================
DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `name_th` VARCHAR(255) NOT NULL,
    `type` ENUM('hospital', 'health_office', 'health_center', 'provincial_office') NOT NULL DEFAULT 'health_center',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_district` (`district_id`),
    CONSTRAINT `fk_org_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 3. Users Table (ผู้ใช้งานและระบบอนุมัติ Superadmin)
-- ========================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
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

-- ========================================================
-- 4. Flood Reports Table (รายงานสถานการณ์อุทกภัยประจำวันระดับหน่วยงาน)
-- ========================================================
DROP TABLE IF EXISTS `flood_reports`;
CREATE TABLE `flood_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `organization_name` VARCHAR(255) NOT NULL,
    `district_id` INT NOT NULL,
    `district_name` VARCHAR(100) NOT NULL,
    `user_id` INT NOT NULL,
    `user_name` VARCHAR(150) NOT NULL,
    `report_date` DATE NOT NULL,

    -- สถานะสถานบริการ & ผลกระทบ
    `impact_status` ENUM('normal', 'affected') NOT NULL DEFAULT 'normal',
    `service_status` ENUM('normal', 'partial', 'closed') NOT NULL DEFAULT 'normal',
    `impact_details` TEXT DEFAULT NULL,
    `substitute_location` VARCHAR(255) DEFAULT NULL,

    -- ยอดรวม 8 กลุ่มเปราะบาง (Rollup Sums จากรายหมู่บ้าน)
    -- ล็อก 1: ผู้ป่วยติดเตียง
    `bedridden_total` INT DEFAULT 0,
    `bedridden_flooded` INT DEFAULT 0,
    `bedridden_home` INT DEFAULT 0,
    `bedridden_shelter` INT DEFAULT 0,
    `bedridden_hospital` INT DEFAULT 0,

    -- ล็อก 2: ผู้ป่วยฟอกไต
    `dialysis_total` INT DEFAULT 0,
    `dialysis_flooded` INT DEFAULT 0,
    `dialysis_home` INT DEFAULT 0,
    `dialysis_shelter` INT DEFAULT 0,
    `dialysis_missed` INT DEFAULT 0,

    -- ล็อก 3: ผู้ป่วยจิตเวชรับยา
    `psychiatric_total` INT DEFAULT 0,
    `psychiatric_flooded` INT DEFAULT 0,
    `psychiatric_home` INT DEFAULT 0,
    `psychiatric_shelter` INT DEFAULT 0,
    `psychiatric_health_issue` INT DEFAULT 0,

    -- ล็อก 4: ผู้สูงอายุ 60 ปีขึ้นไป
    `elderly_total` INT DEFAULT 0,
    `elderly_flooded` INT DEFAULT 0,
    `elderly_home` INT DEFAULT 0,
    `elderly_shelter` INT DEFAULT 0,
    `elderly_out_of_meds` INT DEFAULT 0,

    -- ล็อก 5: ผู้พิการ
    `disabled_total` INT DEFAULT 0,
    `disabled_flooded` INT DEFAULT 0,
    `disabled_home` INT DEFAULT 0,
    `disabled_shelter` INT DEFAULT 0,
    `disabled_health_issue` INT DEFAULT 0,

    -- ล็อก 6: โรคเรื้อรัง (NCD)
    `ncd_total` INT DEFAULT 0,
    `ncd_flooded` INT DEFAULT 0,
    `ncd_home` INT DEFAULT 0,
    `ncd_shelter` INT DEFAULT 0,
    `ncd_out_of_meds` INT DEFAULT 0,

    -- ล็อก 7: หญิงตั้งครรภ์
    `pregnant_total` INT DEFAULT 0,
    `pregnant_flooded` INT DEFAULT 0,
    `pregnant_home` INT DEFAULT 0,
    `pregnant_shelter` INT DEFAULT 0,
    `pregnant_health_issue` INT DEFAULT 0,

    -- ล็อก 8: เด็กอายุ 0-5 ปี
    `children_total` INT DEFAULT 0,
    `children_flooded` INT DEFAULT 0,
    `children_home` INT DEFAULT 0,
    `children_shelter` INT DEFAULT 0,
    `children_health_issue` INT DEFAULT 0,

    -- 3. ทีมปฏิบัติการ & บริการแพทย์เคลื่อนที่
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

-- ========================================================
-- 5. Flood Report Villages Table (การบันทึกจำแนกรายหมู่บ้าน แยกตามล็อกหัวข้อ)
-- ========================================================
DROP TABLE IF EXISTS `flood_report_villages`;
CREATE TABLE `flood_report_villages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `report_date` DATE NOT NULL,
    `organization_id` INT NOT NULL,
    `subdistrict` VARCHAR(100) NOT NULL,
    `village_no` INT NOT NULL,
    `village_name` VARCHAR(150) DEFAULT NULL,

    -- ล็อก 1: ผู้ป่วยติดเตียง
    `bedridden_total` INT DEFAULT 0,
    `bedridden_flooded` INT DEFAULT 0,
    `bedridden_home` INT DEFAULT 0,
    `bedridden_shelter` INT DEFAULT 0,
    `bedridden_hospital` INT DEFAULT 0,

    -- ล็อก 2: ผู้ป่วยฟอกไต
    `dialysis_total` INT DEFAULT 0,
    `dialysis_flooded` INT DEFAULT 0,
    `dialysis_home` INT DEFAULT 0,
    `dialysis_shelter` INT DEFAULT 0,
    `dialysis_missed` INT DEFAULT 0,

    -- ล็อก 3: จิตเวช (รับยา)
    `psychiatric_total` INT DEFAULT 0,
    `psychiatric_flooded` INT DEFAULT 0,
    `psychiatric_home` INT DEFAULT 0,
    `psychiatric_shelter` INT DEFAULT 0,
    `psychiatric_health_issue` INT DEFAULT 0,

    -- ล็อก 4: ผู้สูงอายุ 60 ปีขึ้นไป
    `elderly_total` INT DEFAULT 0,
    `elderly_flooded` INT DEFAULT 0,
    `elderly_home` INT DEFAULT 0,
    `elderly_shelter` INT DEFAULT 0,
    `elderly_out_of_meds` INT DEFAULT 0,

    -- ล็อก 5: ผู้พิการ
    `disabled_total` INT DEFAULT 0,
    `disabled_flooded` INT DEFAULT 0,
    `disabled_home` INT DEFAULT 0,
    `disabled_shelter` INT DEFAULT 0,
    `disabled_health_issue` INT DEFAULT 0,

    -- ล็อก 6: โรคเรื้อรัง (NCD)
    `ncd_total` INT DEFAULT 0,
    `ncd_flooded` INT DEFAULT 0,
    `ncd_home` INT DEFAULT 0,
    `ncd_shelter` INT DEFAULT 0,
    `ncd_out_of_meds` INT DEFAULT 0,

    -- ล็อก 7: หญิงตั้งครรภ์
    `pregnant_total` INT DEFAULT 0,
    `pregnant_flooded` INT DEFAULT 0,
    `pregnant_home` INT DEFAULT 0,
    `pregnant_shelter` INT DEFAULT 0,
    `pregnant_health_issue` INT DEFAULT 0,

    -- ล็อก 8: เด็ก 0-5 ปี
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

-- ========================================================
-- 6. Audit Logs Table (บันทึกประวัติการปฏิบัติงาน Who, Where, What, NOW())
-- ========================================================
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
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

-- ========================================================
-- 7. Medical & Public Health Outreach Services Table (ส่วนที่ 3 ตามแบบฟอร์มไฟล์ราชการ)
-- ========================================================
DROP TABLE IF EXISTS `flood_medical_services`;
CREATE TABLE `flood_medical_services` (
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

-- ========================================================
-- 8. Provincial Flood Situation Parameters (สำหรับ OnePage Dashboard)
-- ========================================================
DROP TABLE IF EXISTS `flood_provincial_situation`;
CREATE TABLE `flood_provincial_situation` (
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

-- ========================================================
-- 9. District Flood Affected Stats (สำหรับตารางพื้นที่และแผนที่ OnePage)
-- ========================================================
DROP TABLE IF EXISTS `district_flood_stats`;
CREATE TABLE `district_flood_stats` (
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

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- Seed Initial Demo Accounts (Password: admin1234 / user1234)
-- ========================================================
INSERT INTO `users` (`username`, `password`, `fullname`, `phone`, `district_id`, `district_name`, `organization_id`, `organization_name`, `role`, `status`, `approved_by`, `approved_at`) VALUES
('superadmin', '$2y$10$w3qE3T7Jd4gW9PjC1E6u8.0uR0hYqA2n7S9wX4v9mD1jK2l3n4o5a', 'ผู้ดูแลระบบสูงสุด (Super Admin)', '035-611234', 1, 'อำเภอเมืองอ่างทอง', 1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)', 'superadmin', 'approved', 1, NOW()),
('admin.angthong', '$2y$10$w3qE3T7Jd4gW9PjC1E6u8.0uR0hYqA2n7S9wX4v9mD1jK2l3n4o5a', 'นายธีรภัทร ชาญวิชัย (Admin จังหวัด)', '081-9988776', 1, 'อำเภอเมืองอ่างทอง', 1, 'สำนักงานสาธารณสุขจังหวัดอ่างทอง (สสจ.อ่างทอง)', 'admin', 'approved', 1, NOW()),
('sarawut.m', '$2y$10$tJ9fA.1w0n7m6Q5r4S3t2u1v0w9x8y7z6a5b4c3d2e1f0g1h2i3j4', 'นายสราวุฒิ มงคลธรรม (จนท. รพ.สต.)', '089-1234567', 7, 'อำเภอสามโก้', 56, 'รพ.สต.มงคลธรรมนิมิต', 'user', 'approved', 1, NOW()),
('somchai.s', '$2y$10$tJ9fA.1w0n7m6Q5r4S3t2u1v0w9x8y7z6a5b4c3d2e1f0g1h2i3j4', 'นายสมชาย สุขเกษม (รออนุมัติ)', '089-7654321', 7, 'อำเภอสามโก้', 55, 'รพ.สต.บ้านสามโก้', 'user', 'pending', NULL, NULL);

-- ========================================================
-- Seed Initial OnePage Provincial Data (Matching reference infographic)
-- ========================================================
INSERT INTO `flood_provincial_situation` (`report_date`, `rain_situation`, `water_discharge`, `shelter_total_sites`, `shelter_capacity`, `shelter_occupants`, `deaths`, `injuries`, `missing`, `data_source`) VALUES
('2026-10-01', 'ไม่มีฝนตกในพื้นที่จังหวัดอ่างทอง', '8.62 (+0.47) (จุดหน้าศาลากลาง)', 2, 300, 0, 0, 0, 0, 'ข้อมูลสถานการณ์อุทกภัยในพื้นที่จังหวัดอ่างทอง สำนักงาน ปภ.จังหวัดอ่างทอง')
ON DUPLICATE KEY UPDATE `rain_situation`=VALUES(`rain_situation`);

INSERT INTO `district_flood_stats` (`report_date`, `district_id`, `district_name`, `subdistricts_affected`, `villages_affected`, `communities_affected`, `households_affected`, `is_flooded`) VALUES
('2026-10-01', 1, 'อำเภอเมืองอ่างทอง', 9, 23, 14, 285, 1),
('2026-10-01', 3, 'อำเภอป่าโมก', 3, 11, 0, 248, 1),
('2026-10-01', 2, 'อำเภอไชโย', 6, 17, 0, 58, 1),
('2026-10-01', 6, 'อำเภอวิเศษชัยชาญ', 10, 61, 0, 1081, 1),
('2026-10-01', 7, 'อำเภอสามโก้', 5, 34, 0, 337, 1),
('2026-10-01', 4, 'อำเภอโพธิ์ทอง', 0, 0, 0, 0, 0),
('2026-10-01', 5, 'อำเภอแสวงหา', 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE `households_affected`=VALUES(`households_affected`);

