# Ghost ModSync — Project Context & Master Technical Reference (`PROJECT_CONTEXT.md`)

**Last Updated:** 2026-11-10 (User Dashboard & Platform Admin Dashboard completed, MySQL migrations applied — see `docs/13` through `docs/18`)  
**Role:** Permanent Single Source of Truth for Engineers & AI Agents  
**Status:** Milestones 1–5.6 Completed (Full Laravel 13 Backend, S3 + Steam Workshop Mods, Dependencies, Convoy Profiles, REST API v1, User Dashboard, Platform Admin Dashboard & MySQL DB Ready)

---

## 1. Project Vision & Domain Overview

**Ghost ModSync** is a multi-tenant, group-based mod management and synchronisation platform for **Euro Truck Simulator 2 (ETS2)** and **American Truck Simulator (ATS)**.

### Core Problem Solved
Virtual Trucking Companies (VTCs) and gaming communities host multiplayer convoys that require every participant to have the exact same set of mods, exact mod versions, and exact load order active in their game profile. Manual distribution via external file-sharing links leads to version mismatches, corrupted archives, broken load orders, and failed convoy joins.

### Solution Overview
1. **Web Platform (Laravel 13 + Blade):** Allows VTC/Group administrators (starting with the initial pilot group, **SFG**) to create public or private groups, manage members and group-scoped roles, maintain a shared mod library (direct S3-hosted `.scs`/`.zip` uploads and Steam Workshop references), declare mod dependencies, and publish versioned **Convoy Profiles** with deterministic mod load orders.
2. **Windows Desktop Client (C# / .NET / WPF):** Authenticates via Laravel Sanctum, registers the user's Windows device, compares locally installed mods (`Documents\Euro Truck Simulator 2\mod` and `Documents\American Truck Simulator\mod` plus Steam Workshop cache) against the selected Convoy Profile's cryptographic manifest (SHA-256 + byte size), and synchronises missing or outdated mods (either automatically or via approval-based workflows).

---

## 2. Confirmed Business Decisions (Immutable Baseline)

| # | Decision | Architectural Implication |
| :--- | :--- | :--- |
| **1** | **Mod distribution supports both direct file uploads and Steam Workshop integration.** | `ModVersion` and `ModSource` records distinguish between `s3_upload` (managed binary with S3 key + SHA-256) and `steam_workshop` (Steam `published_file_id` + Workshop URL). |
| **2** | **Groups support both public and private visibility.** | Public groups are discoverable and readable by authenticated users; private groups require explicit invitation or membership approval (`GroupPolicy` enforced on every query and route). |
| **3** | **Group administrators can configure synchronisation as automatic or approval-based.** | Groups and Convoy Profiles store a `sync_policy` (`automatic` vs. `approval_required`), controlling whether desktop clients auto-apply updates or require explicit user/admin confirmation. |
| **4** | **Users may belong to multiple groups.** | Many-to-many relationship between `users` and `groups` via the `group_memberships` pivot table. |
| **5** | **A user can have different roles in different groups.** | Role (`owner`, `admin`, `mod_manager`, `convoy_dispatcher`, `member`) is stored on the `group_memberships` pivot table, never globally on the `users` table. |
| **6** | **SFG will be the initial pilot group.** | Database seeders and initial acceptance tests will provision the **SFG** group as the reference tenant. |
| **7** | **The system must support multiple VTCs independently.** | Strict logical tenant isolation (`group_id` foreign keys and Laravel Policies) ensures one VTC's private mods, members, and convoy profiles are never leaked to another VTC. |
| **8** | **ETS2 and ATS must be supported.** | Mods and Convoy Profiles are explicitly scoped to a `Game` (`ets2` or `ats`) and compatible game versions (e.g., `1.53.*`). |
| **9** | **The initial desktop client will target Windows (C# / .NET / WPF).** | API responses (`/api/v1/*`) use strict JSON contracts (`JsonResource`), ISO-8601 UTC timestamps, SHA-256 hex digests, and S3 pre-signed URLs tailored for `HttpClient` in .NET. |
| **10** | **The web backend will be developed first.** | Phase 1–5 deliver the complete Laravel 13 web platform and `/api/v1/*` endpoints before WPF client implementation begins. |

---

## 3. Verified Technology Stack Baseline

Respecting the verified environment in `c:\xampp\htdocs\ghostModSync` (see [docs/01-project-audit.md](file:///c:/xampp/htdocs/ghostModSync/docs/01-project-audit.md)):

| Layer | Technology | Verified Version / Configuration |
| :--- | :--- | :--- |
| **PHP Runtime** | PHP 8.4 (Thread-Safe VS17 x64) | `8.4.27` (`C:\xampp\php\php.exe`, constraint `^8.3` in `composer.json`) |
| **Backend Framework** | Laravel 13 | `laravel/framework` `v13.40.0` (`^13.0`) |
| **Web Authentication** | Laravel Breeze (Blade + Alpine) | `laravel/breeze` `v2.4.2` (Session guard `web`, `MustVerifyEmail` enabled on `User`) |
| **API / Desktop Auth** | Laravel Sanctum | `laravel/sanctum` `^4.0` (Bearer Personal Access Tokens, device-scoped) |
| **Database** | MySQL / MariaDB (XAMPP) | `DB_CONNECTION=mysql`, `DB_DATABASE=ghostModsSync`, `utf8mb4_unicode_ci` |
| **Web Frontend** | Blade + Alpine.js + Tailwind CSS | `tailwindcss` `3.4.19`, `alpinejs` `3.17.5`, `vite` `7.3.7` |
| **Object Storage** | S3-Compatible Storage | `league/flysystem-aws-s3-v3` (`^3.0`), pre-signed upload/download URLs |
| **Integrity Verification** | SHA-256 + Byte Size | Lowercase 64-char hex SHA-256 (`CHAR(64)`) verified on upload and desktop sync |
| **Desktop Client (Future)** | C# / .NET 8+ / WPF | Windows desktop application consuming `/api/v1/*` |
| **Automated Testing** | PHPUnit 12 | `phpunit/phpunit` `v12.5.43` (`php vendor/bin/phpunit`) |

---

## 4. Modular Monolith Module Map

Ghost ModSync is structured as a **Modular Monolith** using standard Laravel conventions (`app/Models`, `app/Services`, `app/Policies`, `app/Http/Controllers`, `app/Http/Controllers/Api/V1`, `app/Http/Requests`, `app/Http/Resources/V1`, `app/Enums`) organized around 14 logical domain modules:

1. **Authentication:** Breeze browser session auth + Sanctum API token issuance/revocation.
2. **Users:** User accounts, email verification (`MustVerifyEmail`), profile settings, Steam ID linking.
3. **Devices:** Registered Windows desktop client installations (`devices` table) tied to Sanctum tokens.
4. **Groups:** VTC / community entities (`groups` table, including pilot group **SFG**), visibility (`public`/`private`), and group-level sync mode (`automatic`/`approval_required`).
5. **Memberships:** Multi-group membership lifecycle, invitations, join requests, and role assignments (`group_memberships`, `group_invitations`, `group_join_requests`).
6. **Permissions:** Group-scoped RBAC evaluated via Laravel Policies and `GroupPermissionService`.
7. **Games:** Supported simulators (`ets2`, `ats`), game version compatibility definitions (`game_versions`), and official SCS map/cargo DLCs (`game_dlcs`).
8. **Mods:** Logical mod catalogue entries owned by a group (`mods` table).
9. **Mod Versions:** Immutable semantic releases of a mod (`mod_versions`, `mod_version_dlc`, `group_mod_approvals`) with `sha256_hash`, `file_size_bytes`, DLC requirements, and group-specific approval records.
10. **Mod Sources:** Delivery backing for each mod version (`s3_upload` vs. `steam_workshop`).
11. **Dependencies:** Directed Acyclic Graph (DAG) of required/conflicting mods (`mod_dependencies`).
12. **Convoy Profiles:** Ordered, version-pinned mod manifests (`convoy_profiles`, `convoy_profile_mods`) and immutable published revision snapshots (`convoy_profile_revisions`, `convoy_profile_revision_mods`) representing an approved group/convoy setup.
13. **Synchronisation:** Manifest calculation, diffing, sync approvals, append-only desktop client sync session history, and per-mod sync telemetry (`sync_approvals`, `sync_sessions`, `sync_session_items`).
14. **Audit Logging:** Immutable security and operational audit trail (`audit_logs`) for group, mod, profile, and permission changes.

---

## 5. Documentation Map

All architectural and engineering decisions are maintained in `docs/`:

- [docs/01-project-audit.md](file:///c:/xampp/htdocs/ghostModSync/docs/01-project-audit.md) — Initial Project Audit & Baseline Inspection.
- [docs/02-system-requirements.md](file:///c:/xampp/htdocs/ghostModSync/docs/02-system-requirements.md) — Functional & Non-Functional Requirements, Roles, and Use Cases.
- [docs/03-system-architecture.md](file:///c:/xampp/htdocs/ghostModSync/docs/03-system-architecture.md) — Modular Monolith Architecture, ADRs, S3 & Desktop Sync Flows.
- [docs/04-database-design.md](file:///c:/xampp/htdocs/ghostModSync/docs/04-database-design.md) — Complete MySQL (`ghostModsSync`) 20-Table Schema, Indexes, Constraints, and 14-Migration Plan.
- [docs/05-development-roadmap.md](file:///c:/xampp/htdocs/ghostModSync/docs/05-development-roadmap.md) — Phased Implementation Roadmap & Milestones.
- [docs/06-security-standards.md](file:///c:/xampp/htdocs/ghostModSync/docs/06-security-standards.md) — Authentication, Multi-Tenant Isolation, S3 Security, and SHA-256 Verification.
- [docs/07-coding-standards.md](file:///c:/xampp/htdocs/ghostModSync/docs/07-coding-standards.md) — PHP 8.4 / Laravel 13 Conventions, Testing Standards, and API Design Rules.
- [docs/08-database-erd.md](file:///c:/xampp/htdocs/ghostModSync/docs/08-database-erd.md) — Relational Entity-Relationship Diagram (Mermaid ERD), Foreign Key Deletion Strategy, and SFG Pilot Payloads.
- [docs/09-authentication-and-api.md](file:///c:/xampp/htdocs/ghostModSync/docs/09-authentication-and-api.md) — Browser (Breeze) & Windows Desktop (Sanctum) Authentication Flows and `/api/v1/*` Error/Response Contracts.
- [docs/10-group-management.md](file:///c:/xampp/htdocs/ghostModSync/docs/10-group-management.md) — Multi-Tenant Group Management, Role Hierarchy (`Owner`, `Admin`, `Moderator`, `ModManager`, `ConvoyDispatcher`, `Member`), Join Requests, Invitations, and Cross-Group Isolation.
- [docs/11-device-management.md](file:///c:/xampp/htdocs/ghostModSync/docs/11-device-management.md) — User Device Registration, Multi-PC Lifecycle, Heartbeat, Credential Revocation, and C# WPF Identity Integration.
- [docs/12-phase-1-quality-report.md](file:///c:/xampp/htdocs/ghostModSync/docs/12-phase-1-quality-report.md) — Phase 1 Integration & Quality Assurance Audit Report, Security Remediations, and Phase 2 Readiness Sign-Off.
- [docs/13-user-dashboard-implementation-plan.md](file:///c:/xampp/htdocs/ghostModSync/docs/13-user-dashboard-implementation-plan.md) — User Dashboard audit, design, plan and Phase 1 status.
- [docs/14-user-dashboard-implementation.md](file:///c:/xampp/htdocs/ghostModSync/docs/14-user-dashboard-implementation.md) — User Dashboard full implementation documentation, routes, queries, permissions, and test coverage.
- [docs/15-user-dashboard-qa-report.md](file:///c:/xampp/htdocs/ghostModSync/docs/15-user-dashboard-qa-report.md) — User Dashboard QA, Security, Performance, and UI/UX Review Report.
- [docs/16-admin-dashboard-implementation-plan.md](file:///c:/xampp/htdocs/ghostModSync/docs/16-admin-dashboard-implementation-plan.md) — Platform Administrator Dashboard architecture, role hierarchy, security design, and implementation plan.
- [docs/17-admin-dashboard-implementation.md](file:///c:/xampp/htdocs/ghostModSync/docs/17-admin-dashboard-implementation.md) — Platform Administrator Dashboard full implementation documentation, controllers, services, views, and security test coverage.
- [docs/18-admin-dashboard-qa-report.md](file:///c:/xampp/htdocs/ghostModSync/docs/18-admin-dashboard-qa-report.md) — Platform Administrator Dashboard QA, Security, Performance, and UI/UX Review Report.
- [ASSETS_README.md](file:///c:/xampp/htdocs/ghostModSync/ASSETS_README.md) — Welcome Landing Page Brand Assets, Dimensions, Formats, and Replacement Guide (`config/landing/*`).
