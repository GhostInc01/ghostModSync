# Ghost ModSync — Coding Standards (`docs/07-coding-standards.md`)

**Date:** 2026-11-09  
**Role:** Lead Software Architect  
**Stack:** PHP 8.4, Laravel 13, Blade + Alpine.js + Tailwind CSS, MySQL, PHPUnit 12

---

## 1. PHP 8.4 & Laravel 13 Conventions

1. **Strict Types & Type Declarations:**
   - Declare `declare(strict_types=1);` at the top of all new PHP classes (`Enums`, `Models`, `Services`, `Policies`, `Controllers`, `Requests`, `Resources`, `Jobs`).
   - All method parameters, return types, and class properties must have explicit PHP 8.4 type hints, supplemented by PHPDoc generics (`@return Collection<int, ModVersion>`, `@use HasFactory<GroupFactory>`) where arrays or collections are returned.
2. **Code Formatting (Laravel Pint):**
   - All PHP code must conform to **PSR-12 / Laravel preset** enforced by `./vendor/bin/pint`.
3. **Backed Enums over Magic Strings:**
   - Never hardcode domain status or role strings (`'private'`, `'owner'`, `'ets2'`, `'s3_upload'`) across controllers or views. Always use PHP Backed Enums in `app/Enums/` and cast them in Eloquent models via the `casts(): array` method.

---

## 2. Architectural Layer Rules

### 2.1 Controllers (`app/Http/Controllers/Web` & `app/Http/Controllers/Api/V1`)
- Keep controllers **thin** (ideally under 15–20 lines per action).
- A controller action's only responsibilities are:
  1. Receive a validated `FormRequest` (and route-bound Eloquent models).
  2. Authorize the action via `$this->authorize(...)` (or `FormRequest::authorize()`).
  3. Delegate domain orchestration to a Service class in `app/Services/`.
  4. Return a typed response (`View`, `RedirectResponse`, or `JsonResource` / `JsonResponse`).
- **Separate Web and API Controllers:** Do not mix `request()->wantsJson()` branches inside Blade controllers. Place web controllers in `App\Http\Controllers\Web` and desktop API controllers in `App\Http\Controllers\Api\V1`.

### 2.2 Form Requests (`app/Http/Requests`)
- All `POST`, `PUT`, `PATCH`, and `DELETE` endpoints taking input must use a dedicated `FormRequest` class.
- Use array syntax for validation rules (`['required', 'string', 'max:150', Rule::enum(GameCode::class)]`), never pipe-delimited strings.

### 2.3 Domain Services (`app/Services`)
- Encapsulate multi-step business logic, S3 interactions, manifest hash computation, dependency graph cycle detection, and `DB::transaction(...)` blocks inside dedicated Service classes.
- Inject dependencies via constructor promotion (`public function __construct(private readonly AuditLoggerService $auditLogger) {}`).

### 2.4 Eloquent Models (`app/Models`)
- Explicitly define `protected $fillable = [...]` on every model.
- Use the Laravel 11/12/13 `protected function casts(): array` method rather than the legacy `$casts` property.
- Define return types on all relationship methods (`BelongsTo`, `HasMany`, `BelongsToMany`, `HasOne`).
- Keep `Model::shouldBeStrict(! $this->app->isProduction())` active so N+1 lazy loading is caught immediately during development and automated testing.

---

## 3. Database & Migration Standards

1. **Forward-Only Migrations:**
   - Every schema change must be created as a new migration file in `database/migrations/`.
   - Never edit a migration that has already been committed and shared, and **never run `migrate:fresh` or `migrate:reset` on shared or populated databases without explicit user approval**.
2. **Naming Conventions:**
   - Tables: plural `snake_case` (`convoy_profiles`, `group_memberships`).
   - Columns: singular `snake_case` (`file_size_bytes`, `sha256_hash`, `published_at`).
   - Foreign Keys: `{singular_model}_id` with explicit `->constrained()->cascadeOnDelete()` or `->restrictOnDelete()`.
   - Indexes: Explicitly index all columns used in `WHERE`, `JOIN`, and `ORDER BY` clauses (especially `(group_id, game_id)`, `sha256_hash`, and `(convoy_profile_id, load_order)`).

---

## 4. REST API v1 Design Standards (`/api/v1/*`)

To ensure clean integration with the **C# / .NET / WPF Windows Desktop Application**:
1. **URI Versioning:** Prefix all desktop client routes with `/api/v1/`.
2. **Consistent JSON Envelopes (`JsonResource`):**
   - Always transform models using `Illuminate\Http\Resources\Json\JsonResource` classes in `app/Http/Resources/V1/`.
   - Use `snake_case` for all JSON property keys (matching standard .NET `JsonNamingPolicy.SnakeCaseLower` in `System.Text.Json`).
   - Format all timestamps as ISO-8601 UTC strings (`2026-11-09T10:30:00Z`).
   - Represent file sizes as integer bytes (`file_size_bytes`) and hashes as 64-character lowercase hex (`sha256_hash`).
3. **Standard HTTP Status Codes & Error Format:**
   - `200 OK` / `201 Created` / `202 Accepted` / `204 No Content`
   - `401 Unauthorized` (missing/expired/revoked Sanctum token)
   - `403 Forbidden` (insufficient group role, private group, or unverified email)
   - `404 Not Found`
   - `409 Conflict` (manifest revision mismatch or dependency conflict)
   - `422 Unprocessable Entity` (validation errors)
   - `429 Too Many Requests`

---

## 5. Automated Testing Standards (PHPUnit 12)

1. **Test Coverage Expectations:**
   - Every new feature, controller endpoint (Web and API), policy rule, and domain service must include automated tests in `tests/Feature/` or `tests/Unit/`.
2. **Authorization & Multi-Tenant Isolation Tests:**
   - Every group-scoped feature test must verify both:
     - **Positive cases:** Authorized roles (`owner`, `admin`, `mod_manager`, `convoy_dispatcher`, `member` as applicable) succeed.
     - **Negative cases:** Non-members, members of *another* VTC/group, and lower-privilege roles in the same group receive `403 Forbidden` or `404 Not Found`.
3. **External Service Mocking:**
   - Use `Storage::fake('s3')` for S3 upload/download tests and `Http::fake()` for Steam Workshop API tests so the test suite runs deterministically offline in milliseconds.
4. **Running Tests:**
   - Execute tests via `php vendor/bin/phpunit` (or after fixing `phpunit.xml` bootstrap path to a relative path `vendor/autoload.php`).
