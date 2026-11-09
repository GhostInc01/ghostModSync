# Ghost ModSync — Antigravity Workspace Rules (`AGENTS.md`)

These persistent project rules govern all AI agents operating inside `c:\xampp\htdocs\ghostModSync`.

## 1. Mandatory Pre-Task Context Loading
- **Read `PROJECT_CONTEXT.md` First:** Before starting any significant architectural, database, backend, frontend, or API work, read [PROJECT_CONTEXT.md](file:///c:/xampp/htdocs/ghostModSync/PROJECT_CONTEXT.md) and the relevant specification in `docs/01-project-audit.md` through `docs/07-coding-standards.md`.
- **Never Silently Alter Confirmed Business Decisions:** The 10 confirmed business decisions in `PROJECT_CONTEXT.md` (dual S3 + Steam Workshop sources, public/private groups, automatic/approval-based sync, multi-group membership, per-group roles, SFG pilot group, independent multi-VTC isolation, ETS2 + ATS support, Windows WPF desktop client target, and web-first development) are immutable unless the user explicitly instructs otherwise.

## 2. Architecture & Dependency Discipline
- **Follow the Modular Monolith Architecture:** Structure domain logic across the 14 logical modules (`Authentication`, `Users`, `Devices`, `Groups`, `Memberships`, `Permissions`, `Games`, `Mods`, `Mod versions`, `Mod sources`, `Dependencies`, `Convoy profiles`, `Synchronisation`, `Audit logging`) using standard Laravel 13 directories (`app/Enums`, `app/Models`, `app/Policies`, `app/Services`, `app/Http/Controllers/Web`, `app/Http/Controllers/Api/V1`, `app/Http/Requests`, `app/Http/Resources/V1`).
- **Preserve Existing Authentication:** Retain Laravel Breeze (`web` guard) for browser sessions and use Laravel Sanctum (`sanctum` guard) for `/api/v1/*` and Windows desktop authentication. Never replace Breeze.
- **Avoid Unnecessary Dependency Installation:** Do not install unapproved Composer or NPM packages when native PHP 8.4, Laravel 13, Blade, Alpine.js, or Tailwind CSS features suffice. Always ask before adding new third-party dependencies.

## 3. Database & Migration Safety
- **Use Migrations for All Schema Changes:** Never alter the database schema manually via raw SQL outside `database/migrations/`.
- **Never Perform Destructive Database Operations Without Explicit Approval:** Do **not** run `migrate:fresh`, `migrate:reset`, `db:wipe`, `DROP TABLE`, or `TRUNCATE` on the `ghostModsSync` MySQL database unless the user explicitly approves it in the current turn.

## 4. Security, Validation & Authorization
- **Never Expose Secrets:** Never print, log, or commit secret values from `.env` (`APP_KEY`, database passwords, AWS/S3 keys, Sanctum tokens).
- **Use FormRequests & Laravel Policies Everywhere:** Every mutating or tenant-scoped action in Web and API controllers must validate input using a dedicated `FormRequest` class and authorize access via a Laravel `Policy` enforcing group membership, group visibility (`public`/`private`), and group-scoped roles.
- **Direct-to-S3 Binary Transfers & SHA-256 Verification:** Never stream large `.scs` or `.zip` mod archives through PHP workers. Always use S3 pre-signed URLs and enforce 64-character lowercase SHA-256 hash (`sha256_hash`) and byte-size (`file_size_bytes`) verification.

## 5. Quality, Testing & Documentation Maintenance
- **Write Automated Tests:** Every new model, service, policy, web controller, or `/api/v1` endpoint must include automated PHPUnit 12 feature/unit tests covering both positive flows and cross-group/unauthorized negative flows.
- **Update Documentation:** Whenever schema tables, architectural boundaries, or roadmap statuses change, update `PROJECT_CONTEXT.md` and the corresponding document in `docs/`.
- **Explain Important Implementation Decisions:** Clearly explain the rationale, trade-offs, and security/desktop-integration impact of major implementation decisions to the user.
