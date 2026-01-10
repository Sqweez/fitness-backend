# Fitness Club Management System

Backend API для системы управления фитнес-клубом. Laravel 8 + MySQL + Redis.

## Требования

- Docker & Docker Compose
- Make (опционально)

**Или без Docker:**
- PHP 7.4+
- Composer
- MySQL 8.0
- Redis

## Установка (Docker)

```bash
# Клонировать репозиторий
git clone <repository-url>
cd fitness-backend

# Скопировать конфигурацию
cp .env.docker .env

# Запустить установку
make setup
```

Приложение будет доступно на `http://localhost:8080`

## Установка (без Docker)

```bash
# Установить зависимости
composer install

# Скопировать и настроить .env
cp .env.example .env

# Сгенерировать ключи
php artisan key:generate
php artisan jwt:secret

# Запустить миграции
php artisan migrate --seed

# Запустить сервер
php artisan serve
```

## Команды Docker (Makefile)

```bash
make up              # Запустить контейнеры
make down            # Остановить контейнеры
make restart         # Перезапустить
make logs s=php      # Логи контейнера

make shell           # Shell в PHP контейнер
make shell-mysql     # Shell в MySQL

make artisan c="..."  # Artisan команда
make composer c="..." # Composer команда

make migrate         # Миграции
make migrate-fresh   # Fresh + seed
make seed            # Сидеры
make rollback        # Откат миграции

make cache-clear     # Очистить кэш
make test            # Тесты

make db-dump         # Дамп БД
make db-restore      # Восстановить БД

make help            # Все команды
```

## API

### Аутентификация

JWT токен. Передавать в заголовке:
```
Authorization: Bearer <token>
```

### Основные эндпоинты

| Метод | URL | Описание |
|-------|-----|----------|
| POST | `/api/v1/auth/login` | Авторизация |
| GET | `/api/v1/clients` | Список клиентов |
| GET | `/api/v1/services` | Список услуг |
| GET | `/api/v1/products` | Список товаров |
| GET | `/api/v1/dashboard/*` | Дашборд |
| GET | `/api/v1/economy/*` | Финансовые отчеты |
| GET | `/api/v1/stats/*` | Статистика |

### Mobile API

| Метод | URL | Описание |
|-------|-----|----------|
| POST | `/api/mobile/v1/auth/login` | Авторизация клиента |
| GET | `/api/mobile/v1/profile` | Профиль клиента |
| GET | `/api/mobile/v1/club/stats` | Загрузка клубов |

## Структура проекта

```
app/
├── Actions/          # Бизнес-логика
├── Http/
│   ├── Controllers/  # Контроллеры API
│   ├── DTO/          # Data Transfer Objects
│   ├── Requests/     # Form Requests
│   ├── Resources/    # API Resources
│   └── Services/     # Сервисы
├── Models/           # Eloquent модели
└── helpers.php       # Глобальные хелперы

docker/
├── nginx/            # Конфиг Nginx
└── php/              # Dockerfile PHP
```

## Технологии

- **Framework:** Laravel 8
- **Auth:** JWT (tymon/jwt-auth)
- **Media:** Spatie Media Library
- **DTO:** Spatie Data Transfer Object
- **Dev:** Laravel Telescope, IDE Helper
