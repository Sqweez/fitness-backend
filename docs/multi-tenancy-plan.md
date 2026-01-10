# Multi-Tenancy Plan (DB-per-tenant, MySQL, X-Tenant-Key)

## Goals
- Keep tenant data isolated in separate MySQL databases.
- Identify tenant by `X-Tenant-Key` header on each request.
- Keep current production tenant as-is (no data migration), add infra for new tenants.
- Use `spatie/laravel-multitenancy` with custom tenant resolution and DB switching.

## Assumptions
- Laravel 8.x.
- One "landlord" database stores tenant metadata and API keys.
- Each tenant database has its own credentials.

## Phase 1: Landlord setup
- Add a new `landlord` connection in `config/database.php`.
- Add a `tenant` connection template in `config/database.php` (empty values, set at runtime).
- Create landlord migrations:
  - `tenants`: id, name, status, db_host, db_port, db_name, db_username, db_password_encrypted, created_at, updated_at.
  - `tenant_api_keys`: id, tenant_id, key_hash, name, last_used_at, revoked_at.
- Create `Tenant` model implementing `IsTenant` and using `UsesLandlordConnection`.

## Phase 2: Tenancy package setup
- Install `spatie/laravel-multitenancy`.
- Publish config and set:
  - `tenant_database_connection_name = tenant`
  - `landlord_database_connection_name = landlord`
  - `tenant_finder = App\Multitenancy\HeaderTenantFinder::class`
  - `tenant_model = App\Models\Tenant::class`
  - `switch_tenant_tasks` includes a custom DB switch task (below).

## Phase 3: Tenant resolution (X-Tenant-Key)
- Implement `HeaderTenantFinder`:
  - Read `X-Tenant-Key`.
  - Hash with a keyed hash (e.g. `hash_hmac('sha256', $key, config('app.key'))`).
  - Lookup in `tenant_api_keys` table (hash only).
  - Return the matched `Tenant` or `null` -> 401.
- Add middleware that enforces tenant context for API routes.

## Phase 4: Switching tenant DB with credentials
- Implement `SwitchTenantDatabaseWithCredentialsTask`:
  - `DB::purge('tenant')`
  - Set `database.connections.tenant` values for host/port/database/user/password.
  - `DB::reconnect('tenant')`
- Register the task in `switch_tenant_tasks`.

## Phase 5: Tenant migrations & seeds
- Split migrations:
  - Landlord migrations remain in `database/migrations`.
  - Tenant migrations move to `database/migrations/tenant`.
- Run tenant migrations via `tenants:artisan "migrate --path=database/migrations/tenant"`.
- Add optional tenant seed command with `tenants:artisan "db:seed --class=..."`

## Phase 6: Provisioning commands
- `tenant:create` command:
  - Create tenant database and user (MySQL).
  - Insert `tenants` row (store encrypted password).
  - Generate raw `X-Tenant-Key`, store hash, return raw key once.
  - Run tenant migrations and seeds.
- `tenant:rotate-key` (optional).
- `tenant:disable` to revoke access (optional).

## Phase 7: Jobs, queues, and scheduled tasks
- Ensure tenant context is attached to queued jobs and restored on execution.
- Mark global jobs as not tenant-aware where needed.

## Phase 8: Current tenant onboarding
- Create landlord DB and insert Tenant #1 pointing to the existing DB.
- Generate and distribute a tenant key for current API clients.
- No data migration required.

## Phase 9: Testing
- Feature tests:
  - Reject requests with missing/invalid `X-Tenant-Key`.
  - Resolve correct tenant and connect to correct DB.
  - Data isolation across two tenants (create A, ensure B cannot read).
- Smoke test: `tenant:create` provisions new DB and runs migrations.

## Rollout
- Stage: enable middleware in non-production, verify existing tenant works with key.
- Prod: create landlord DB, insert Tenant #1, deploy middleware, distribute key, monitor logs.
