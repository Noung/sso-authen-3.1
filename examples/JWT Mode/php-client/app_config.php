<?php
// www/app_config.php

// **สำคัญมาก:** กุญแจลับนี้ต้องตรงกับค่า JWT_SECRET_KEY
// ในไฟล์ config.php ของ sso-authen ทุกประการ
define('JWT_SHARED_SECRET_KEY', 'YOUR_SUPER_SECRET_KEY_FOR_JWT_GENERATION_THAT_IS_VERY_LONG');
