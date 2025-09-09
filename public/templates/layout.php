<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'PSU SSO Authentication'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
    <style>
        /* CSS ที่เรียบง่ายสำหรับจัดกลางหน้าจอและสร้างกล่องข้อความ */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: "Prompt", sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .message-box {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 2rem;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .swal2-popup {
            font-family: "Prompt", sans-serif !important;
        }
    </style>
</head>
<body>
    
    <div class="message-box">
        <!-- <?php echo $pageContent ?? ''; ?> -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php echo $pageScript ?? ''; ?>
    
</body>
</html>