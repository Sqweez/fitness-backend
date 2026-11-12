# Локальная проверка production-стека

Этот этап выполняется только локально. SSH, изменение Plesk, загрузка файлов на сервер и переключение домена в него не входят. Используются только синтетические данные; копия боевой БД и фотографий не загружалась.

## Состав

- `compose.production.yml` — отдельный стек, не меняющий `docker-compose.yml` разработки.
- `docker/production/Dockerfile` — цели `app`, `web`, `test`; PHP 8.0.30, nginx 1.24.0, Composer 2.8. Код и vendor находятся в образе; bind mount исходников не нужен.
- MariaDB 10.2.44 соответствует версии сервера. Порт БД на хост не публикуется.
- Наружу публикуется только nginx на `127.0.0.1:APP_PORT`.
- `database` и `storage` — постоянные именованные тома. Имя Compose-проекта отделяет пробные данные от других стеков.
- PHP работает под UID 10000 / GID 1003, как владелец сайта на сервере. Nginx работает без root и читает хранилище без права записи.
- Scheduler выключен по умолчанию и включается профилем `scheduler`. Очередь остаётся `sync`.
- Контейнеры не запускают миграции, сидеры и генерацию ключей при старте.

Версии PHP/MariaDB выбраны для первого переноса без одновременного обновления платформы. Их обновление и проверка на ядре боевого сервера остаются отдельными этапами.

## Настройки

Скопировать `.env.production.example` в `.env.production` и заполнить пароли БД. Этот файл исключён из Git. `APP_ENV_FILE` указывает на существующий файл Laravel с `APP_KEY`, `JWT_SECRET` и прочими настройками. Файл монтируется только для чтения; его содержимое не попадает в образ.

Настройки подключения к контейнерной БД, production/debug, файлового кэша и сессий задаются непосредственно в Compose и имеют приоритет над смонтированным Laravel-файлом. Пароль root БД не передаётся PHP. Для реального переноса нужно сохранить прежние ключи приложения, а не генерировать новые.

Для локального стенда использовать отдельный файл с тестовыми ключами, БД **`fitness_docker_preflight`**, порт **18080** и `APP_URL=http://localhost:18080`. `.env` текущей разработки не заменять.

## Сборка и запуск

Все команды запускать из корня репозитория. Проверка конфигурации с `--quiet` не выводит пароли.

```bash
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight config --quiet
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight build php nginx
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight up -d
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight ps
```

Стенд собирается под `linux/amd64`, архитектуру сервера. На ARM-компьютере первая сборка использует эмуляцию и занимает больше времени. В сборку не входят `.env`, SQL-дампы, пользовательское хранилище и документы с аудитом клиентов; из `docs` включается только публичный `openapi.yaml`.

Проверка HTTP: `http://localhost:18080/api/mobile/v1/docs`. Корень `/` намеренно возвращает 404; healthcheck использует маршрут схемы API, который загружает Laravel через PHP-FPM.

## Тесты PHP 8.0

```bash
docker build --platform linux/amd64 --target test -f docker/production/Dockerfile -t gladiators-tests:local .
docker run --rm -e APP_ENV=testing -e CACHE_DRIVER=array -e SESSION_DRIVER=array -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= gladiators-tests:local
```

Здесь запускаются существующие тесты мобильного API, тренеров и часового пояса, использующие SQLite in-memory. Тестовый образ содержит dev-зависимости; production-образ устанавливается с `--no-dev`.

`tests/Docker/smoke.php` предназначен только для отдельной базы `fitness_docker_preflight` с подготовленной схемой. Он отказывается работать с другим именем БД. Режим `create` создаёт синтетического клиента, посещение и тренера с фотографией, выполняет вход через HTTP и сохраняет тестовый токен в непубличной части тестового тома. Также проверяются HTTPS за прокси и раздельные ограничения по IP.

```bash
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight exec -T php php tests/Docker/smoke.php create
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight up -d --force-recreate php nginx
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight exec -T php php tests/Docker/smoke.php verify
```

Повторный режим `verify` проверяет сохранность авторизации, времени посещения и фотографии после пересоздания контейнеров. Для production не использовать тестовую базу и тестовые ключи. Данные реального сайта нужно будет импортировать из свежего дампа вместе с таблицей `migrations`; полный запуск старых миграций на пустой базе не заменяет проверку импорта.

## Остановка

```bash
docker compose --env-file .env.production -f compose.production.yml -p gladiators-preflight down
```

Команда сохраняет тома. `down -v` удаляет тестовую БД и файлы — для обычного обновления или остановки её не применять.

Перед настоящим переключением scheduler включается только после отключения старого cron Gladiators. На этом локальном этапе cron сервера не меняется.
