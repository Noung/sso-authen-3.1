<?php
/**
 * www/user_handler.php
 * เทมเพลตสำหรับจัดการข้อมูลผู้ใช้ในฐานข้อมูลของแอปพลิเคชัน ที่แต่ละแอปพลิเคชันต้องสร้างขึ้นเอง v.1
 */

/**
 * ค้นหาผู้ใช้จากฐานข้อมูลด้วยข้อมูลจาก SSO หากไม่พบจะสร้างผู้ใช้ใหม่
 *
 * @param array $normalizedUser ข้อมูลผู้ใช้ที่ผ่านการแปลงชื่อฟิลด์เป็นมาตรฐานแล้ว
 * @param object $ssoUserInfo ข้อมูลผู้ใช้ดิบที่ได้จาก PSU SSO
 * @return array ข้อมูลผู้ใช้จากฐานข้อมูลภายในของแอปพลิเคชัน (รวม role)
 */
function findOrCreateUser(array $normalizedUser, object $ssoUserInfo): array
{
    // 1. เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล
    require_once __DIR__ . '/db_config.php';

    // 2. ตั้งค่าการเชื่อมต่อ PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // 3. เชื่อมต่อฐานข้อมูล
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // 4. ค้นหาผู้ใช้จากอีเมล (หรือ user_id ถ้าต้องการ)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$normalizedUser['email']]);
        $user = $stmt->fetch();

        if ($user) {
            // --- กรณีที่ 1: พบผู้ใช้ในระบบ (สมาชิกเก่า) ---

            // อัปเดตข้อมูลล่าสุด (เผื่อมีการเปลี่ยนชื่อ-สกุล)
            $updateStmt = $pdo->prepare(
                "UPDATE users SET name = ?, user_id = ? WHERE id = ?"
            );
            $updateStmt->execute([
                $normalizedUser['name'],
                $normalizedUser['id'], // user_id
                $user['id']
            ]);

            // คืนค่าข้อมูลผู้ใช้จากฐานข้อมูลของเรา
            return $user;
        } else {
            // --- กรณีที่ 2: ไม่พบผู้ใช้ในระบบ (สมาชิกใหม่) ---

            // กำหนด Role เริ่มต้น
            $defaultRole = 'user';

            // เตรียมข้อมูลและสร้างผู้ใช้ใหม่
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (user_id, email, name, role) VALUES (?, ?, ?, ?)"
            );
            $insertStmt->execute([
                $normalizedUser['id'], // user_id
                $normalizedUser['email'],
                $normalizedUser['name'],
                $defaultRole
            ]);

            $newUserId = $pdo->lastInsertId();

            // คืนค่าข้อมูลผู้ใช้ใหม่ที่เพิ่งสร้าง
            return [
                'id' => $newUserId,
                'user_id' => $normalizedUser['id'],
                'email' => $normalizedUser['email'],
                'name' => $normalizedUser['name'],
                'role' => $defaultRole
            ];
        }
    } catch (\PDOException $e) {
        // หากมีปัญหาในการเชื่อมต่อหรือคิวรีฐานข้อมูล ให้หยุดการทำงานและแสดงข้อผิดพลาด
        // ในระบบจริง ควรจะบันทึก Log แทนการ die()
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}