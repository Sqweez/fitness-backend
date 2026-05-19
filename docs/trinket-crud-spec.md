# ТЗ: CRUD для модели Trinket

## Цель

Добавить административный CRUD для ключей/браслетов (`Trinket`) в существующий API Laravel.

Функциональность должна позволять авторизованным пользователям просматривать, создавать, редактировать и удалять ключи клубов без поломки текущей логики выдачи ключей клиенту через `SessionController::attach`.

## Текущий контекст

- Модель: `App\Models\Trinket`.
- Таблица: `trinkets`.
- Поля:
  - `id`
  - `code`
  - `cabinet_number`
  - `club_id`
  - `created_at`
  - `updated_at`
- Связи:
  - `Trinket belongsTo Club`
  - `Trinket hasMany Session`
  - `Trinket hasOne active_session`, где `finished_at IS NULL`
- Текущие использования:
  - выдача ключа клиенту: `POST /api/v1/session/attach/{client}`;
  - список занятых ключей: `GET /api/v1/economy/my/keys`;
  - история клиентов и отчеты через связь `session.trinket`.

## Границы задачи

Входит:

- API CRUD для `Trinket`.
- Валидация входных данных.
- Фильтрация и поиск списка.
- Проверка занятости ключа перед удалением.
- Тесты на основные успешные и ошибочные сценарии.

Не входит:

- Изменение бизнес-логики выдачи ключей клиенту.
- Импорт ключей из старой базы.
- Массовый импорт/экспорт ключей.
- Фронтенд-реализация.
- Роли и permissions сверх текущей модели `is_boss`/`club_id`, если отдельная система прав не появится.

## Требования к API

Все endpoints размещаются внутри существующей группы:

```php
Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api',
], function () {
    Route::apiResource('trinkets', TrinketController::class);
});
```

Базовый путь: `/api/v1/trinkets`.

### GET `/api/v1/trinkets`

Возвращает список ключей.

Query parameters:

- `club_id` optional integer: фильтр по клубу.
- `search` optional string: поиск по `code` и `cabinet_number`.
- `status` optional string: `free`, `busy`, `all`; default `all`.
- `per_page` optional integer: default `50`, max `100`.
- `page` optional integer.

Правила доступа:

- Boss-пользователь может видеть все клубы и использовать `club_id`.
- Не boss-пользователь видит только ключи своего `auth()->user()->club_id`; переданный чужой `club_id` игнорируется или возвращает 403. Выбрать один вариант и зафиксировать в тестах, предпочтительно 403.

Ответ:

```json
{
  "trinkets": [
    {
      "id": 1,
      "code": "7555530",
      "cabinet_number": "1Ж",
      "club": {
        "id": 2,
        "name": "Top Star Атриум"
      },
      "is_busy": true,
      "active_session": {
        "id": 123,
        "client": {
          "id": 45,
          "name": "Иван Иванов"
        },
        "started_at": "2026-05-20 10:30:00"
      },
      "created_at": "2026-05-20 10:00:00",
      "updated_at": "2026-05-20 10:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1
  }
}
```

### POST `/api/v1/trinkets`

Создает ключ.

Request:

```json
{
  "code": "7555530",
  "cabinet_number": "1Ж",
  "club_id": 2
}
```

Validation:

- `code`: required, string, max 255, unique within `trinkets.code`.
- `cabinet_number`: required, string, max 255.
- `club_id`: required, integer, exists in `clubs.id`.
- Для не boss-пользователя `club_id` должен совпадать с `auth()->user()->club_id`.
- Пара `club_id + cabinet_number` должна быть уникальной, чтобы в одном клубе не было двух одинаковых шкафчиков.

Success response:

```json
{
  "trinket": {
    "id": 1,
    "code": "7555530",
    "cabinet_number": "1Ж",
    "club": {
      "id": 2,
      "name": "Top Star Атриум"
    },
    "is_busy": false
  },
  "message": "Ключ успешно добавлен!"
}
```

### GET `/api/v1/trinkets/{trinket}`

Возвращает один ключ с клубом, текущей активной сессией, если ключ занят, и полной историей выдачи этого ключа.

История нужна для расследования потерь ключей: администратор должен видеть, кто из клиентов и когда получал ключ, кто из сотрудников начал/завершил сессию, и когда ключ был возвращен.

> Если во фронтенде или старом API будет использоваться singular path `/api/v1/trinket/{trinket}`, он должен быть либо алиасом этого endpoint, либо явно заменен на canonical `/api/v1/trinkets/{trinket}`.

Response должен включать:

- основные поля ключа;
- `club`;
- `is_busy`;
- `active_session`, если есть;
- `history`: список всех сессий по ключу, отсортированный от новых к старым.

History item:

```json
{
  "id": 123,
  "client": {
    "id": 45,
    "name": "Иван Иванов"
  },
  "club": {
    "id": 2,
    "name": "Top Star Атриум"
  },
  "start_user": {
    "id": 7,
    "name": "Администратор"
  },
  "finish_user": {
    "id": 8,
    "name": "Администратор 2"
  },
  "started_at": "2026-05-20 10:30:00",
  "finished_at": "2026-05-20 12:05:00",
  "duration": "1ч 35м"
}
```

Технические требования:

- Историю грузить через связь `sessions` с `client`, `club`, `start_user`, `finish_user`.
- Для первой версии допустимо отдавать последние `100` записей истории в `show`, чтобы не перегружать ответ на старых ключах.
- Если нужна полная история без ограничения, добавить query parameter `history_limit=all`; по умолчанию использовать `100`.
- Не включать историю в `index`, чтобы список ключей оставался легким.

