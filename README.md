# Fitness Club Management System

Backend API для системы управления фитнес-клубом. Laravel 13 + PHP 8.4 + MariaDB 10.11 + Reverb.

## Требования

- Docker & Docker Compose
- Make (опционально)

**Или без Docker:**
- PHP 8.4 (расширения: pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, intl)
- Composer 2
- MariaDB 10.11

## Установка (Docker)

```bash
# Клонировать репозиторий
git clone <repository-url>
cd fitness-backend

# Установка: создаст .env из .env.docker, соберёт образы, поднимет контейнеры,
# поставит зависимости, сгенерирует APP_KEY и прогонит миграции
make setup
```

После установки работает:

| Что | Где |
|-----|-----|
| API | `http://localhost:8080` |
| Reverb (WebSocket) | `ws://localhost:8080/app/{REVERB_APP_KEY}` — nginx проксирует `/app/` в контейнер `reverb` |
| MariaDB | `127.0.0.1:3306`, пользователь и пароль из `.env` |
| Очередь | контейнер `queue`, драйвер `database` |

Планировщик по умолчанию не запускается: `make scheduler`.

Если `.env` уже был, `make setup` его не перезапишет. Должно быть `DB_HOST=db`, `QUEUE_CONNECTION=database`, `BROADCAST_DRIVER=reverb` и заполнены `REVERB_*` (см. `.env.docker`).

После изменения кода, который выполняется в очереди или в Reverb, перезапусти их: `make queue-restart`.

## Установка (без Docker)

```bash
# Установить зависимости
composer install

# Скопировать и настроить .env
cp .env.example .env

# Сгенерировать ключи
php artisan key:generate

# Запустить миграции
php artisan migrate --seed

# Запустить сервер, очередь и Reverb (в отдельных терминалах)
php artisan serve
php artisan queue:work
php artisan reverb:start
```

## Команды Docker (Makefile)

```bash
make up              # Запустить контейнеры
make down            # Остановить контейнеры
make restart         # Перезапустить
make logs s=php      # Логи контейнера

make shell           # Shell в PHP контейнер
make shell-db        # Консоль MariaDB

make artisan c="..."  # Artisan команда
make composer c="..." # Composer команда

make migrate         # Миграции
make migrate-fresh   # Fresh + seed
make seed            # Сидеры
make rollback        # Откат миграции

make queue-restart   # Перезапустить очередь и Reverb
make scheduler       # Запустить планировщик

make cache-clear     # Очистить кэш
make test            # Тесты

make db-dump         # Дамп БД
make db-restore      # Восстановить БД

make help            # Все команды
```

## API

### Аутентификация

Токен Laravel Sanctum. Передавать в заголовке:
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
├── php/              # Dockerfile PHP для локальной разработки
└── production/       # Образы и nginx.conf для прода (nginx.conf используется и локально)
```

## Технологии

- **Framework:** Laravel 13, PHP 8.4
- **WebSocket:** Laravel Reverb
- **Auth:** Laravel Sanctum, раздельные guards для сотрудников и клиентов
- **Media:** Spatie Media Library
- **DTO:** Spatie Data Transfer Object
- **Dev:** Laravel Telescope, IDE Helper
