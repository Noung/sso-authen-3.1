<?php
/**
 * คู่มือการแก้ปัญหา Legacy Mode แบบข้ามโดเมน
 * วิธีแปลง user_handler.php local เป็น HTTP endpoint
 */

echo "<h1>คู่มือแก้ปัญหา Legacy Mode ข้ามโดเมน</h1>";

echo "<h2>ข้อจำกัดปัจจุบัน</h2>";
echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>❌ ใช้งานไม่ได้:</strong> โดเมนต่างกันพร้อม local file path<br>";
echo "sso-authen.com ไม่สามารถเข้าถึงไฟล์ใน legacy-app.com ผ่าน require_once<br>";
echo "</div>";

echo "<h2>✅ วิธีแก้: แปลงเป็น HTTP Endpoint</h2>";

echo "<h3>ขั้นตอนที่ 1: สำรองไฟล์เดิม</h3>";
echo "<pre>";
echo "# ในโดเมนของแอป legacy
cp api/user_handler.php api/user_handler_original.php
";
echo "</pre>";

echo "<h3>ขั้นตอนที่ 2: สร้าง HTTP Wrapper</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<strong>สร้างไฟล์ api/user_handler.php ใหม่:</strong><br>";
echo "<code>";
echo htmlspecialchars("<?php
// รับ HTTP requests
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจาก sso-authen
    \$input = file_get_contents('php://input');
    \$data = json_decode(\$input, true);
    
    // โหลดโค้ดเดิม
    require_once __DIR__ . '/user_handler_original.php';
    
    // เรียกใช้ฟังก์ชันเดิม
    \$result = findOrCreateUser(\$data['normalizedUser'], \$data['ssoUserInfo']);
    
    // ส่งคืนแบบ JSON
    header('Content-Type: application/json');
    echo json_encode(\$result);
}");
echo "</code>";
echo "</div>";

echo "<h3>ขั้นตอนที่ 3: อัพเดตการตั้งค่า SSO</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<strong>ใน sso-authen admin panel ตั้งค่า:</strong><br>";
echo "<code>user_handler_endpoint: http://legacy-app.com/api/user_handler.php</code><br>";
echo "<code>api_secret_key: YOUR_SECRET_KEY</code>";
echo "</div>";

echo "<h3>ขั้นตอนที่ 4: ทดสอบการเชื่อมต่อ</h3>";
echo "<pre>";
echo "# ทดสอบ endpoint ด้วยตนเอง:
curl -X POST http://legacy-app.com/api/user_handler.php \\
  -H 'Content-Type: application/json' \\
  -H 'X-API-SECRET: YOUR_SECRET_KEY' \\
  -d '{\"normalizedUser\": {\"id\":\"test\", \"email\":\"test@example.com\", \"name\":\"Test User\"}, \"ssoUserInfo\": {}}'
";
echo "</pre>";

echo "<h2>🎯 ข้อดีของการใช้ HTTP Endpoint</h2>";
echo "<ul>";
echo "<li>✅ ใช้งานได้ข้ามโดเมนต่างกัน</li>";
echo "<li>✅ คงโค้ด user handler เดิมไว้</li>";
echo "<li>✅ เพิ่มความปลอดภัยด้วย secret key</li>";
echo "<li>✅ ไม่ต้องแก้ไข sso-authen core</li>";
echo "<li>✅ ยังคงสร้าง session ในแอป legacy ได้</li>";
echo "</ul>";

echo "<h2>⚠️ ทางเลือก: Deploy ในโดเมนเดียวกัน</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<strong>หากต้องการ Legacy Mode แบบแท้จริง:</strong><br>";
echo "Deploy ทั้ง sso-authen และ legacy app ในโดเมน/เซิร์ฟเวอร์เดียวกัน<br>";
echo "ตัวอย่าง: <code>mycompany.com/sso/</code> และ <code>mycompany.com/legacy-app/</code>";
echo "</div>";

echo "<h2>📝 สรุป</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<strong>Legacy Mode ข้ามโดเมน:</strong><br>";
echo "❌ Local file path = ใช้งานไม่ได้<br>";
echo "✅ HTTP endpoint = ใช้งานได้ (แนะนำ)<br>";
echo "✅ Same domain = ใช้งานได้<br>";
echo "✅ Shared file system = ใช้งานได้<br>";
echo "</div>";
?>