Правила доступа:

- Boss может получить любой ключ.
- Не boss может получить только ключ своего клуба.

### PUT/PATCH `/api/v1/trinkets/{trinket}`

Редактирует `code`, `cabinet_number`, `club_id`.

Request:

```json
{
  "code": "7555530",
  "cabinet_number": "2Ж",
  "club_id": 2
}
```

Validation:

- Те же правила, что при создании.
- Unique-проверки должны игнорировать текущий `trinket.id`.
- Если ключ занят (`active_session` существует), разрешить менять только `cabinet_number`. Изменение `code` или `club_id` занятого ключа запретить, чтобы не сломать текущую сессию и `clients.cached_trinket`.

Ошибка для занятого ключа:

```json
{
  "message": "Нельзя изменить код или клуб занятого ключа."
}
```

### DELETE `/api/v1/trinkets/{trinket}`

Удаляет ключ.

Правила:

- Запретить удаление, если у ключа есть `active_session`.
- Если у ключа есть исторические завершенные `sessions`, допустимые варианты:
  - предпочтительно запретить hard delete и вернуть ошибку;
  - либо реализовать soft delete через отдельную миграцию.
- Для первой версии выбрать запрет удаления при наличии любых `sessions`, чтобы не нарушить историю посещений.

Ошибка:

```json
{
  "message": "Нельзя удалить ключ, который используется в истории посещений."
}
```

## Модель данных и миграции

Нужно добавить миграцию с индексами:

- unique index на `code`;
- unique composite index на `club_id, cabinet_number`;
- index на `club_id`, если текущий индекс отсутствует или требует уточнения.

Перед добавлением unique-индексов проверить текущие production-данные на дубли:

```sql
SELECT code, COUNT(*) FROM trinkets GROUP BY code HAVING COUNT(*) > 1;
SELECT club_id, cabinet_number, COUNT(*) FROM trinkets GROUP BY club_id, cabinet_number HAVING COUNT(*) > 1;
```

Если дубли есть, сначала подготовить отдельный cleanup-план и не накатывать unique-индексы вслепую.

## Рекомендуемые файлы реализации

- `app/Http/Controllers/api/v1/TrinketController.php`
- `app/Http/Requests/Trinket/StoreTrinketRequest.php`
- `app/Http/Requests/Trinket/UpdateTrinketRequest.php`
- `app/Http/Resources/Trinket/TrinketResource.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_add_unique_indexes_to_trinkets_table.php`
- `routes/api.php`
- `tests/Feature/Trinket/TrinketCrudTest.php`

## Архитектурные решения

- Использовать REST-style `Route::apiResource`, как в существующих API ресурсах.
- Возвращать `JsonResource`, чтобы не отдавать Eloquent-модель с лишними связями.
- Не выносить отдельный service layer для первой версии: CRUD простой, бизнес-ограничения помещаются в controller/request.
- Не менять `SessionController::attach`; он должен продолжить искать ключ по `code`.

## Ошибки и формат ответов

Использовать существующие helper-ответы из `ApiController`/`ReturnsJsonResponse`, если controller наследуется от `ApiController`.

Обязательные статусы:

- `200`: успешный `index`, `show`, `update`.
- `201` или текущий проектный `200`: успешный `store`; выбрать существующий стиль проекта.
- `204` или текущий проектный `200`: успешный `destroy`; выбрать существующий стиль проекта.
- `403`: доступ к чужому клубу.
- `422`: ошибки валидации.
- `409` или текущий project-style error response: попытка удалить/изменить занятый ключ.

## Тестовые сценарии

Feature tests:

- Boss видит ключи всех клубов.
- Не boss видит только ключи своего клуба.
- Создание ключа с валидными данными.
- Нельзя создать ключ с дублирующимся `code`.
- Нельзя создать ключ с дублирующимся `cabinet_number` в рамках одного клуба.
- Можно иметь одинаковый `cabinet_number` в разных клубах, если это принято бизнесом.
- Не boss не может создать ключ в чужом клубе.
- Редактирование свободного ключа.
- Нельзя изменить `code` или `club_id` занятого ключа.
- Нельзя удалить ключ с активной сессией.
- Нельзя удалить ключ с историческими сессиями.
- Можно удалить ключ без сессий.
- `GET /api/v1/trinkets/{trinket}` возвращает историю выдачи ключа с клиентом, клубом, сотрудником начала, сотрудником завершения, временем начала и временем завершения.
- `GET /api/v1/economy/my/keys` продолжает работать после добавления CRUD.
- `POST /api/v1/session/attach/{client}` продолжает находить ключ по `code`.

## Acceptance checklist

- В `routes/api.php` добавлен `Route::apiResource('trinkets', TrinketController::class)` внутри `auth:api`.
- CRUD endpoints работают под JWT auth.
- Ответы не раскрывают лишние поля модели.
- Валидация покрывает `code`, `cabinet_number`, `club_id`.
- Не boss ограничен своим клубом.
- Удаление и критичные изменения занятых/исторических ключей запрещены.
- `show` endpoint помогает расследовать потери ключей: показывает историю всех выдач ключа или последние `100` записей с возможностью запросить полную историю.
- Индексы не ломают production-данные; дубли проверены до миграции.
- Feature tests проходят в Docker PHP 7.4.
- Существующие сценарии выдачи ключа клиенту не регрессировали.
