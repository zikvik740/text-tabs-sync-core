
// Конфигурация API
export const API_CONFIG = {
  // Базовый URL для API (можно настроить под разные окружения)
  BASE_URL: '/api',
  
  // Эндпоинты
  ENDPOINTS: {
    // Аутентификация
    LOGIN: '/auth.php',
    LOGOUT: '/auth.php',
    VERIFY_TOKEN: '/auth.php',
    
    // Пользователи
    USERS: '/users.php',
    USER_BY_ID: '/users.php',
    
    // Страницы пользователей
    PAGES: '/pages.php',
    
    // Статистика и дашборд
    DASHBOARD_STATS: '/dashboard.php',
    
    // Настройки
    SETTINGS: '/settings.php'
  },
  
  // Заголовки по умолчанию
  DEFAULT_HEADERS: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
};

// Получение токена из localStorage (временно, для совместимости)
export const getAuthToken = (): string | null => {
  return localStorage.getItem('auth_token');
};

// Сохранение токена
export const setAuthToken = (token: string): void => {
  localStorage.setItem('auth_token', token);
};

// Удаление токена
export const removeAuthToken = (): void => {
  localStorage.removeItem('auth_token');
};
