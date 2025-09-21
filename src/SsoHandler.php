<?php
/**
 * src/SsoHandler.php
 * คลาสที่ทำหน้าที่จัดการกระบวนการ OIDC ทั้งหมด
 */

// การใช้ namespace เปรียบเสมือนการสร้าง "นามสกุล" ให้กับคลาสของเรา
// เพื่อป้องกันการตั้งชื่อซ้ำกับไลบรารีอื่น
namespace SsoAuthen;

// "use" คือการบอกว่าเราจะเรียกใช้คลาสจากไลบรารีภายนอก
use Jumbojett\OpenIDConnectClient;
use Exception;

class SsoHandler {

    // --- Properties: ตัวแปรที่ใช้เก็บของภายในคลาส ---

    /**
     * @var OpenIDConnectClient object
     * ตัวแปรสำหรับเก็บ client ที่ใช้สื่อสารกับ PSU SSO
     */
    private $oidc;

    /**
     * @var array
     * ตัวแปรสำหรับเก็บค่าคอนฟิกของ Provider ที่ใช้งานอยู่
     */
    private $config;

    // --- Constructor: "พิมพ์เขียว" ที่จะถูกเรียกใช้เมื่อมีการสร้าง Object ---

    /**
     * Constructor จะถูกเรียกอัตโนมัติเมื่อมีการสร้าง SsoHandler ใหม่
     * @param array $config ค่าคอนฟิกของ Provider ที่เราโหลดมาจาก config/config.php
     */
    public function __construct(array $config) {
        $this->config = $config; // เก็บค่าคอนฟิกไว้ใช้ในเมธอดอื่น

        // สร้าง OIDC client เตรียมไว้
        $this->oidc = new OpenIDConnectClient(
            $this->config['providerURL'],
            $this->config['clientID'],
            $this->config['clientSecret']
        );
    }

    // --- Methods: "ความสามารถ" ที่คลาสนี้ทำได้ ---

    /**
     * เมธอดสำหรับเริ่มต้นกระบวนการล็อกอิน (ส่งผู้ใช้ไปที่ PSU SSO)
     */
    public function login() {
        $this->oidc->setRedirectURL($this->config['redirectUri']);
        $this->oidc->addScope($this->config['scopes']);
        $this->oidc->authenticate();
    }

