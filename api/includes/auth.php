
<?php
/**
 * Функции для работы с аутентификацией и JWT токенами
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Генерация простого JWT токена (базовая реализация)
 * В production рекомендуется использовать библиотеку firebase/php-jwt
 */
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload_json = json_encode($payload);
    
    $header_encoded = base64url_encode($header);
    $payload_encoded = base64url_encode($payload_json);
    
    $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, JWT_SECRET_KEY, true);
    $signature_encoded = base64url_encode($signature);
    
    return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
}

/**
 * Проверка JWT токена
 */
function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $expected_signature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET_KEY, true);
    $expected_signature_encoded = base64url_encode($expected_signature);
    
    if (!hash_equals($expected_signature_encoded, $signature)) {
        return false;
    }
    
    $payload_data = json_decode(base64url_decode($payload), true);
    
    // Проверяем срок действия токена
    if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
        return false;
    }
    
    return $payload_data;
}

/**
 * Получение токена из заголовков
 */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Получение заголовка Authorization
 */
function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * Проверка аутентификации пользователя
 */
function requireAuth() {
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token required']);
        exit;
    }
    
    $payload = verifyJWT($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    return $payload;
}

/**
 * Хеширование пароля
 */
function hashPassword($password) {
    return password_hash($password . PASSWORD_SALT, PASSWORD_DEFAULT);
}

/**
 * Проверка пароля
 */
function verifyPassword($password, $hash) {
    return password_verify($password . PASSWORD_SALT, $hash);
}

/**
 * Base64 URL safe encode
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL safe decode
 */
function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
?>
