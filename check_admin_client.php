<?php
require_once __DIR__ . '/admin/config/admin_config.php';

$config = require __DIR__ . '/admin/config/admin_config.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['database']['host'],
    $config['database']['port'],
    $config['database']['database'],
    $config['database']['charset']
);

try {
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);

    $stmt = $pdo->prepare("SELECT client_id, user_handler_endpoint FROM clients WHERE client_id = ? LIMIT 1");
    $stmt->execute(['admin-panel']);

    $client = $stmt->fetch();

    if ($client) {
        echo "Client found:\n";
        echo "Client ID: " . $client['client_id'] . "\n";
        echo "User Handler Endpoint: " . $client['user_handler_endpoint'] . "\n";
    } else {
        echo "No client found with client_id = 'admin-panel'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
