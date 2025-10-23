<?php
/** 
 * public/helpers.php
 * *  
 */ 

/**
 * แสดงผลหน้าเว็บพร้อม SweetAlert และ Redirect ไปยัง URL ที่กำหนด
 * @param string $title หัวข้อของ Alert
 * @param string $text ข้อความใน Alert
 * @param string $icon ไอคอน ('success', 'error', 'warning', 'info')
 * @param string $redirectUrl URL ที่จะให้ไปต่อหลังกดปุ่ม
 */
function render_alert_and_redirect(string $title, string $text, string $icon, string $redirectUrl) {
    $pageTitle = $title;
    
    // Generate JavaScript code for SweetAlert
    $script = "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '" . addslashes($title) . "',
                    text: '" . addslashes($text) . "',
                    icon: '" . addslashes($icon) . "',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    window.location.href = '" . addslashes($redirectUrl) . "';
                });
            });
        </script>
    ";

    // --- PHP Templating Logic ---
    ob_start(); // Start output buffering
    echo "<h3>Please wait...</h3>";
    $pageContent = ob_get_clean(); // Get buffered output
    $pageScript = $script; // ส่ง script ไปให้ layout

    require_once __DIR__ . '/templates/layout.php'; // เรียกใช้ Layout
    exit; // หยุดการทำงานทันที
}