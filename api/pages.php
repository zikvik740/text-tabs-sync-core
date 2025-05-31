
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
        case 'get_pages':
            getPages($pdo, $input);
            break;
            
        case 'get_page':
            getPage($pdo, $input);
            break;
            
        case 'create_page':
            createPage($pdo, $input);
            break;
            
        case 'update_page':
            updatePage($pdo, $input);
            break;
            
        case 'delete_page':
            deletePage($pdo, $_GET);
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
 * Получение списка страниц
 */
function getPages($pdo, $params) {
    $page = (int)($params['page'] ?? 1);
    $limit = (int)($params['limit'] ?? 20);
    $search = $params['search'] ?? '';
    $userId = $params['user_id'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Базовый запрос
    $whereConditions = [];
    $queryParams = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $queryParams[] = "%$search%";
        $queryParams[] = "%$search%";
    }
    
    if (!empty($userId)) {
        $whereConditions[] = "p.user_id = ?";
        $queryParams[] = $userId;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Получаем общее количество
    $countQuery = "SELECT COUNT(*) as total FROM user_pages p $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($queryParams);
    $total = $countStmt->fetch()['total'];
    
    // Получаем страницы с пагинацией
    $query = "
        SELECT 
            p.id,
            p.user_id,
            p.title,
            p.content,
            p.created_at,
            p.updated_at,
            u.email as user_email
        FROM user_pages p
        LEFT JOIN users u ON p.user_id = u.id
        $whereClause
        ORDER BY p.updated_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $pages = $stmt->fetchAll();
    
    // Форматируем данные
    foreach ($pages as &$page) {
        $page['id'] = (int)$page['id'];
        $page['user_id'] = (int)$page['user_id'];
        $page['createdAt'] = $page['created_at'];
        $page['updatedAt'] = $page['updated_at'];
        $page['userEmail'] = $page['user_email'];
        unset($page['created_at'], $page['updated_at'], $page['user_email']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'pages' => $pages,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit
        ]
    ]);
}

/**
 * Получение страницы по ID
 */
function getPage($pdo, $params) {
    $id = $params['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Page ID is required'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.user_id,
            p.title,
            p.content,
            p.created_at as createdAt,
            p.updated_at as updatedAt,
            u.email as userEmail
        FROM user_pages p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$id]);
    $page = $stmt->fetch();
    
    if (!$page) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Page not found'
        ]);
        return;
    }
    
    $page['id'] = (int)$page['id'];
    $page['user_id'] = (int)$page['user_id'];
    
    echo json_encode([
        'success' => true,
        'data' => $page
    ]);
}

/**
 * Создание страницы
 */
function createPage($pdo, $data) {
    $userId = $data['user_id'] ?? '';
    $title = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    
    if (empty($userId) || empty($title)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User ID and title are required'
        ]);
        return;
    }
    
    // Проверяем, существует ли пользователь
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'User does not exist'
        ]);
        return;
    }
    
    // Создаем страницу
    $stmt = $pdo->prepare("
        INSERT INTO user_pages (user_id, title, content, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    if ($stmt->execute([$userId, $title, $content])) {
        $pageId = $pdo->lastInsertId();
        
        // Получаем созданную страницу
        $pageStmt = $pdo->prepare("
            SELECT 
                p.id,
                p.user_id,
                p.title,
                p.content,
                p.created_at as createdAt,
                p.updated_at as updatedAt,
                u.email as userEmail
            FROM user_pages p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $pageStmt->execute([$pageId]);
        $page = $pageStmt->fetch();
        
        $page['id'] = (int)$page['id'];
        $page['user_id'] = (int)$page['user_id'];
        
        echo json_encode([
            'success' => true,
            'data' => $page,
            'message' => 'Page created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create page'
        ]);
    }
}

/**
 * Обновление страницы
 */
function updatePage($pdo, $data) {
    $id = $data['id'] ?? null;
    $title = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Page ID is required'
        ]);
        return;
    }
    
    $updateFields = [];
    $params = [];
    
    if (!empty($title)) {
        $updateFields[] = "title = ?";
        $params[] = $title;
    }
    
    if (isset($content)) {
        $updateFields[] = "content = ?";
        $params[] = $content;
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
    
    $query = "UPDATE user_pages SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'Page updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update page'
        ]);
    }
}

/**
 * Удаление страницы
 */
function deletePage($pdo, $params) {
    $id = $params['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Page ID is required'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM user_pages WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode([
            'success' => true,
            'message' => 'Page deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete page'
        ]);
    }
}
?>
