<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'SSO Authentication'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Simple CSS for centering content and creating message boxes */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Bai Jamjuree', sans-serif;
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
            font-family: 'Bai Jamjuree', sans-serif !important;
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