    /**
     * เมธอดสำหรับจัดการ Callback, ตรวจสอบ Token, และดึงข้อมูลผู้ใช้
     * @param array $clientConfig ข้อมูล client configuration จาก database
     * @return array ข้อมูลผู้ใช้จากระบบภายในหลังจากผ่านการตรวจสอบแล้ว
     * @throws Exception หากกระบวนการล้มเหลว
     */
    public function handleCallback(array $clientConfig = []): array {
        $this->oidc->setRedirectURL($this->config['redirectUri']);

        // 1. ตรวจสอบ Token และ State
        if (!$this->oidc->authenticate()) {
            throw new Exception('การยืนยันตัวตนล้มเหลว (State Mismatch หรือ Token ไม่ถูกต้อง)');
        }

        // 2. ดึงข้อมูลดิบจาก SSO
        $ssoUserInfo = $this->oidc->requestUserInfo();

        // --- เพิ่มโค้ดดีบักชั่วคราว ---
        // echo "<pre style='background-color: #f5f5f5; padding: 15px; border: 1px solid #ccc;'>";
        // echo "<strong>Raw User Info (Claims) from Provider:</strong>\n";
        // print_r($ssoUserInfo);
        // echo "</pre>";
        // die("--- End of Debug ---"); // หยุดการทำงานเพื่อดูผลลัพธ์
        // ----------------------------

        // 3. แปลงข้อมูลดิบให้เป็นรูปแบบมาตรฐานของเรา
        $normalizedUser = $this->normalizeClaims($ssoUserInfo);

        // // 4. เรียกใช้ฟังก์ชัน User Handler (user_handler.php) จากแอปพลิเคชัน
        // if (!function_exists('findOrCreateUser')) {
        //     throw new Exception('Application must implement findOrCreateUser() function.');
        // }
        // $internalUser = findOrCreateUser($normalizedUser, $ssoUserInfo);

        // -- v.3 (ปรับปรุง) --
        if (!empty($clientConfig['user_handler_endpoint'])) {
            // --- โหมด API (สำหรับ V2 API,V3 JWT) ---
            $internalUser = $this->callUserHandlerApi($normalizedUser, $ssoUserInfo, $clientConfig);
            return $internalUser; // จบการทำงานและส่งค่ากลับไปให้ callback.php สร้าง JWT
        } else {
            // --- โหมด Legacy (สำหรับ V1 Session) ---
            
            // ตรวจสอบว่ามี user_handler_endpoint ที่กำหนดไว้สำหรับ Legacy Mode ไหม
            $userHandlerPath = null;
            if (!empty($clientConfig['user_handler_endpoint'])) {
                // ถ้ามี endpoint และเป็น local file path
                if (strpos($clientConfig['user_handler_endpoint'], 'http') !== 0) {
                    // เป็น local path เช่น /api/user_handler.php
                    $userHandlerPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($clientConfig['user_handler_endpoint'], '/');
                }
            }
            
            // Legacy Mode requires explicit path - no fallback
            if (!$userHandlerPath) {
                throw new \Exception("Legacy Mode requires explicit User Handler File Path. Please configure user_handler_endpoint in client settings.");
            }
            
            if (!file_exists($userHandlerPath)) {
                throw new \Exception("ไม่พบไฟล์ user_handler.php ที่: {$userHandlerPath} โปรดสร้างไฟล์นี้ตามเทมเพลต");
            }
            require_once $userHandlerPath;

            if (!function_exists('findOrCreateUser')) {
                throw new \Exception("ไม่พบฟังก์ชัน findOrCreateUser() ในไฟล์ user_handler.php");
            }

            $internalUser = findOrCreateUser($normalizedUser, $ssoUserInfo);

            // สร้าง Session เฉพาะในโหมดนี้เท่านั้น
            $_SESSION['user_is_logged_in'] = true;
            $_SESSION['user_info'] = $internalUser;

            return $internalUser;
        }
    }

    /**
     * (เมธอดใหม่) เรียกไปยัง User Handler API Endpoint
     *
     * @param array $normalizedUser ข้อมูลผู้ใช้ที่แปลงแล้ว
     * @param object $ssoUserInfo ข้อมูลดิบจาก SSO
     * @param array $clientConfig ข้อมูล client configuration
     * @return array ข้อมูลผู้ใช้จาก Web Application
     * @throws \Exception
     */
    private function callUserHandlerApi(array $normalizedUser, object $ssoUserInfo, array $clientConfig): array
    {
        $endpointUrl = $clientConfig['user_handler_endpoint'];

        $payload = json_encode([
            'normalizedUser' => $normalizedUser,
            'ssoUserInfo' => $ssoUserInfo
        ]);

        $ch = curl_init($endpointUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'X-API-SECRET: ' . ($clientConfig['api_secret_key'] ?? '')
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception('API Call Error: ' . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("API Endpoint returned HTTP status {$httpCode}. Response: {$response}");
        }

        $userData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('ไม่สามารถถอดรหัส JSON ที่ได้รับจาก API Endpoint ได้');
        }

        return $userData;
    }
    
    /**
     * เมธอดสำหรับออกจากระบบ (ทำลาย Session)
     */
    public static function logout() {
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * เมธอดภายใน (private) สำหรับแปลงชื่อ Claims
     * @param object $ssoUserInfo ข้อมูลดิบที่ได้จาก SSO
     * @return array ข้อมูลที่ถูกแปลงเป็นรูปแบบมาตรฐานแล้ว
     */
    private function normalizeClaims(object $ssoUserInfo): array {
        $mapping = $this->config['claim_mapping'];
        $normalized = [];

        foreach ($mapping as $standardKey => $providerKey) {
            $normalized[$standardKey] = $ssoUserInfo->{$providerKey} ?? null;
        }

        return $normalized;
    }
}