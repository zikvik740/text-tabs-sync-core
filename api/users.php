
<?php
require_once 'config/config.php';
require_once 'config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Получаем данные запроса
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_users':
            getUsers($pdo, $input);
            break;
            
        case 'get_user':
            getUser($pdo, $input);
            break;
            
        case 'create_user':
            createUser($pdo, $input);
            break;
            
        case 'update_user':
            updateUser($pdo, $input);
            break;
            
        case 'delete_user':
            deleteUser($pdo, $_GET);
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
 * Получение списка пользователей
 */
function getUsers($pdo, $params) {
    $page = (int)($params['page'] ?? 1);
    $limit = (int)($params['limit'] ?? 20);
    $search = $params['search'] ?? '';
    $status = $params['status'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Базовый запрос
    $whereConditions = [];
    $queryParams = [];
    
    if (!empty($search)) {
        $whereConditions[] = "email LIKE ?";
        $queryParams[] = "%$search%";
    }
    
    if (!empty($status)) {
        $whereConditions[] = "status = ?";
        $queryParams[] = $status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Получаем общее количество
    $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($queryParams);
    $total = $countStmt->fetch()['total'];
    
    // Получаем пользователей с пагинацией
    $query = "
        SELECT 
            u.id,
            u.email,
            u.status,
            u.created_at,
            u.last_active,
            COUNT(p.id) as pagesCount
        FROM users u
        LEFT JOIN user_pages p ON u.id = p.user_id
        $whereClause
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $users = $stmt->fetchAll();
    
    // Форматируем данные
    foreach ($users as &$user) {
        $user['id'] = (int)$user['id'];
        $user['pagesCount'] = (int)$user['pagesCount'];
        $user['createdAt'] = $user['created_at'];
        $user['lastActive'] = $user['last_active'];
        unset($user['created_at'], $user['last_active']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
}

/**
 * Получение пользователя по ID
 */
function getUser($pdo, $params) {
    $id = $params['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User ID is required'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.status,
            u.created_at as createdAt,
            u.last_active as lastActive,
            COUNT(p.id) as pagesCount
        FROM users u
        LEFT JOIN user_pages p ON u.id = p.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        return;
    }
    
    $user['id'] = (int)$user['id'];
    $user['pagesCount'] = (int)$user['pagesCount'];
    
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
}

/**
 * Создание пользователя
 */
function createUser($pdo, $data) {
    $email = $data['email'] ?? '';
    $status = $data['status'] ?? 'pending';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email is required'
        ]);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        return;
    }
    
    // Проверяем, существует ли пользователь
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User with this email already exists'
        ]);
        return;
    }
    
    // Создаем пользователя
    $password_hash = password_hash('temporary_password_' . uniqid(), PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, status, created_at, updated_at, last_active)
        VALUES (?, ?, ?, NOW(), NOW(), NOW())
    ");
    
    if ($stmt->execute([$email, $password_hash, $status])) {
        $userId = $pdo->lastInsertId();
        
        // Получаем созданного пользователя
        $userStmt = $pdo->prepare("
            SELECT id, email, status, created_at as createdAt, last_active as lastActive
            FROM users WHERE id = ?
        ");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        $user['id'] = (int)$user['id'];
        $user['pagesCount'] = 0;
        
        echo json_encode([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create user'
        ]);
    }
}

/**
 * Обновление пользователя
 */
function updateUser($pdo, $data) {
    $id = $data['id'] ?? null;
    $email = $data['email'] ?? '';
    $status = $data['status'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User ID is required'
        ]);
        return;
    }
    
    $updateFields = [];
    $params = [];
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid email format'
            ]);
            return;
        }
        $updateFields[] = "email = ?";
        $params[] = $email;
    }
    
    if (!empty($status)) {
        $updateFields[] = "status = ?";
        $params[] = $status;
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No fields to update'
        ]);
        return;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $params[] = $id;
    
    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update user'
        ]);
    }
}

/**
 * Удаление пользователя
 */
function deleteUser($pdo, $params) {
    $id = $params['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User ID is required'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete user'
        ]);
    }
}
?>
