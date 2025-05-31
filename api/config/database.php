<?php
/**
 * Конфигурация базы данных
 * 
 * Этот файл содержит настройки подключения к MySQL базе данных.
 * Адаптируйте параметры под ваше окружение.
 */

// Определяем окружение (можно изменить через переменную окружения или настройки хостинга)
$environment = $_ENV['APP_ENV'] ?? 'production';

// Настройки для разных окружений
$database_config = [
    'development' => [
        'host' => 'localhost',
        'database' => 'test2',
        'username' => 'test2',
        'password' => 'DxhwsB',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    'production' => [
        'host' => 'localhost',
        'database' => 'test2',
        'username' => 'test2',
        'password' => 'DxhwsB',
        'charset' => 'utf8mb4',
        'port' => 3306
    ]
];

// Выбираем конфигурацию для текущего окружения
$db_config = $database_config[$environment];

// Настройки подключения PDO
$dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}";

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['charset']}"
];

/**
 * Функция для получения подключения к базе данных
 * @return PDO
 */
function getDBConnection() {
    global $dsn, $db_config, $pdo_options;
    
    try {
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $pdo_options);
        return $pdo;
    } catch (PDOException $e) {
        // В production логируем ошибку, но не показываем детали
        error_log("Database connection failed: " . $e->getMessage());
        
        // Возвращаем общую ошибку
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed'
        ]);
        exit;
    }
}

/**
 * Функция для безопасного закрытия подключения
 * @param PDO $pdo
 */
function closeDBConnection(&$pdo) {
    $pdo = null;
}
?>
