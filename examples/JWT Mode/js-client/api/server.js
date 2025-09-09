// js-client-example/api/server.js

const express = require('express');
const cors = require('cors');
const app = express();
const port = 8080; // Port สำหรับ API Server

// --- การตั้งค่าที่ต้องทำใน Web Application ของคุณ ---
// Secret Key ที่ต้องตรงกับ 'api_secret_key' ที่ลงทะเบียนไว้ใน sso-authen
// ในระบบจริง ควรเก็บค่านี้ไว้ใน Environment Variable (.env)
const APP_API_SECRET_KEY = 'VERY_SECRET_KEY_FOR_JS_APP';
// --- สิ้นสุดการตั้งค่า ---

app.use(cors()); // อนุญาตให้ Frontend (ที่อยู่คนละ Port) เรียกใช้ได้
app.use(express.json()); // Middleware สำหรับอ่าน JSON body

/**
 * Endpoint นี้คือ user_handler_endpoint ที่จะให้ sso-authen เรียก
 */
app.post('/sso-user-handler', (req, res) => {
    console.log('Received request from sso-authen...');

    // 1. ตรวจสอบความปลอดภัยของ Request
    const receivedKey = req.headers['x-api-secret'];
    if (receivedKey !== APP_API_SECRET_KEY) {
        console.error('Unauthorized API Key!');
        return res.status(401).json({ error: 'Unauthorized' });
    }

    const { normalizedUser } = req.body;
    if (!normalizedUser) {
        return res.status(400).json({ error: 'Invalid payload' });
    }
    
    // 2. จัดการผู้ใช้ (ค้นหา/สร้าง/อัปเดต ใน DB ของคุณ)
    // ในตัวอย่างนี้ เราจะจำลองการทำงานโดยการเพิ่ม role เข้าไป
    console.log('User data received:', normalizedUser);
    const internalUser = {
        id: `app-user-${normalizedUser.id}`, // ID จากระบบของคุณ
        user_id: normalizedUser.id,
        email: normalizedUser.email,
        name: normalizedUser.name,
        role: 'user' // Role จากระบบของคุณ
    };

    // 3. ส่งข้อมูลผู้ใช้ในระบบของคุณกลับไป
    console.log('Returning internal user data:', internalUser);
    res.status(200).json(internalUser);
});

app.listen(port, () => {
    console.log(`JS App Backend listening at http://localhost:${port}`);
});