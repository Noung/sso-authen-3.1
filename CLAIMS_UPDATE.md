# 🎉 Claims System Update - Complete Documentation

**อัปเดตเมื่อ:** 2025-10-22  
**เวอร์ชัน:** SSO-Authen V.3 Extended Claims Support

---

## 📋 สรุปการอัปเดต

การอัปเดตครั้งนี้เพิ่มการรองรับ **Extended Claims** จาก OIDC Providers (โดยเฉพาะ PSU SSO) ซึ่งมีข้อมูลผู้ใช้ที่ครบถ้วนกว่า providers อื่นๆ เช่น Google, Auth0, Microsoft

### ✅ **สิ่งที่ทำสำเร็จ**

1. ✅ อัปเดต Provider Configurations ทั้ง 5 ไฟล์
2. ✅ อัปเดต Database Schema (`admin_users` table)
3. ✅ อัปเดตตัวอย่าง User Handler (PHP)
4. ✅ สร้าง SQL Migration Scripts
5. ✅ สร้างคำแนะนำสำหรับ Client Applications

---

## 🎯 **Extended Claims ที่เพิ่มเข้ามา**

### **Normalized User Schema (New)**

```php
$normalizedUser = [
    // ===== Basic Claims (เดิม) =====
    'id'           => '...',  // Required
    'username'     => '...',  // Required
    'name'         => '...',  // Required
    'firstName'    => '...',  // Required
    'lastName'     => '...',  // Required
    'email'        => '...',  // Required
    'department'   => '...',  // Optional
    
    // ===== Extended Claims (ใหม่) =====
    'position'     => '...',  // ตำแหน่งงาน (PSU: position_th)
    'campus'       => '...',  // วิทยาเขต (PSU: campus_th)
    'officeName'   => '...',  // ชื่อสำนักงาน (PSU: office_name_th)
    'facultyId'    => '...',  // รหัสคณะ (PSU: faculty_id)
    'departmentId' => '...',  // รหัสภาควิชา (PSU: department_id)
    'campusId'     => '...',  // รหัสวิทยาเขต (PSU: campus_id)
    'groups'       => []      // กลุ่มผู้ใช้ (PSU/Okta: groups)
];
```

---

## 📊 **Provider Claim Mapping**

### **PSU SSO** (ครบถ้วน)
```php
'claim_mapping' => [
    // Basic
    'id'           => 'psu_id',
    'username'     => 'preferred_username',
    'name'         => 'display_name_th',
    'firstName'    => 'first_name_th',
    'lastName'     => 'last_name_th',
    'email'        => 'email',
    'department'   => 'department_th',
    
    // Extended (PSU มีครบ!)
    'position'     => 'position_th',
    'campus'       => 'campus_th',
    'officeName'   => 'office_name_th',
    'facultyId'    => 'faculty_id',
    'departmentId' => 'department_id',
    'campusId'     => 'campus_id',
    'groups'       => 'groups'
]
```

### **Google** (พื้นฐาน)
```php
'claim_mapping' => [
    // Basic
    'id'           => 'sub',
    'username'     => 'email',
    'name'         => 'name',
    'firstName'    => 'given_name',
    'lastName'     => 'family_name',
    'email'        => 'email',
    'department'   => null,
    
    // Extended (ไม่มี - จะเป็น null)
    'position'     => null,
    'campus'       => null,
    'officeName'   => null,
    'facultyId'    => null,
    'departmentId' => null,
    'campusId'     => null,
    'groups'       => null
]
```

### **Auth0, Microsoft, Okta**
คล้ายกับ Google - มี basic claims แต่ extended claims เป็น null (ยกเว้น Okta อาจมี `groups`)

---

## 🗄️ **Database Changes**

### **1. SSO-Authen: `admin_users` Table**

