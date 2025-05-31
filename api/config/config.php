
<?php
/**
 * Главный конфигурационный файл
 */

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

// Настройки безопасности
define('JWT_SECRET_KEY', 'your-super-secret-jwt-key-change-this-in-production');
define('PASSWORD_SALT', 'your-password-salt-change-this-too');

// Настройки API
define('API_VERSION', 'v1');
define('ALLOW_CORS', true);

// Настройки пагинации
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Настройки сессии (если используется)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Включаем отображение ошибок только в development
$environment = $_ENV['APP_ENV'] ?? 'production';
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Настройки CORS если необходимо
if (ALLOW_CORS) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Обработка preflight запросов
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Устанавливаем JSON как тип контента по умолчанию для API
header('Content-Type: application/json; charset=utf-8');
?>
