<?php
/**
 * Legacy Mode Domain Requirement Guide
 * คู่มือข้อกำหนดโดเมนสำหรับ Legacy Mode
 */

echo "<h1>คู่มือข้อกำหนด Legacy Mode</h1>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
echo "<h2 style='color: #856404; margin-top: 0;'>⚠️ ข้อจำกัดสำคัญของ Legacy Mode</h2>";
echo "<p><strong>Legacy Mode รองรับเฉพาะแอปพลิเคชัน PHP ที่อยู่ในโดเมนเดียวกันกับ SSO Gateway เท่านั้น</strong></p>";
echo "</div>";

echo "<h2>✅ สถานการณ์ที่ใช้งานได้</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Same Domain/Server:</h3>";
echo "<code>";
echo "SSO Gateway: http://192.168.159.14/sso-authen-3/<br>";
echo "PHP App:     http://192.168.159.14/my-app/<br>";
echo "✅ Legacy Mode ใช้งานได้";
echo "</code>";
echo "</div>";

echo "<h2>❌ สถานการณ์ที่ใช้งานไม่ได้</h2>";
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Different Domain/Server:</h3>";
echo "<code>";
echo "SSO Gateway: http://192.168.159.14/sso-authen-3/<br>";
echo "PHP App:     http://192.168.159.15/my-app/<br>";
echo "❌ Legacy Mode ใช้งานไม่ได้";
echo "</code>";
echo "</div>";

echo "<h2>🔧 วิธีการตั้งค่า Legacy Mode</h2>";

echo "<h3>1. ข้อกำหนดเบื้องต้น</h3>";
echo "<ul>";
echo "<li>แอป PHP ต้องติดตั้งอยู่ในเซิร์ฟเวอร์เดียวกันกับ SSO Gateway</li>";
echo "<li>สร้างไฟล์ <code>user_handler.php</code> ตามตัวอย่างใน <code>examples/Legacy Mode/</code></li>";
echo "<li>ตั้งค่า database connection ในแอป PHP</li>";
echo "</ul>";

echo "<h3>2. การตั้งค่าใน Admin Panel</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<strong>เมื่อสร้าง Client ใหม่:</strong><br>";
echo "1. เลือก <strong>Legacy Mode</strong><br>";
echo "2. ใส่ path ของ user_handler.php เช่น: <code>/my-app/api/user_handler.php</code><br>";
echo "3. เว้น API Secret Key ว่างไว้<br>";
echo "4. Status เป็น Active";
echo "</div>";

echo "<h3>3. ตัวอย่าง user_handler.php</h3>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo htmlspecialchars('<?php
/**
 * user_handler.php สำหรับ Legacy Mode
 * ไฟล์นี้ต้องอยู่ในแอป PHP ที่โดเมนเดียวกันกับ SSO Gateway
 */

function findOrCreateUser(array $normalizedUser, object $ssoUserInfo): array
{
    // เชื่อมต่อ database ของแอป
    require_once __DIR__ . \'/db_config.php\';
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // ค้นหาหรือสร้างผู้ใช้
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$normalizedUser[\'email\']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // สร้างผู้ใช้ใหม่
        $stmt = $pdo->prepare("INSERT INTO users (user_id, email, name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $normalizedUser[\'id\'],
            $normalizedUser[\'email\'],
            $normalizedUser[\'name\'],
            \'user\'
        ]);
        
        $user = [
            \'id\' => $pdo->lastInsertId(),
            \'user_id\' => $normalizedUser[\'id\'],
            \'email\' => $normalizedUser[\'email\'],
            \'name\' => $normalizedUser[\'name\'],
            \'role\' => \'user\'
        ];
    }
    
    return $user;
}');
echo "</pre>";

echo "<h2>🚨 แนวทางสำหรับแอป Cross-Domain</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>หากแอป PHP อยู่คนละโดเมน:</strong><br>";
echo "1. <strong>ไม่สามารถใช้ Legacy Mode ได้</strong><br>";
echo "2. ต้องเปลี่ยนเป็น <strong>JWT Mode</strong><br>";
echo "3. แปลง user_handler.php เป็น HTTP endpoint<br>";
echo "4. ใช้ cURL แทน require_once";
echo "</div>";

echo "<h2>📊 เปรียบเทียบ Authentication Modes</h2>";
echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='border: 1px solid #dee2e6; padding: 12px;'>Feature</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 12px;'>Legacy Mode</th>";
echo "<th style='border: 1px solid #dee2e6; padding: 12px;'>JWT Mode</th>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'><strong>Domain Requirement</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px; background: #fff3cd;'>⚠️ Same domain only</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px; background: #d4edda;'>✅ Cross-domain support</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'><strong>Communication</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'>require_once file</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'>HTTP API call</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'><strong>Session Handling</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px; background: #d4edda;'>✅ Automatic \$_SESSION</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'>Manual JWT handling</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'><strong>Security</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'>File-based (same server)</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px; background: #d4edda;'>✅ API Secret Key</td>";
echo "</tr>";
echo "<tr>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'><strong>Performance</strong></td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px; background: #d4edda;'>✅ Fast (no HTTP)</td>";
echo "<td style='border: 1px solid #dee2e6; padding: 12px;'>HTTP overhead</td>";
echo "</tr>";
echo "</table>";

echo "<h2>🎯 คำแนะนำการเลือกใช้</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<h3>ใช้ Legacy Mode เมื่อ:</h3>";
echo "<ul>";
echo "<li>✅ แอป PHP อยู่ในเซิร์ฟเวอร์เดียวกันกับ SSO Gateway</li>";
echo "<li>✅ ต้องการ Session-based authentication แบบดั้งเดิม</li>";
echo "<li>✅ ไม่ต้องการปรับปรุงโค้ดเดิมมาก</li>";
echo "</ul>";

echo "<h3>ใช้ JWT Mode เมื่อ:</h3>";
echo "<ul>";
echo "<li>✅ แอปอยู่คนละเซิร์ฟเวอร์</li>";
echo "<li>✅ ต้องการ Stateless authentication</li>";
echo "<li>✅ แอปไม่ใช่ PHP (React, Node.js, etc.)</li>";
echo "<li>✅ ต้องการความปลอดภัยสูง</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>💡 หมายเหตุ:</strong> ข้อจำกัดนี้เป็นลักษณะเฉพาะของ Legacy Mode ที่ออกแบบมาเพื่อรองรับแอป PHP เก่าที่ต้องการการเปลี่ยนแปลงน้อยที่สุด หากต้องการความยืดหยุ่นมากขึ้น แนะนำให้ใช้ JWT Mode";
echo "</div>";
?>