# Ghost ModSync — Initial Project Audit (`docs/01-project-audit.md`)

**Date:** 2026-11-09  
**Role:** Senior Laravel Software Architect & Lead Backend Engineer  
**Scope:** Inspection-only baseline architectural, dependency, authentication, convention, and security audit of the Ghost ModSync repository (`c:/xampp/htdocs/ghostModSync`).

---

## 1. Executive Summary

### Verified Observations
- **Current Framework & Runtime:** The repository is a freshly scaffolded **Laravel 12** (`laravel/framework` `v12.71.0`) application with **Laravel Breeze** (`laravel/breeze` `v2.4.2`, Blade + Alpine.js + Tailwind CSS stack) installed. The local CLI PHP runtime is **PHP 8.2.12** (`C:\xampp\php\php.exe`).
- **Critical Version Mismatch vs. Intended Stack:** While the intended stack specifies **Laravel 13**, Laravel 13 (`laravel/framework` `^13.0`) strictly requires **PHP >= 8.3** (dropping PHP 8.2 support completely). The current XAMPP PHP 8.2.12 runtime cannot run Laravel 13 without a PHP runtime upgrade to PHP 8.3 or 8.4.
- **Application State:** Only the stock Laravel 12 skeleton and Laravel Breeze authentication/profile scaffolding exist. Zero domain models, migrations, controllers, policies, or services for Ghost ModSync (groups, VTCs, mods, versions, Steam Workshop references, convoy mod profiles) have been created yet.
- **Git Working Tree:** All 88 project files are currently staged (`git status` shows `A` for all files) with no initial commit yet recorded.

### Technical Assumptions
- The project is in its initial bootstrap phase prior to domain implementation, and the team intends either to upgrade the local XAMPP PHP environment to PHP 8.3+ for Laravel 13, or to proceed on Laravel 12.x if locked to PHP 8.2.
- The future C#/.NET Windows desktop client will act as a first-party native consumer of versioned REST API endpoints (`/api/v1/...`) to authenticate users, fetch convoy/group mod manifests, and download mod archives from S3-compatible object storage via pre-signed URLs.

### Recommendations
1. **Resolve the PHP / Laravel Version Decision Immediately:** Upgrade the local development and target deployment PHP runtime to **PHP 8.3 or 8.4** before upgrading `laravel/framework` to `^13.0`.
2. **Preserve Existing Breeze Scaffolding:** Retain all existing Breeze controllers, requests, routes, and Blade views for browser authentication, and layer `laravel/sanctum` alongside Breeze via `php artisan install:api` without replacing web authentication.
3. **Commit the Baseline:** Create an initial Git commit of the clean Breeze skeleton before introducing domain or package changes.

### Unresolved Questions
1. Can the local XAMPP environment (and target production server) be upgraded from PHP 8.2.12 to PHP 8.3/8.4 immediately to support Laravel 13, or should the project remain on Laravel 12 (`^12.0`)?
2. Which S3-compatible provider (AWS S3, Cloudflare R2, MinIO, Backblaze B2, DigitalOcean Spaces) will host the mod archives in development and production?

---

## 2. Existing Technology Stack

### Verified Observations

#### Backend & Composer Dependencies ([composer.json](file:///c:/xampp/htdocs/ghostModSync/composer.json) / [composer.lock](file:///c:/xampp/htdocs/ghostModSync/composer.lock))
| Component / Package | Constraint in `composer.json` | Installed (`composer.lock`) | Status / Notes |
| :--- | :--- | :--- | :--- |
| **PHP Runtime (CLI)** | `^8.2` | `8.2.12` (ZTS Visual C++ 2019 x64) | **Incompatible with Laravel 13** (which requires PHP `>=8.3`). |
| `laravel/framework` | `^12.0` | `v12.71.0` | Laravel 12 installed, not Laravel 13. |
| `laravel/tinker` | `^2.10.1` | `v2.11.1` | Installed. |
| `laravel/breeze` (dev) | `^2.4` | `v2.4.2` | Installed; supports `illuminate/*` `^11.0\|^12.0\|^13.0`. |
| `laravel/pint` (dev) | `^1.24` | `v1.30.4` | Installed (code style fixer). |
| `laravel/pail` (dev) | `^1.2.2` | `v1.2.7` | Installed (log viewer). |
| `laravel/sail` (dev) | `^1.41` | `v1.69.0` | Installed. |
| `phpunit/phpunit` (dev) | `^11.5.50` | `v11.5.57` | Installed. |
| `nunomaduro/collision` (dev) | `^8.6` | `v8.9.5` | Installed. |
| `fakerphp/faker` (dev) | `^1.23` | `v1.24.1` | Installed. |
| `mockery/mockery` (dev) | `^1.6` | `v1.6.15` | Installed. |

