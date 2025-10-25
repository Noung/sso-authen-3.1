-- =====================================================
-- Migration: เพิ่มคอลัมน์ Extended Claims ลงใน admin_users
-- วันที่: 2025-10-22
-- จุดประสงค์: รองรับข้อมูลเพิ่มเติมจาก OIDC Providers (โดยเฉพาะ PSU SSO)
-- =====================================================

USE sso_authen;

-- เพิ่มคอลัมน์ใหม่สำหรับ Extended Claims
-- ใช้ AFTER updated_at เพราะเป็นคอลัมน์สุดท้ายที่มีอยู่
ALTER TABLE admin_users 
ADD COLUMN `position` VARCHAR(255) NULL COMMENT 'ตำแหน่งงาน (จาก position_th)' AFTER updated_at,
ADD COLUMN `campus` VARCHAR(255) NULL COMMENT 'วิทยาเขต (จาก campus_th)' AFTER `position`,
ADD COLUMN `office_name` VARCHAR(255) NULL COMMENT 'ชื่อสำนักงาน (จาก office_name_th)' AFTER `campus`,
ADD COLUMN `faculty_id` VARCHAR(50) NULL COMMENT 'รหัสคณะ' AFTER `office_name`,
ADD COLUMN `department_id` VARCHAR(50) NULL COMMENT 'รหัสภาควิชา/หน่วยงาน' AFTER `faculty_id`,
ADD COLUMN `campus_id` VARCHAR(50) NULL COMMENT 'รหัสวิทยาเขต' AFTER `department_id`,
ADD COLUMN `groups` TEXT NULL COMMENT 'กลุ่มผู้ใช้ (JSON array หรือ comma-separated)' AFTER `campus_id`,
ADD COLUMN `provider` VARCHAR(50) DEFAULT 'psu' COMMENT 'ผู้ให้บริการ OIDC (psu, google, auth0, etc.)' AFTER `groups`;

-- เพิ่ม INDEX สำหรับการค้นหา (ใน MySQL 5.7/8.0 ไม่มี IF NOT EXISTS สำหรับ INDEX)
-- ใช้ CREATE INDEX แทน
CREATE INDEX idx_faculty_id ON admin_users(faculty_id);
CREATE INDEX idx_department_id ON admin_users(department_id);
CREATE INDEX idx_campus_id ON admin_users(campus_id);
CREATE INDEX idx_provider ON admin_users(provider);

-- แสดงโครงสร้างตารางหลังอัปเดต
DESCRIBE admin_users;

SELECT 'Migration completed successfully!' AS Status;
