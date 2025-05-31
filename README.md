
# Админ-панель Text Tabs - React + PHP + MySQL

Это проект админ-панели для управления системой Text Tabs, построенный на React (фронтенд) и PHP (бэкенд) с базой данных MySQL.

## Архитектура проекта

- **Фронтенд**: React + TypeScript + Vite + Tailwind CSS + shadcn/ui
- **Бэкенд**: PHP с RESTful API
- **База данных**: MySQL
- **Развертывание**: Виртуальный PHP-хостинг

## Структура проекта

```
project/
├── api/                     # PHP бэкенд
│   ├── config/
│   │   ├── config.php      # Основные настройки
│   │   └── database.php    # Настройки БД (генерируется автоматически)
│   ├── includes/
│   │   └── auth.php        # JWT аутентификация
│   ├── dashboard.php       # API для дашборда
│   ├── users.php          # API для пользователей
│   ├── pages.php          # API для страниц
│   └── settings.php       # API для настроек
├── src/                    # React фронтенд
├── dist/                   # Собранный фронтенд (после npm run build)
└── README.md
```

## Настройка и развертывание на виртуальном хостинге

### 1. Подготовка файлов

1. **Сборка React-приложения:**
   ```bash
   npm install
   npm run build
   ```

2. **Подготовка к загрузке:**
   - Скопируйте содержимое папки `dist/` в корневую директорию вашего хостинга (обычно `public_html/`)
   - Скопируйте папку `api/` в корневую директорию хостинга

### 2. Структура на хостинге

После загрузки на хостинг структура должна выглядеть так:

```
public_html/
├── api/                    # PHP API
│   ├── config/
│   ├── includes/
│   ├── dashboard.php
│   ├── users.php
│   ├── pages.php
│   └── settings.php
├── assets/                 # Статические файлы React
├── index.html             # Главная страница React
└── ... (другие файлы из dist/)
```

### 3. Настройка базы данных MySQL

#### Создание базы данных

1. **Войдите в панель управления хостингом** (cPanel, ISPManager и т.д.)
2. **Создайте новую базу данных MySQL**
3. **Создайте пользователя базы данных** и назначьте ему все права на созданную БД
4. **Запомните данные подключения:**
   - Хост (обычно `localhost`)
   - Имя базы данных
   - Имя пользователя
   - Пароль
   - Порт (обычно `3306`)

#### Автоматическое создание таблиц

1. **Откройте админ-панель** в браузере (ваш_сайт.com)
2. **Перейдите в раздел "Настройки"**
3. **Введите данные подключения к БД**
4. **Нажмите "Тестировать подключение"** для проверки
5. **Нажмите "Создать таблицы"** для автоматического создания всех необходимых таблиц
6. **Нажмите "Сохранить настройки"** для сохранения конфигурации

#### Ручное создание таблиц (альтернативный способ)

Если автоматическое создание не работает, выполните следующие SQL-запросы:

```sql
-- Таблица пользователей
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица страниц пользователей
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица администраторов
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица системных настроек
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание администратора по умолчанию (логин: admin, пароль: admin123)
INSERT INTO admin_users (username, password_hash, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Базовые системные настройки
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('smtp_host', '', 'SMTP сервер для отправки почты'),
('smtp_port', '587', 'Порт SMTP сервера'),
('smtp_username', '', 'Имя пользователя SMTP'),
('smtp_password', '', 'Пароль SMTP'),
('smtp_encryption', 'tls', 'Тип шифрования (tls/ssl)'),
('site_url', 'https://yourdomain.com', 'URL сайта'),
('admin_email', 'admin@yourdomain.com', 'Email администратора');
```

### 4. Настройка прав доступа

Убедитесь, что у папки `api/config/` есть права на запись (обычно 755 или 777), чтобы приложение могло создавать и обновлять файл `database.php`.

### 5. Первый запуск

1. **Откройте сайт в браузере**
2. **Перейдите в раздел "Настройки"**
3. **Настройте подключение к базе данных**
4. **Создайте необходимые таблицы**
5. **Начните работу с админ-панелью**

## Функциональность

### Дашборд
- Общая статистика (количество пользователей, страниц, активность)
- Графики роста пользователей и активности
- Список последних регистраций

### Управление пользователями
- Просмотр списка всех пользователей
- Создание новых пользователей
- Фильтрация по статусу и поиск
- Редактирование и удаление пользователей

### Управление страницами
- Просмотр всех текстовых страниц пользователей
- Создание новых страниц
- Фильтрация по пользователям и поиск по содержимому
- Редактирование и удаление страниц

### Настройки
- Настройка подключения к базе данных
- Тестирование подключения
- Автоматическое создание таблиц
- Управление системными параметрами

## API Endpoints

### Пользователи (`/api/users.php`)
- `GET ?action=get_users` - получить список пользователей
- `GET ?action=get_user&id=X` - получить пользователя по ID
- `POST action=create_user` - создать пользователя
- `PUT action=update_user` - обновить пользователя
- `DELETE ?action=delete_user&id=X` - удалить пользователя

### Страницы (`/api/pages.php`)
- `GET ?action=get_pages` - получить список страниц
- `GET ?action=get_page&id=X` - получить страницу по ID
- `POST action=create_page` - создать страницу
- `PUT action=update_page` - обновить страницу
- `DELETE ?action=delete_page&id=X` - удалить страницу

### Дашборд (`/api/dashboard.php`)
- `GET ?action=get_dashboard_data` - получить данные для дашборда

### Настройки (`/api/settings.php`)
- `POST action=test_db_connection` - тестировать подключение к БД
- `POST action=save_db_config` - сохранить настройки БД
- `POST action=create_tables` - создать таблицы в БД

## Безопасность

### Важные настройки безопасности:

1. **Измените JWT секретный ключ** в файле `api/config/config.php`:
   ```php
   define('JWT_SECRET_KEY', 'ваш-уникальный-секретный-ключ');
   ```

2. **Измените соль для паролей** в том же файле:
   ```php
   define('PASSWORD_SALT', 'ваша-уникальная-соль');
   ```

3. **Настройте права доступа к файлам:**
   - Файлы PHP: 644
   - Папки: 755
   - Папка `api/config/`: 755 (нужна запись для создания database.php)

4. **Скройте служебные файлы** через `.htaccess`:
   ```apache
   # Защита конфигурационных файлов
   <Files "*.php">
       Order allow,deny
       Allow from all
   </Files>
   
   <Files "config.php">
       Order deny,allow
       Deny from all
   </Files>
   
   <Files "database.php">
       Order deny,allow
       Deny from all
   </Files>
   ```

## Поддержка и разработка

### Требования для разработки:
- Node.js 16+
- PHP 7.4+
- MySQL 5.7+

### Локальная разработка:
```bash
# Установка зависимостей
npm install

# Запуск в режиме разработки
npm run dev

# Сборка для продакшена
npm run build
```

## Решение проблем

### Распространенные проблемы:

1. **Ошибка подключения к БД**
   - Проверьте правильность данных подключения
   - Убедитесь, что база данных создана
   - Проверьте права пользователя БД

2. **404 ошибки для API**
   - Убедитесь, что папка `api/` загружена на хостинг
   - Проверьте настройки URL Rewrite

3. **Пустые страницы или белый экран**
   - Проверьте логи ошибок хостинга
   - Убедитесь, что файлы загружены корректно

4. **Проблемы с CORS**
   - API настроен на работу с любыми доменами
   - При необходимости измените настройки в `api/config/config.php`

Для получения помощи обратитесь к документации вашего хостинг-провайдера или в техническую поддержку.
