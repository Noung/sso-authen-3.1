// js-client-example/public/app.js

// --- การตั้งค่าสำหรับ Frontend ---
const SSO_SERVER_URL = 'http://auth.my-organization.com/public'; // URL ของ sso-authen
const MY_APP_BASE_URI = 'http://localhost:5500/public'; // URL ของ Frontend App (ขึ้นอยู่กับ Live Server)
const MY_CLIENT_ID = 'my_js_app'; // Client ID ที่ลงทะเบียนไว้

// สร้าง URL สำหรับ Login/Logout
const loginUrl = `${SSO_SERVER_URL}/login.php?client_id=${MY_CLIENT_ID}&redirect_uri=${encodeURIComponent(`${MY_APP_BASE_URI}/callback.html`)}`;
const logoutUrl = `${SSO_SERVER_URL}/logout.php?post_logout_redirect_uri=${encodeURIComponent(`${MY_APP_BASE_URI}/index.html`)}`;
// --- สิ้นสุดการตั้งค่า ---

// ฟังก์ชันหลักที่จะทำงานเมื่อหน้าเว็บโหลดเสร็จ
window.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path.endsWith('callback.html')) {
        handleCallback();
    } else if (path.endsWith('index.html') || path === '/') {
        setupIndexPage();
    } else if (path.endsWith('userinfo.html')) {
        setupUserInfoPage();
    }
});

// จัดการหน้า callback
function handleCallback() {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');

    if (token) {
        localStorage.setItem('jwt_token', token); // เก็บ Token ลงใน localStorage
        window.location.href = 'index.html'; // ส่งกลับไปหน้าหลัก
    } else {
        document.body.innerHTML = '<h3>Error: Login failed, no token received.</h3>';
    }
}

// ตั้งค่าหน้า index
function setupIndexPage() {
    const token = localStorage.getItem('jwt_token');

    if (token) {
        // ถ้ามี Token (Login แล้ว)
        document.getElementById('logged-out-view').style.display = 'none';
        document.getElementById('logged-in-view').style.display = 'block';
        
        // ถอดรหัส Token เพื่อแสดงข้อมูล (แบบง่ายๆ ไม่มีการ verify)
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            document.getElementById('user-info').textContent = JSON.stringify(payload.data, null, 2);
        } catch (e) {
            document.getElementById('user-info').textContent = 'Cannot decode token.';
        }

        document.getElementById('logout-button').href = logoutUrl;

    } else {
        // ถ้าไม่มี Token (ยังไม่ Login)
        document.getElementById('login-button').href = loginUrl;
    }
}

// ตั้งค่าหน้า userinfo
function setupUserInfoPage() {
    const token = localStorage.getItem('jwt_token');

    if (!token) {
        // ถ้าไม่มี Token ให้ไล่กลับไปหน้าแรก
        alert('กรุณาเข้าสู่ระบบก่อน');
        window.location.href = 'index.html';
        return;
    }
    
    // **ตัวอย่างการเรียก API ที่ต้องยืนยันตัวตน**
    // ในระบบจริง Backend ควรจะมีการตรวจสอบ JWT Token นี้ด้วย
    document.getElementById('user-data').innerHTML = `
        <h5>ข้อมูลนี้ดึงมาจาก Token ที่ Frontend เก็บไว้:</h5>
        <pre class="p-3 bg-dark text-white rounded">${JSON.stringify(JSON.parse(atob(token.split('.')[1])).data, null, 2)}</pre>
    `;
}