
<?php
require_once 'config/config.php';

header('Content-Type: application/json');

// Получаем данные запроса
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'test_db_connection':
        testDatabaseConnection($input['config']);
        break;
        
    case 'save_db_config':
        saveDatabaseConfig($input['config']);
        break;
        
    case 'create_tables':
        createDatabaseTables($input['config']);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}

/**
 * Тестирование подключения к базе данных
 */
function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Проверяем соединение простым запросом
        $stmt = $pdo->query('SELECT 1');
        
        echo json_encode([
            'success' => true,
            'message' => 'Подключение к базе данных успешно установлено!'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка подключения: ' . $e->getMessage()
        ]);
    }
}

/**
 * Создание таблиц в базе данных
 */
function createDatabaseTables($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // SQL для создания таблиц
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    email_verification_token VARCHAR(100) DEFAULT NULL,
                    email_verified_at TIMESTAMP NULL DEFAULT NULL,
                    password_reset_token VARCHAR(100) DEFAULT NULL,
                    password_reset_expires_at TIMESTAMP NULL DEFAULT NULL,
                    status ENUM('pending', 'verified', 'blocked') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_status (status),
                    INDEX idx_verification_token (email_verification_token),
                    INDEX idx_reset_token (password_reset_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'user_pages' => "
                CREATE TABLE IF NOT EXISTS user_pages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(500) NOT NULL,
                    content LONGTEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'admin_users' => "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL DEFAULT NULL,
                    INDEX idx_username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'system_settings' => "
                CREATE TABLE IF NOT EXISTS system_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NOT NULL,
                    description TEXT DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_key (setting_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        // Создаем таблицы
        $createdTables = [];
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            $createdTables[] = $tableName;
        }
        
        // Создаем первого администратора если его нет
        $adminCheck = $pdo->query("SELECT COUNT(*) as count FROM admin_users")->fetch();
        if ($adminCheck['count'] == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("
                INSERT INTO admin_users (username, password_hash, email) 
                VALUES ('admin', ?, 'admin@example.com')
            ")->execute([$adminPassword]);
            $createdTables[] = 'admin_users (с пользователем admin/admin123)';
        }
        
        // Добавляем базовые настройки если их нет
        $settingsCheck = $pdo->query("SELECT COUNT(*) as count FROM system_settings")->fetch();
        if ($settingsCheck['count'] == 0) {
            $defaultSettings = [
                ['smtp_host', '', 'SMTP сервер для отправки почты'],
                ['smtp_port', '587', 'Порт SMTP сервера'],
                ['smtp_username', '', 'Имя пользователя SMTP'],
                ['smtp_password', '', 'Пароль SMTP'],
                ['smtp_encryption', 'tls', 'Тип шифрования (tls/ssl)'],
                ['site_url', 'https://yourdomain.com', 'URL сайта'],
                ['admin_email', 'admin@yourdomain.com', 'Email администратора']
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
            $createdTables[] = 'system_settings (с базовыми настройками)';
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Таблицы успешно созданы: ' . implode(', ', $createdTables)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при создании таблиц: ' . $e->getMessage()
        ]);
    }
}

/**
 * Сохранение конфигурации базы данных
 */
function saveDatabaseConfig($config) {
    try {
        // Сначала тестируем подключение
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Если подключение успешно, сохраняем конфигурацию
        $configTemplate = "<?php
/**
 * Конфигурация базы данных
 * 
 * Этот файл содержит настройки подключения к MySQL базе данных.
 * Адаптируйте параметры под ваше окружение.
 */

// Определяем окружение (можно изменить через переменную окружения или настройки хостинга)
\$environment = \$_ENV['APP_ENV'] ?? 'production';

// Настройки для разных окружений
\$database_config = [
    'development' => [
        'host' => '{$config['host']}',
        'database' => '{$config['database']}',
        'username' => '{$config['username']}',
        'password' => '{$config['password']}',
        'charset' => 'utf8mb4',
        'port' => {$config['port']}
    ],
    'production' => [
        'host' => '{$config['host']}',
        'database' => '{$config['database']}',
        'username' => '{$config['username']}',
        'password' => '{$config['password']}',
        'charset' => 'utf8mb4',
        'port' => {$config['port']}
    ]
];

// Выбираем конфигурацию для текущего окружения
\$db_config = \$database_config[\$environment];

// Настройки подключения PDO
\$dsn = \"mysql:host={\$db_config['host']};port={\$db_config['port']};dbname={\$db_config['database']};charset={\$db_config['charset']}\";

\$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES {\$db_config['charset']}\"
];

/**
 * Функция для получения подключения к базе данных
 * @return PDO
 */
function getDBConnection() {
    global \$dsn, \$db_config, \$pdo_options;
    
    try {
        \$pdo = new PDO(\$dsn, \$db_config['username'], \$db_config['password'], \$pdo_options);
        return \$pdo;
    } catch (PDOException \$e) {
        // В production логируем ошибку, но не показываем детали
        error_log(\"Database connection failed: \" . \$e->getMessage());
        
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
 * @param PDO \$pdo
 */
function closeDBConnection(&\$pdo) {
    \$pdo = null;
}
?>";

        // Записываем файл конфигурации
        $configPath = __DIR__ . '/config/database.php';
        file_put_contents($configPath, $configTemplate);
        
        echo json_encode([
            'success' => true,
            'message' => 'Настройки базы данных успешно сохранены!'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при сохранении: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка записи файла: ' . $e->getMessage()
        ]);
    }
}
?>
