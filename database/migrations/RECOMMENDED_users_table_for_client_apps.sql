-- =====================================================
-- คำแนะนำ: Schema สำหรับตาราง users ของ Client Application
-- วันที่: 2025-10-22
-- =====================================================
-- 
-- ไฟล์นี้เป็นเทมเพลตแนะนำสำหรับนักพัฒนา Client Application
-- ที่ต้องการรับข้อมูลผู้ใช้จาก SSO-Authen ผ่าน User Handler API
--
-- คุณสามารถปรับแต่งตามความต้องการของแอปพลิเคชันได้
-- =====================================================

-- สร้างตาราง users สำหรับเก็บข้อมูลผู้ใช้ในแอปพลิเคชัน
CREATE TABLE IF NOT EXISTS users (
    -- Primary Key
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- ===== Basic User Information (Required) =====
    -- ข้อมูลพื้นฐานที่ทุก OIDC Provider ควรมี
    user_id VARCHAR(255) NULL COMMENT 'User ID จาก Provider (PSU ID, Google ID, etc.)',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'อีเมลผู้ใช้ (ใช้เป็น unique identifier)',
    name VARCHAR(255) NOT NULL COMMENT 'ชื่อ-นามสกุลเต็ม',
    first_name VARCHAR(255) NULL COMMENT 'ชื่อจริง',
    last_name VARCHAR(255) NULL COMMENT 'นามสกุล',
    username VARCHAR(255) NULL COMMENT 'ชื่อผู้ใช้',
    department VARCHAR(255) NULL COMMENT 'ภาควิชา/หน่วยงาน',
    
    -- ===== Extended User Information (Optional) =====
    -- ข้อมูลเพิ่มเติมที่จะมีค่าหรือเป็น NULL ขึ้นกับ Provider
    -- PSU SSO: มีครบทุกฟิลด์
    -- Google/Auth0/Microsoft: ส่วนใหญ่เป็น NULL
    position VARCHAR(255) NULL COMMENT 'ตำแหน่งงาน (PSU: position_th)',
    campus VARCHAR(255) NULL COMMENT 'วิทยาเขต (PSU: campus_th)',
    office_name VARCHAR(255) NULL COMMENT 'ชื่อสำนักงาน (PSU: office_name_th)',
    faculty_id VARCHAR(50) NULL COMMENT 'รหัสคณะ (PSU: faculty_id)',
    department_id VARCHAR(50) NULL COMMENT 'รหัสภาควิชา (PSU: department_id)',
    campus_id VARCHAR(50) NULL COMMENT 'รหัสวิทยาเขต (PSU: campus_id)',
    groups TEXT NULL COMMENT 'กลุ่มผู้ใช้ - เก็บเป็น JSON array หรือ comma-separated string',
    
    -- ===== Application-Specific Fields =====
    -- ฟิลด์เฉพาะของแอปพลิเคชันของคุณ
    role VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'บทบาทในระบบ (user, admin, editor, etc.)',
    status VARCHAR(20) DEFAULT 'active' COMMENT 'สถานะผู้ใช้ (active, inactive, suspended)',
    profile_image VARCHAR(500) NULL COMMENT 'URL รูปโปรไฟล์',
    last_login TIMESTAMP NULL COMMENT 'เวลา login ล่าสุด',
    login_count INT DEFAULT 0 COMMENT 'จำนวนครั้งที่ login',
    
    -- ===== Metadata =====
    provider VARCHAR(50) DEFAULT 'psu' COMMENT 'OIDC Provider ที่ใช้ล็อกอิน (psu, google, auth0, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้างบัญชี',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดตล่าสุด',
    
    -- ===== Indexes for Performance =====
    INDEX idx_email (email),
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_provider (provider),
    INDEX idx_faculty_id (faculty_id),
    INDEX idx_department_id (department_id),
    INDEX idx_campus_id (campus_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ตารางผู้ใช้สำหรับ Client Application - รองรับข้อมูลจาก SSO-Authen';

-- ===== ตัวอย่างการใช้งาน =====

-- 1. ค้นหาผู้ใช้จาก PSU SSO (มีข้อมูลครบ)
-- SELECT * FROM users WHERE email = 'john.d@psu.ac.th';

-- 2. ค้นหาผู้ใช้จาก Google (extended fields เป็น NULL)
-- SELECT * FROM users WHERE provider = 'google' AND email = 'user@gmail.com';

-- 3. ค้นหาผู้ใช้ตามคณะ (เฉพาะ PSU)
-- SELECT * FROM users WHERE faculty_id = 'F01' AND provider = 'psu';

-- 4. ค้นหาผู้ใช้ที่มีบทบาท admin
-- SELECT * FROM users WHERE role = 'admin' AND status = 'active';

-- ===== หมายเหตุสำหรับนักพัฒนา =====
-- 
-- 1. การจัดการ Extended Fields ที่เป็น NULL:
--    - ใช้ COALESCE() หรือ IFNULL() เมื่อแสดงผลใน UI
--    - ตัวอย่าง: COALESCE(position, 'ไม่ระบุ') AS position_display
--
-- 2. การจัดเก็บ Groups:
--    - ถ้าเป็น array: เก็บเป็น JSON string -> JSON_ENCODE() ใน PHP
--    - ถ้าเป็น comma-separated: เก็บเป็น TEXT
--    - ค้นหา: ใช้ JSON_CONTAINS() หรือ FIND_IN_SET()
--
-- 3. Security Best Practices:
--    - อย่าเปิดเผย user_id จาก provider ใน URL
--    - ใช้ internal id (auto-increment) สำหรับ references
--    - เข้ารหัส sensitive data ถ้าจำเป็น
--
-- 4. Performance:
--    - เพิ่ม INDEX ตามการใช้งานจริงของคุณ
--    - พิจารณาใช้ ENUM สำหรับ role และ status ถ้ามีค่าคงที่
--
-- =====================================================

SELECT 'Schema template created successfully!' AS Status,
       'This is a RECOMMENDED schema for client applications' AS Note,
       'Customize it according to your application needs' AS Reminder;
