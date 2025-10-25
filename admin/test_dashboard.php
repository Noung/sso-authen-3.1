<?php
require_once 'src/Database/Connection.php';
require_once 'src/Controllers/DashboardController.php';

use SsoAdmin\Database\Connection;
use SsoAdmin\Controllers\DashboardController;

// Load admin configuration
$config = require 'config/admin_config.php';

// Initialize database connection
Connection::init($config['database']);

// Test the new methods
$controller = new DashboardController();

echo "Testing Dashboard Methods:\n";

// Test requests today
$requestsToday = (new ReflectionClass($controller))->getMethod('getRequestsToday');
$requestsToday->setAccessible(true);
echo "Requests Today: " . $requestsToday->invoke($controller) . "\n";

// Test success rate
$successRate = (new ReflectionClass($controller))->getMethod('getSuccessRate');
$successRate->setAccessible(true);
echo "Success Rate: " . $successRate->invoke($controller) . "%\n";

// Test recent activities
$recentActivities = (new ReflectionClass($controller))->getMethod('getRecentActivitiesFromDb');
$recentActivities->setAccessible(true);
$activities = $recentActivities->invoke($controller, 5);
echo "Recent Activities:\n";
print_r($activities);