**Migration:** [`database/migrations/add_extended_claims_to_admin_users.sql`](file://c:\laragon\www\sso-authen-3\database\migrations\add_extended_claims_to_admin_users.sql)

**คอลัมน์ที่เพิ่ม:**
```sql
position       VARCHAR(255) NULL
campus         VARCHAR(255) NULL
office_name    VARCHAR(255) NULL
faculty_id     VARCHAR(50) NULL
department_id  VARCHAR(50) NULL
campus_id      VARCHAR(50) NULL
`groups`       TEXT NULL
provider       VARCHAR(50) DEFAULT 'psu'
```

**วิธีรัน:**
```bash
# ผ่าน phpMyAdmin หรือ MySQL CLI
mysql -u root -p sso_authen < database/migrations/add_extended_claims_to_admin_users.sql
```

### **2. Client Applications: `users` Table**

**แนะนำ Schema:** [`database/migrations/RECOMMENDED_users_table_for_client_apps.sql`](file://c:\laragon\www\sso-authen-3\database\migrations\RECOMMENDED_users_table_for_client_apps.sql)

นี่เป็นเพียงคำแนะนำ - คุณสามารถปรับแต่งตามความต้องการของแอปพลิเคชันได้

---

## 🔧 **การใช้งาน Extended Claims**

### **สำหรับนักพัฒนา Client Application**

#### **1. อัปเดตตาราง `users`** (ถ้ายังไม่มี extended columns)

```sql
ALTER TABLE users 
ADD COLUMN position VARCHAR(255) NULL AFTER department,
ADD COLUMN campus VARCHAR(255) NULL AFTER position,
ADD COLUMN office_name VARCHAR(255) NULL AFTER campus,
ADD COLUMN faculty_id VARCHAR(50) NULL AFTER office_name,
ADD COLUMN department_id VARCHAR(50) NULL AFTER faculty_id,
ADD COLUMN campus_id VARCHAR(50) NULL AFTER department_id,
ADD COLUMN `groups` TEXT NULL AFTER campus_id;
```

#### **2. อัปเดต User Handler API**

**ตัวอย่าง:** [`examples/JWT Mode/php-client/api/user-handler.php`](file://c:\laragon\www\sso-authen-3\examples\JWT Mode\php-client\api\user-handler.php)

```php
// รับข้อมูลจาก SSO-Authen
$normalizedUser = $data['normalizedUser'];

// บันทึกทั้ง basic และ extended claims
$insertStmt = $pdo->prepare("
    INSERT INTO users (
        user_id, email, name, first_name, last_name,
        position, campus, office_name,
        faculty_id, department_id, campus_id, `groups`,
        role
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insertStmt->execute([
    $normalizedUser['id'],
    $normalizedUser['email'],
    $normalizedUser['name'],
    $normalizedUser['firstName'] ?? null,
    $normalizedUser['lastName'] ?? null,
    $normalizedUser['position'] ?? null,      // Extended
    $normalizedUser['campus'] ?? null,        // Extended
    $normalizedUser['officeName'] ?? null,    // Extended
    $normalizedUser['facultyId'] ?? null,     // Extended
    $normalizedUser['departmentId'] ?? null,  // Extended
    $normalizedUser['campusId'] ?? null,      // Extended
    isset($normalizedUser['groups']) ? json_encode($normalizedUser['groups']) : null,
    'user'
]);
```

#### **3. จัดการ NULL Values**

เนื่องจาก providers บางตัว (เช่น Google) ไม่มี extended claims คุณต้องจัดการ NULL:

```php
// แสดงผลใน UI
$position = $user['position'] ?? 'ไม่ระบุ';
$campus = $user['campus'] ?? 'N/A';

// ใน SQL Query
SELECT COALESCE(position, 'ไม่ระบุ') AS position_display FROM users;

// การค้นหาที่ปลอดภัย
WHERE faculty_id IS NOT NULL AND faculty_id = 'F01';
```

---

## 📚 **ตัวอย่างการใช้งาน**

### **ตัวอย่าง 1: แสดงข้อมูลผู้ใช้ PSU**

```php
// ผู้ใช้จาก PSU SSO (มีข้อมูลครบ)
Array (
    [id] => 123
    [user_id] => '6510110001'
    [email] => 'john.d@psu.ac.th'
    [name] => 'นายจอห์น โด'
    [first_name] => 'จอห์น'
    [last_name] => 'โด'
    [position] => 'อาจารย์'
    [campus] => 'หาดใหญ่'
    [office_name] => 'คณะวิทยาศาสตร์'
    [faculty_id] => 'F01'
    [department_id] => 'D0101'
    [campus_id] => 'C01'
    [groups] => '["staff","lecturer"]'
    [role] => 'admin'
)
```

### **ตัวอย่าง 2: แสดงข้อมูลผู้ใช้ Google**

```php
// ผู้ใช้จาก Google (extended เป็น null)
Array (
    [id] => 456
    [user_id] => '108234567890'
    [email] => 'user@gmail.com'
    [name] => 'John Doe'
    [first_name] => 'John'
    [last_name] => 'Doe'
    [position] => null        // ← Google ไม่มี
    [campus] => null          // ← Google ไม่มี
    [office_name] => null     // ← Google ไม่มี
    [faculty_id] => null      // ← Google ไม่มี
    [department_id] => null   // ← Google ไม่มี
    [campus_id] => null       // ← Google ไม่มี
    [groups] => null          // ← Google ไม่มี
    [role] => 'user'
)
```

---

## 🔍 **Use Cases**

### **1. Authorization ตาม Faculty**
```php
// อนุญาตเฉพาะคณะวิทยาศาสตร์
if ($user['faculty_id'] === 'F01') {
    // ให้เข้าถึงฟีเจอร์พิเศษ
}
```

### **2. แสดงข้อมูลตาม Campus**
```php
// แสดง resources เฉพาะวิทยาเขต
SELECT * FROM resources WHERE campus_id = :campus_id;
```

### **3. จัดการ Groups**
```php
// ตรวจสอบว่าอยู่ในกลุ่ม
$groups = json_decode($user['groups'], true) ?? [];
if (in_array('admin', $groups)) {
    // Admin access
}
```

---

## ⚠️ **ข้อควรระวัง**

### **1. NULL Safety**
```php
// ❌ อันตราย - อาจ error
echo $user['position'];

// ✅ ปลอดภัย
echo $user['position'] ?? 'ไม่ระบุ';
```

### **2. Groups Handling**
```php
// Groups อาจเป็น JSON string หรือ NULL
$groupsArray = $user['groups'] ? json_decode($user['groups'], true) : [];
```

### **3. Provider-Specific Logic**
```php
// ตรวจสอบ provider ก่อนใช้ extended claims
if ($user['provider'] === 'psu') {
    // ใช้ faculty_id, campus_id ได้เลย
} else {
    // ต้องจัดการ NULL
}
```

---

## 📖 **เอกสารเพิ่มเติม**

- **PSU SSO OpenID Configuration**: ข้อมูล claims ที่รองรับจริง
- **Provider Configurations**: [`config/providers/`](file://c:\laragon\www\sso-authen-3\config\providers)
- **User Handler Examples**: [`examples/JWT Mode/`](file://c:\laragon\www\sso-authen-3\examples\JWT Mode)
- **API Documentation**: [`admin/public/api-docs-v3.html`](file://c:\laragon\www\sso-authen-3\admin\public\api-docs-v3.html)

---

## 🎓 **FAQ**

**Q: ทำไมต้องเพิ่ม extended claims?**  
A: PSU SSO ให้ข้อมูลผู้ใช้ที่มากกว่า providers อื่น ถ้าไม่รองรับจะเสียข้อมูลที่มีประโยชน์

**Q: แอปของผมต้องรองรับทั้งหมดไหม?**  
A: ไม่จำเป็น - ถ้าใช้เฉพาะ PSU SSO ก็รองรับได้เต็มที่ แต่ถ้าต้องรองรับหลาย providers ต้องจัดการ NULL

**Q: จะรู้ได้ไงว่า provider ไหนมี claim อะไร?**  
A: ดูที่ `claim_mapping` ในไฟล์ `config/providers/{provider}.php`

**Q: Groups เก็บเป็น JSON หรือ comma-separated?**  
A: แนะนำ JSON เพราะจัดการง่ายกว่า และรองรับ nested structures

---

## ✅ **Checklist สำหรับนักพัฒนา**

เมื่อนำไปใช้งานจริง ตรวจสอบว่าทำครบหรือยัง:

- [ ] อัปเดตตาราง `users` ให้มี extended columns
- [ ] อัปเดต User Handler API ให้บันทึก extended claims
- [ ] จัดการ NULL values ในโค้ดทุกที่ที่ใช้ extended claims
- [ ] เพิ่ม indexes สำหรับ columns ที่ใช้ค้นหาบ่อย
- [ ] ทดสอบกับทั้ง PSU SSO และ providers อื่นๆ
- [ ] อัปเดตเอกสาร API ของแอปพลิเคชัน
- [ ] แจ้งทีมเกี่ยวกับ fields ใหม่

---

**🎉 สรุป:** การอัปเดตนี้ทำให้ SSO-Authen รองรับข้อมูลผู้ใช้ที่หลากหลายมากขึ้น โดยยังคง backward compatible กับระบบเดิม

**ติดต่อ:** หากมีคำถามหรือปัญหา โปรดแจ้งที่ทีมพัฒนา SSO-Authen
