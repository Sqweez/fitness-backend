# План внедрения multi-tenancy (DB-per-tenant, MySQL, X-Tenant-Key)

## Цели
- Жесткая изоляция данных: у каждого tenant своя база MySQL.
- Идентификация tenant через заголовок `X-Tenant-Key`.
- Текущий прод‑тенант остается без миграции данных, добавляем инфраструктуру для новых.
- Используем `spatie/laravel-multitenancy` + кастомный резолвер и переключение БД.

## Предпосылки
- Laravel 8.x.
- Отдельная "landlord" БД хранит метаданные tenant и ключи.
- У каждого tenant свои креды к БД.

## Фаза 1: Landlord‑инфраструктура
- Добавить подключение `landlord` в `config/database.php`.
- Добавить шаблон подключения `tenant` в `config/database.php` (значения задаем в рантайме).
- Миграции landlord:
  - `tenants`: id, name, status, db_host, db_port, db_name, db_username, db_password_encrypted, created_at, updated_at.
  - `tenant_api_keys`: id, tenant_id, key_hash, name, last_used_at, revoked_at.
- Модель `Tenant` реализует `IsTenant` и использует `UsesLandlordConnection`.

## Фаза 2: Подключение пакета
- Установить `spatie/laravel-multitenancy`.
- Опубликовать конфиг и выставить:
  - `tenant_database_connection_name = tenant`
  - `landlord_database_connection_name = landlord`
  - `tenant_finder = App\Multitenancy\HeaderTenantFinder::class`
  - `tenant_model = App\Models\Tenant::class`
  - `switch_tenant_tasks` включает кастомный task для переключения БД.

## Фаза 3: Резолв tenant по `X-Tenant-Key`
- Реализовать `HeaderTenantFinder`:
  - Читать `X-Tenant-Key`.
  - Хэшировать `hash_hmac('sha256', $key, config('app.key'))`.
  - Ищем совпадение в `tenant_api_keys` по хэшу.
  - При отсутствии/невалидности -> 401.
- Middleware для обязательного tenant‑контекста на API‑маршрутах.

## Фаза 4: Переключение БД tenant
- Task `SwitchTenantDatabaseWithCredentialsTask`:
  - `DB::purge('tenant')`
  - Записать `database.connections.tenant` (host/port/database/user/password).
  - `DB::reconnect('tenant')`
- Зарегистрировать task в `switch_tenant_tasks`.

## Фаза 5: Миграции и сиды tenant
- Разделить миграции:
  - Landlord остаются в `database/migrations`.
  - Tenant миграции перенести в `database/migrations/tenant`.
- Запуск миграций tenant:
  - `tenants:artisan "migrate --path=database/migrations/tenant"`
- Сиды по необходимости:
  - `tenants:artisan "db:seed --class=..."`

## Фаза 6: Provisioning (создание tenant)
- Команда `tenant:create`:
  - Создать БД tenant и пользователя MySQL.
  - Записать tenant в landlord (пароль хранить encrypted).
  - Сгенерировать raw `X-Tenant-Key`, сохранить хэш, вернуть raw ключ один раз.
  - Прогнать миграции и сиды.
- Опционально:
  - `tenant:rotate-key`
  - `tenant:disable`

## Фаза 7: Очереди, джобы, расписание
- Пробрасывать tenant контекст в jobs и восстанавливать при выполнении.
- Отдельно отметить глобальные задачи, которые не привязаны к tenant.

## Фаза 8: Подключение текущего tenant
- Создать landlord БД и добавить Tenant #1 со ссылкой на текущую БД.
- Сгенерировать ключ и отдать клиентам.
- Миграция данных не нужна.

## Фаза 9: Тестирование
- Feature‑тесты:
  - 401 при отсутствии/неверном `X-Tenant-Key`.
  - Правильный резолв tenant и подключение к нужной БД.
  - Изоляция данных: A не видит данные B.
- Smoke‑тест `tenant:create`.

## Релиз
- Stage: подключить middleware, проверить работу текущего tenant с ключом.
- Prod: создать landlord БД, добавить Tenant #1, включить middleware, раздать ключ, мониторинг логов.