#### Frontend & Node Dependencies ([package.json](file:///c:/xampp/htdocs/ghostModSync/package.json) / [package-lock.json](file:///c:/xampp/htdocs/ghostModSync/package-lock.json))
| Package | Constraint in `package.json` | Resolved (`package-lock.json`) | Notes |
| :--- | :--- | :--- | :--- |
| `vite` | `^7.0.7` | `7.3.7` | Installed and configured in [vite.config.js](file:///c:/xampp/htdocs/ghostModSync/vite.config.js). |
| `laravel-vite-plugin` | `^2.0.0` | `2.1.0` | Configured for `resources/css/app.css` and `resources/js/app.js`. |
| `tailwindcss` | `^3.1.0` | `3.4.19` | Active via [postcss.config.js](file:///c:/xampp/htdocs/ghostModSync/postcss.config.js) and [tailwind.config.js](file:///c:/xampp/htdocs/ghostModSync/tailwind.config.js). |
| `@tailwindcss/vite` | `^4.0.0` | `4.3.5` | **Redundant/Conflicting:** Left over from Laravel 12 skeleton before Breeze installed Tailwind v3 PostCSS. Not imported in [vite.config.js](file:///c:/xampp/htdocs/ghostModSync/vite.config.js). |
| `@tailwindcss/forms` | `^0.5.2` | `0.5.11` | Used in [tailwind.config.js](file:///c:/xampp/htdocs/ghostModSync/tailwind.config.js). |
| `alpinejs` | `^3.4.2` | `3.17.5` | Initialized in [resources/js/app.js](file:///c:/xampp/htdocs/ghostModSync/resources/js/app.js). |
| `axios` | `^1.11.0` | `1.21.0` | Configured in [resources/js/bootstrap.js](file:///c:/xampp/htdocs/ghostModSync/resources/js/bootstrap.js). |
| `postcss` / `autoprefixer` | `^8.4.31` / `^10.4.2` | `8.5.32` / `10.6.2` | Active in [postcss.config.js](file:///c:/xampp/htdocs/ghostModSync/postcss.config.js). |
| `concurrently` | `^9.0.1` | `9.2.5` | Used by `composer run dev`. |

### Technical Assumptions
- The frontend will remain server-rendered Blade with Alpine.js and Tailwind CSS v3 as scaffolded by Breeze.

### Recommendations
- Remove the unused `@tailwindcss/vite` (`^4.0.0`) dependency from [package.json](file:///c:/xampp/htdocs/ghostModSync/package.json) during cleanup so there is no ambiguity between Tailwind v3 (PostCSS) and Tailwind v4 (Vite plugin).

### Unresolved Questions
- Will the web UI stay pure Blade + Alpine.js for complex mod load-order sorting and manifest management, or is Livewire / SortableJS planned?

---

## 3. Existing Application Structure

### Verified Observations
The codebase uses the streamlined **Laravel 11/12/13 application structure**:
- **Application Bootstrap:** [bootstrap/app.php](file:///c:/xampp/htdocs/ghostModSync/bootstrap/app.php) configures routing (`web: routes/web.php`, `commands: routes/console.php`, `health: '/up'`), middleware, and exception handling. No `api` route file is registered yet.
- **Service Providers:** [bootstrap/providers.php](file:///c:/xampp/htdocs/ghostModSync/bootstrap/providers.php) registers only [`AppServiceProvider`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php), which currently has empty `register()` and `boot()` methods.
- **Models (`app/Models`):** Only [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php) exists. No domain models or Eloquent relationships exist yet.
- **Controllers (`app/Http/Controllers`):**
  - Base abstract [`Controller`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Controller.php) (empty abstract class).
  - [`ProfileController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/ProfileController.php) (`edit`, `update`, `destroy`).
  - 9 Breeze authentication controllers under `app/Http/Controllers/Auth/`:
    - [`AuthenticatedSessionController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/AuthenticatedSessionController.php)
    - [`ConfirmablePasswordController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/ConfirmablePasswordController.php)
    - [`EmailVerificationNotificationController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/EmailVerificationNotificationController.php)
    - [`EmailVerificationPromptController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/EmailVerificationPromptController.php)
    - [`NewPasswordController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/NewPasswordController.php)
    - [`PasswordController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/PasswordController.php)
    - [`PasswordResetLinkController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/PasswordResetLinkController.php)
    - [`RegisteredUserController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/RegisteredUserController.php)
    - [`VerifyEmailController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Auth/VerifyEmailController.php)
- **Form Requests (`app/Http/Requests`):**
  - [`LoginRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Auth/LoginRequest.php) (handles login validation and rate limiting: 5 attempts per email+IP).
  - [`ProfileUpdateRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/ProfileUpdateRequest.php) (validates `name` and unique `email`).
  - Note: Other auth controllers validate inline using `$request->validate()` or `$request->validateWithBag()`.
- **Middleware:** No custom middleware directory (`app/Http/Middleware`) exists; all middleware is configured through the framework defaults in [bootstrap/app.php](file:///c:/xampp/htdocs/ghostModSync/bootstrap/app.php).
- **Routes (`routes/`):**
  - [routes/web.php](file:///c:/xampp/htdocs/ghostModSync/routes/web.php): Defines `/`, `/dashboard` (`auth`, `verified`), `/profile` CRUD (`auth`), and requires `routes/auth.php`.
  - [routes/auth.php](file:///c:/xampp/htdocs/ghostModSync/routes/auth.php): Defines `guest` and `auth` route groups for Breeze.
  - [routes/console.php](file:///c:/xampp/htdocs/ghostModSync/routes/console.php): Default `inspire` command.
  - Missing: `routes/api.php` (not yet installed).
- **Blade Views (`resources/views`):**
  - Layouts: [`AppLayout`](file:///c:/xampp/htdocs/ghostModSync/app/View/Components/AppLayout.php) ([resources/views/layouts/app.blade.php](file:///c:/xampp/htdocs/ghostModSync/resources/views/layouts/app.blade.php)), [`GuestLayout`](file:///c:/xampp/htdocs/ghostModSync/app/View/Components/GuestLayout.php) ([resources/views/layouts/guest.blade.php](file:///c:/xampp/htdocs/ghostModSync/resources/views/layouts/guest.blade.php)), and [resources/views/layouts/navigation.blade.php](file:///c:/xampp/htdocs/ghostModSync/resources/views/layouts/navigation.blade.php).
  - 13 anonymous Blade UI components in `resources/views/components/` (buttons, inputs, modal, dropdown, nav links, logo).
  - Auth views in `resources/views/auth/` and profile views in `resources/views/profile/`.
- **Absent Architectural Layers:**
  - No `app/Services/` or `app/Actions/` directory.
  - No `app/Policies/` directory or gate definitions.
  - No `app/Http/Resources/` directory (API Resources).
  - No `app/Enums/` directory.
- **Tests (`tests/`):**
  - Feature tests for all Breeze auth flows (`tests/Feature/Auth/*`) and profile management ([tests/Feature/ProfileTest.php](file:///c:/xampp/htdocs/ghostModSync/tests/Feature/ProfileTest.php)).
  - Configured via [phpunit.xml](file:///c:/xampp/htdocs/ghostModSync/phpunit.xml) to use in-memory SQLite (`:memory:`).

### Technical Assumptions
- As Ghost ModSync grows to support Web (Blade) and Desktop Client (API) workflows, business logic shared between Web Controllers and API Controllers (such as mod archive hashing, S3 pre-signed URL generation, load-order validation, and group membership verification) will need dedicated Service/Action classes to avoid duplication.

### Recommendations
- Introduce a structured domain layer before building features:
  - `app/Enums/` for strongly typed PHP 8.2+ backed enums (`GameType` (`ets2`, `ats`), `GroupVisibility` (`public`, `private`), `GroupRole`, `ModSourceType` (`upload`, `steam_workshop`)).
  - `app/Services/` (or `app/Actions/`) for shared domain operations.
  - `app/Policies/` for multi-tenant group/VTC authorization (`GroupPolicy`, `ModPolicy`, `ModProfilePolicy`).
  - `app/Http/Controllers/Api/V1/` and `app/Http/Resources/V1/` for the C# desktop client REST API.
  - Extract inline controller validation in auth/domain controllers into dedicated `FormRequest` classes.

### Unresolved Questions
- Does the team prefer Action classes (single-purpose classes per use case) or domain Service classes (`GroupService`, `ModSyncService`, `StorageService`)?

---

## 4. Existing Authentication Capabilities & Compatibility (Task 2)

### Verified Observations
1. **Browser-Based Registration, Login, Logout, Password Reset, and Authenticated Blade Pages:**
   - **Fully implemented and tested** via Laravel Breeze (`v2.4.2`) using the stateful `web` session guard ([config/auth.php](file:///c:/xampp/htdocs/ghostModSync/config/auth.php)).
2. **Email Verification Incomplete Activation:**
   - Routes (`verification.notice`, `verification.verify`, `verification.send`), controllers, and views exist, and `/dashboard` applies the `verified` middleware ([routes/web.php](file:///c:/xampp/htdocs/ghostModSync/routes/web.php#L10-L12)).
   - **However**, in [`User.php`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php#L5-L11), `use Illuminate\Contracts\Auth\MustVerifyEmail;` is commented out and `class User extends Authenticatable` does **not** implement `MustVerifyEmail`. Consequently, newly registered users are not required to verify their email address before accessing `/dashboard`.
3. **Laravel Sanctum & C# Desktop Client Authentication:**
   - `laravel/sanctum` is **not currently installed** in [composer.json](file:///c:/xampp/htdocs/ghostModSync/composer.json).
   - `HasApiTokens` trait is not yet added to [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
   - `personal_access_tokens` migration and `routes/api.php` do not yet exist.
4. **Compatibility with Existing Breeze Setup & Laravel 13:**
   - **Coexistence:** Laravel Breeze (`web` guard using session cookies) and Laravel Sanctum (`sanctum` guard using Bearer Personal Access Tokens) are designed to work side-by-side on the same [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php) model without altering or replacing Breeze.
   - **How the C#/.NET Desktop App Will Authenticate:**
     1. Install Sanctum (`php artisan install:api`) and add `Laravel\Sanctum\HasApiTokens` to [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
     2. Create dedicated API token endpoints (e.g., `POST /api/v1/auth/login` or device token exchange, and `POST /api/v1/auth/logout`) that validate credentials (and email verification status if required) and return `$user->createToken($deviceName, ['mods:read', 'profiles:sync'])->plainTextToken`.
     3. The C# desktop client stores the token securely (e.g., Windows DPAPI / Credential Manager) and sends `Authorization: Bearer <token>` and `Accept: application/json` headers on requests to `auth:sanctum`-protected `/api/v1/*` routes.
   - **Laravel 13 Package Compatibility Verification:**
     - `laravel/breeze` `v2.4.2` explicitly supports `illuminate/support` `^11.0|^12.0|^13.0` in its `composer.json`.
     - `laravel/sanctum` `^4.0` supports Laravel 11, 12, and 13 (`illuminate/support` `^11.0|^12.0|^13.0`).
     - **Blocker:** `laravel/framework` `^13.0` requires **PHP `^8.3`**. Attempting to upgrade to Laravel 13 on the current PHP `8.2.12` environment will fail Composer platform checks.

### Technical Assumptions
- The C#/.NET Windows client will authenticate either via a standard email/password + device-name login endpoint returning a Sanctum Personal Access Token (PAT) or via a browser-initiated device-code / OAuth-like handoff that issues a Sanctum PAT.

### Recommendations
- Uncomment and implement `MustVerifyEmail` on [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php) if email verification is mandatory.
- Install `laravel/sanctum` (`^4.0`) via `php artisan install:api` once the PHP version / Laravel version decision is finalized, and add `HasApiTokens` to [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
- Scope desktop client tokens using Sanctum token abilities (e.g., `sync:read`, `mods:download`) and configure token expiration in `config/sanctum.php`.

### Unresolved Questions
- Should C# desktop client users log in directly inside the C# app with email/password (or 2FA), or authenticate via the browser and authorize the desktop client via a one-time pairing code / custom URI scheme (`ghostmodsync://`)?
- Should unverified users be blocked from both web dashboard and desktop client API synchronization?

---

## 5. Database Status

### Verified Observations
- **Environment Configuration (`c:/xampp/htdocs/ghostModSync/.env` vs [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example)):**
  - `.env` is configured for MySQL: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=ghostModsSync`, `DB_USERNAME=root`, and an empty password.
  - [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example) still defaults to `DB_CONNECTION=sqlite` with MySQL variables commented out.
  - Note: `DB_DATABASE=ghostModsSync` in `.env` uses plural `Mods` (`ghostModsSync`), whereas the workspace folder and project name are singular (`ghostModSync`).
- **Residual SQLite Database File:**
  - `database/database.sqlite` exists on disk because `composer create-project` ran `@php artisan migrate --graceful` against SQLite before `.env` was switched to MySQL. It contains the initial 9 framework tables (`migrations`, `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`).
- **Existing Migrations (`database/migrations/`):**
  1. [0001_01_01_000000_create_users_table.php](file:///c:/xampp/htdocs/ghostModSync/database/migrations/0001_01_01_000000_create_users_table.php) (`users`, `password_reset_tokens`, `sessions`)
  2. [0001_01_01_000001_create_cache_table.php](file:///c:/xampp/htdocs/ghostModSync/database/migrations/0001_01_01_000001_create_cache_table.php) (`cache`, `cache_locks`)
  3. [0001_01_01_000002_create_jobs_table.php](file:///c:/xampp/htdocs/ghostModSync/database/migrations/0001_01_01_000002_create_jobs_table.php) (`jobs`, `job_batches`, `failed_jobs`)
- **Missing Domain Schema:** No tables exist yet for VTCs/groups, group memberships/roles, mods, mod versions/files, Steam Workshop metadata, or convoy mod profiles/load orders.

### Technical Assumptions
- MySQL (via XAMPP MariaDB/MySQL on `127.0.0.1:3306`) is the primary relational database for development and production.

### Recommendations
- Update [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example) to reflect `DB_CONNECTION=mysql` and `DB_DATABASE=ghost_mod_sync` (or `ghostModSync`) and placeholder S3 endpoint variables so new developer environments match the intended stack.
- Remove the unused `database/database.sqlite` file once MySQL migrations are verified.
- Follow strict Laravel database naming conventions for upcoming migrations: plural `snake_case` table names (`groups`, `group_user`, `mods`, `mod_versions`, `convoy_profiles`, `convoy_profile_mod`), singular `snake_case` foreign keys (`group_id`, `mod_id`) with `->constrained()->cascadeOnDelete()`, and composite unique indexes on pivot tables.

### Unresolved Questions
- Has the `ghostModsSync` database been created in your local XAMPP MySQL server yet, and have the 3 baseline migrations been executed against MySQL?

---

## 6. Existing Reusable Components

### Verified Observations
The following components are already installed, cleanly structured, and ready for reuse:
- **Authentication & Profile Management:** Full Breeze controller suite (`app/Http/Controllers/Auth/*`, [`ProfileController`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/ProfileController.php)) and feature test suite (`tests/Feature/Auth/*`, [tests/Feature/ProfileTest.php](file:///c:/xampp/htdocs/ghostModSync/tests/Feature/ProfileTest.php)).
- **Blade Layout System:** [`AppLayout`](file:///c:/xampp/htdocs/ghostModSync/app/View/Components/AppLayout.php), [`GuestLayout`](file:///c:/xampp/htdocs/ghostModSync/app/View/Components/GuestLayout.php), and responsive top navigation ([resources/views/layouts/navigation.blade.php](file:///c:/xampp/htdocs/ghostModSync/resources/views/layouts/navigation.blade.php)) with dark mode utility classes (`dark:*`).
- **13 Blade UI Primitives (`resources/views/components/`):**
  - `primary-button`, `secondary-button`, `danger-button`
  - `text-input`, `input-label`, `input-error`
  - `modal`, `dropdown`, `dropdown-link`
  - `nav-link`, `responsive-nav-link`
  - `application-logo`, `auth-session-status`
- **Database Queue & Cache Infrastructure:** Migrations for `jobs`, `job_batches`, `failed_jobs`, `cache`, and `sessions` are present and configured in `.env`.

### Technical Assumptions
- The existing Blade components will serve as the base design system for building Group, VTC, Mod Repository, and Convoy Profile management screens.

### Recommendations
- Extend the existing `resources/views/components/` library with domain-specific components (e.g., `<x-badge>`, `<x-card>`, `<x-game-selector>`, `<x-file-dropzone>`) rather than introducing a second competing UI framework.

### Unresolved Questions
- None.

---

## 7. Missing Dependencies

### Verified Observations
 Comparing the installed packages in [composer.json](file:///c:/xampp/htdocs/ghostModSync/composer.json) against the stated Ghost ModSync technical stack reveals the following missing dependencies:

1. **`laravel/sanctum` (`^4.0`):**
   - Required for REST API and C#/.NET Windows desktop client authentication.
   - Currently absent from `composer.json`.
2. **`league/flysystem-aws-s3-v3` (`^3.0`):**
   - Required by Laravel's `s3` filesystem disk ([config/filesystems.php](file:///c:/xampp/htdocs/ghostModSync/config/filesystems.php#L50-L61)) to interact with S3-compatible object storage and generate pre-signed upload/download URLs.
   - Currently absent from `composer.json` (attempting to use `Storage::disk('s3')` without it will throw a missing class exception).
3. **PHP >= 8.3 Runtime & `laravel/framework: ^13.0`:**
   - Required to meet the "Laravel 13" specification. Currently running PHP 8.2.12 and `laravel/framework: ^12.0`.

### Technical Assumptions
- Steam Workshop metadata resolution will use Laravel's built-in `Illuminate\Support\Facades\Http` client (Guzzle, already included in `laravel/framework`) against the Steam Web API (`ISteamRemoteStorage/GetPublishedFileDetails/v1/`), so no third-party Steam Composer package is strictly required unless Steam OpenID login is desired.

### Recommendations
- Install `laravel/sanctum` via `php artisan install:api`.
- Install `league/flysystem-aws-s3-v3` (`composer require league/flysystem-aws-s3-v3 "^3.0"`).
- Enable `throw => true` on the `s3` disk in [config/filesystems.php](file:///c:/xampp/htdocs/ghostModSync/config/filesystems.php#L59) so storage failures surface immediately in logs and queue jobs instead of failing silently.

### Unresolved Questions
- Will users link their Steam accounts via Steam OpenID / OAuth, or are Steam Workshop items referenced solely by Workshop URL / PublishedFileId?

---

## 8. Security Findings (Task 4)

### Verified Observations
No credentials or secret values are printed in this report.

1. **Environment Secrets & Git Hygiene (SAFE):**
   - [.gitignore](file:///c:/xampp/htdocs/ghostModSync/.gitignore) properly excludes `.env`, `.env.backup`, `.env.production`, `/auth.json`, and `/storage/*.key`.
   - `git status` confirms `.env` is untracked and not staged.
   - In `.env`, `APP_KEY` is populated (`[SET]`), while `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_PASSWORD`, `AWS_ACCESS_KEY_ID`, and `AWS_SECRET_ACCESS_KEY` are currently empty/null.
2. **Email Verification Bypass (MEDIUM RISK):**
   - Although `/dashboard` uses the `verified` middleware, [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php) does not implement `MustVerifyEmail`. Lookups to `$user->hasVerifiedEmail()` via the `EnsureEmailIsVerified` middleware only enforce verification when the model implements `MustVerifyEmail`.
3. **Mass Assignment Protection (SAFE NOW, NEEDS STRICT MODE FOR DOMAIN MODELS):**
   - [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php#L21-L25) explicitly defines `$fillable = ['name', 'email', 'password']` and `$hidden = ['password', 'remember_token']`.
   - [`AppServiceProvider`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php) does not yet call `Model::shouldBeStrict(! $this->app->isProduction())` to catch unguarded mass assignment or N+1 lazy loading during development.
4. **Authentication & Password Handling (SAFE):**
   - Passwords use bcrypt (`BCRYPT_ROUNDS=12` in `.env`) and the `'password' => 'hashed'` cast on [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php#L46).
   - Login rate limiting is enforced in [`LoginRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Auth/LoginRequest.php#L61-L77) (5 attempts per lowercase email + IP).
   - Session fixation is prevented via `$request->session()->regenerate()` on login and `$request->session()->invalidate()` + `regenerateToken()` on logout and account deletion.
5. **CSRF Protection (SAFE):**
   - Enabled by default for all `web` routes via Laravel 12's `ValidateCsrfToken` middleware, and `@csrf` is present in all Breeze POST/PATCH/PUT/DELETE forms.
6. **File Upload & Object Storage Security (ARCHITECTURAL RISK FOR UPCOMING MOD FEATURES):**
   - ETS2/ATS `.scs` and `.zip` mod archives regularly range from **50 MB to 5+ GB**.
   - Routing multi-gigabyte uploads or downloads through PHP-FPM/Apache (`c:/xampp`) will exhaust `upload_max_filesize`, `post_max_size`, `max_execution_time`, and server memory, creating a trivial Denial-of-Service (DoS) vector.
   - Furthermore, `.scs` and `.zip` archives must be validated for file extension, MIME type, SHA-256 checksum integrity, and path traversal (zip-slip) if inspected server-side.
7. **Multi-Group Authorization Isolation (ARCHITECTURAL RISK FOR UPCOMING FEATURES):**
   - No `Policies` or role checks exist yet. Because Ghost ModSync will host **private groups and proprietary VTC mods**, Insecure Direct Object Reference (IDOR) vulnerabilities on mod download endpoints or convoy profile endpoints are the highest upcoming security risk.

### Technical Assumptions
- Mod files stored in S3-compatible storage for private groups must remain in a **private bucket** (never public-read) and be accessed exclusively via short-lived S3 pre-signed URLs issued after Laravel verifies the requesting user's group membership.

### Recommendations
1. Enable `Model::shouldBeStrict(! $this->app->isProduction())` in [`AppServiceProvider::boot()`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php).
2. Configure `Password::defaults()` in [`AppServiceProvider::boot()`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php) (e.g., minimum 8–12 characters, mixedCase, numbers, uncompromised check in production).
3. Use **S3 Pre-Signed URLs (`Storage::disk('s3')->temporaryUrl(...)` and `temporaryUploadUrl(...)`)** for all mod uploads and desktop client downloads so binary traffic flows directly between the client and S3 after Laravel authorizes the request.
4. Enforce Laravel Policies (`$this->authorize(...)` / `can` middleware) on every Group, Mod, and Convoy Profile controller action (both Web and API).
5. Store SHA-256 hashes and exact byte sizes for every mod version in the database so the C# desktop client can verify downloaded files before placing them into the user's `Documents\Euro Truck Simulator 2\mod` or `Documents\American Truck Simulator\mod` folder.

### Unresolved Questions
- Will mod uploads be performed solely via the web browser (using multipart/pre-signed S3 uploads), or will group admins also upload mods from the C# desktop application?

---

## 9. Technical Risks

### Verified Observations & Architectural Risk Matrix
| Risk ID | Risk Description | Severity | Category |
| :--- | :--- | :--- | :--- |
| **RISK-01** | **PHP 8.2.12 vs. Laravel 13 Incompatibility:** Laravel 13 requires PHP `>=8.3`. The current XAMPP CLI runs PHP `8.2.12`. | **High (Blocker)** | Environment / Stack |
| **RISK-02** | **PHPUnit Execution Path Bug on Windows:** Running `php artisan test` via XAMPP PHP 8.2.12 failed with `Cannot open bootstrap script "C:\xampp\htdocs\ghostModSync\vendor/autoload.php"` due to mixed directory separators or process runner working directory resolution on Windows. | **Medium** | Testing / DX |
| **RISK-03** | **Large Binary Mod Transfers via PHP:** Proxying multi-hundred-megabyte `.scs`/`.zip` ETS2/ATS mods through PHP memory/workers instead of S3 pre-signed URLs will cause timeouts and memory exhaustion. | **High** | Scalability / Storage |
| **RISK-04** | **Dual Tailwind Build Packages:** Both `tailwindcss` (`^3.1.0`) and `@tailwindcss/vite` (`^4.0.0`) are in `package.json`, creating risk of broken styles if someone switches `vite.config.js` without migrating CSS directives. | **Low** | Frontend Build |
| **RISK-05** | **Database Name & Driver Drift:** `.env.example` specifies `sqlite`, while `.env` specifies `mysql` with `DB_DATABASE=ghostModsSync` (plural `Mods`), and `database/database.sqlite` still sits in `database/`. | **Low** | Configuration |

### Technical Assumptions
- Running `./vendor/bin/phpunit` directly or upgrading PHP will resolve `RISK-02`.

### Recommendations
- Address `RISK-01` and `RISK-05` before writing any migrations or domain code.
- Address `RISK-03` by designing the `ModVersion` schema and `StorageService` around S3 keys, SHA-256 hashes, file sizes, and temporary pre-signed URLs from day one.

### Unresolved Questions
- None.

---

## 10. Recommended Improvements

1. **Runtime & Framework Alignment:**
   - Upgrade local PHP to **PHP 8.3 or 8.4**, update [composer.json](file:///c:/xampp/htdocs/ghostModSync/composer.json) to `"php": "^8.3"` and `"laravel/framework": "^13.0"`, and run `composer update`.
2. **Complete Authentication Setup:**
   - Implement `MustVerifyEmail` on [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
   - Install Laravel Sanctum (`php artisan install:api`) and add `HasApiTokens` to [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
3. **Install & Configure S3 Storage:**
   - Require `league/flysystem-aws-s3-v3`.
   - Set `'throw' => true` on the `s3` disk in [config/filesystems.php](file:///c:/xampp/htdocs/ghostModSync/config/filesystems.php) and document `AWS_ENDPOINT` and `AWS_USE_PATH_STYLE_ENDPOINT` in [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example) for S3-compatible providers.
4. **Harden `AppServiceProvider`:**
   - Enable `Model::shouldBeStrict(! $this->app->isProduction())`.
   - Configure strong `Password::defaults()`.
   - Configure API rate limiters (`RateLimiter::for('api', ...)` and `RateLimiter::for('downloads', ...)`).
5. **Clean Up Frontend & Environment Config:**
   - Remove `@tailwindcss/vite` from [package.json](file:///c:/xampp/htdocs/ghostModSync/package.json).
   - Synchronize [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example) with the MySQL + S3 architecture and remove `database/database.sqlite`.

---

## 11. Recommended Implementation Order

Once you approve moving from audit to implementation, we recommend executing the following phased roadmap:

1. **Phase 1 — Environment, Framework & Baseline Commit:**
   - Create initial Git commit of the clean repository.
   - Align PHP runtime (`>=8.3`) and upgrade `laravel/framework` to `^13.0` (or confirm staying on Laravel 12.x if keeping PHP 8.2).
   - Clean up [.env.example](file:///c:/xampp/htdocs/ghostModSync/.env.example), remove `database/database.sqlite`, and remove unused `@tailwindcss/vite` from [package.json](file:///c:/xampp/htdocs/ghostModSync/package.json).
2. **Phase 2 — Core Packages & Authentication Hardening:**
   - Enable `MustVerifyEmail` on [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php).
   - Install `laravel/sanctum` (`php artisan install:api`) and `league/flysystem-aws-s3-v3`.
   - Harden [`AppServiceProvider`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php) (strict models, password defaults, rate limiters).
   - Verify MySQL migrations and PHPUnit test execution.
3. **Phase 3 — Domain Data Model & Migrations (Groups, VTCs, Memberships):**
   - Create backed Enums (`GameType`, `GroupVisibility`, `GroupRole`, `ModSourceType`).
   - Create migrations, models, factories, and policies for `Group` (supporting VTCs, public/private visibility) and `GroupMembership` (`group_user` pivot with roles and invitations).
4. **Phase 4 — Mod Repository & S3 / Steam Workshop Architecture:**
   - Create migrations, models, and policies for `Mod`, `ModVersion` (storing S3 object keys, file size, SHA-256 hash, target game version, or Steam Workshop `published_file_id`).
   - Implement `ModStorageService` for pre-signed S3 upload/download URLs.
5. **Phase 5 — Convoy Mod Profiles & Load Order Management:**
   - Create migrations, models, and policies for `ModProfile` (convoy profiles per game: ETS2/ATS) and `ModProfileItem` (ordered pivot with load-order priority and required/optional flags).
6. **Phase 6 — Web UI (Blade) & Versioned REST API (`/api/v1`) for C# Desktop Client:**
   - Build Blade controllers, FormRequests, and views for group, mod, and profile management.
   - Build `/api/v1` Sanctum auth endpoints, JSON `JsonResource` transformers, and sync manifest endpoints for the C# Windows client, backed by comprehensive Feature tests.
