
<?php
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_dashboard_data':
            getDashboardData($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Получение данных для дашборда
 */
function getDashboardData($pdo) {
    // Общее количество пользователей
    $totalUsersStmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $totalUsersStmt->fetch()['count'];
    
    // Количество подтвержденных пользователей
    $verifiedUsersStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'verified'");
    $verifiedUsers = $verifiedUsersStmt->fetch()['count'];
    
    // Общее количество страниц
    $totalPagesStmt = $pdo->query("SELECT COUNT(*) as count FROM user_pages");
    $totalPages = $totalPagesStmt->fetch()['count'];
    
    // Активность за последнюю неделю (новые пользователи)
    $weekAgoUsersStmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $weekAgoUsers = $weekAgoUsersStmt->fetch()['count'];
    
    // Активность за предыдущую неделю
    $prevWeekUsersStmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $prevWeekUsers = $prevWeekUsersStmt->fetch()['count'];
    
    // Вычисляем процент изменения активности
    $activityPercent = 0;
    if ($prevWeekUsers > 0) {
        $activityPercent = round((($weekAgoUsers - $prevWeekUsers) / $prevWeekUsers) * 100);
    } elseif ($weekAgoUsers > 0) {
        $activityPercent = 100;
    }
    
    // Данные для графика пользователей (последние 6 месяцев)
    $usersChartStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as users
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $usersChart = $usersChartStmt->fetchAll();
    
    // Данные для графика активности (последние 7 дней)
    $activityChartStmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as pages
        FROM user_pages 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $activityChart = $activityChartStmt->fetchAll();
    
    // Последние пользователи
    $recentUsersStmt = $pdo->query("
        SELECT 
            id,
            email,
            status,
            created_at as createdAt
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $recentUsersStmt->fetchAll();
    
    // Форматируем данные
    foreach ($recentUsers as &$user) {
        $user['id'] = (int)$user['id'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'totalUsers' => (int)$totalUsers,
                'verifiedUsers' => (int)$verifiedUsers,
                'totalPages' => (int)$totalPages,
                'activityPercent' => $activityPercent
            ],
            'usersChart' => $usersChart,
            'activityChart' => $activityChart,
            'recentUsers' => $recentUsers
        ]
    ]);
}
?>
