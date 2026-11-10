# 17 — Platform Administrator Dashboard Implementation

**Document Version:** 1.1  
**Date:** 2026-11-10  
**Related Plan:** [16-admin-dashboard-implementation-plan.md](file:///c:/xampp/htdocs/ghostModSync/docs/16-admin-dashboard-implementation-plan.md)

---

## 1. Executive Summary

This document records the full-stack implementation of the **Ghost ModSync Platform Administrator Dashboard** (`/admin/*`), built in accordance with the approved architecture plan in [docs/16-admin-dashboard-implementation-plan.md](file:///c:/xampp/htdocs/ghostModSync/docs/16-admin-dashboard-implementation-plan.md).

The Platform Administrator Dashboard provides cross-tenant platform governance across all 10 required operational sections while strictly preserving VTC tenant isolation, separating platform-level roles (`SuperAdmin`, `PlatformAdmin`) from group-scoped roles (`owner`, `admin`, `moderator`, `mod_manager`, `convoy_dispatcher`, `member`), and keeping the public landing page and ordinary User Dashboard (`/dashboard`) intact.

---

## 2. Database & Domain Layer Additions

1. **Additive Migration ([`2026_11_10_100011_add_platform_admin_and_moderation_reports_tables.php`](file:///c:/xampp/htdocs/ghostModSync/database/migrations/2026_11_10_100011_add_platform_admin_and_moderation_reports_tables.php))**
   - Extended `users` with `platform_role` (`VARCHAR(32)` nullable, indexed), `suspended_at` (`TIMESTAMP` nullable, indexed), and `suspended_reason` (`VARCHAR(500)` nullable).
   - Backfilled any existing `users.is_platform_admin = 1` records to `platform_role = 'super_admin'`.
   - Created `moderation_reports` table with polymorphic `reportable_type` / `reportable_id` (`Group` or `Mod`), `reporter_id`, `reason`, `details`, `status` (`pending`, `resolved`, `dismissed`), `reviewed_by`, `reviewed_at`, and `review_notes`.
2. **Enums, Models & Seeders/Factories**
   - [`PlatformRole`](file:///c:/xampp/htdocs/ghostModSync/app/Enums/PlatformRole.php): `SuperAdmin` (`super_admin`), `PlatformAdmin` (`platform_admin`), with `canAccessAdminDashboard()` and `canManageSystemSettings()`.
   - [`ModerationReportStatus`](file:///c:/xampp/htdocs/ghostModSync/app/Enums/ModerationReportStatus.php): `Pending` (`pending`), `Resolved` (`resolved`), `Dismissed` (`dismissed`).
   - [`ModerationReport`](file:///c:/xampp/htdocs/ghostModSync/app/Models/ModerationReport.php): Polymorphic moderation ticket model.
   - [`User`](file:///c:/xampp/htdocs/ghostModSync/app/Models/User.php): Added `isSuspended()`, `isPlatformAdmin()`, `isSuperAdmin()`, and `platformRoleLabel()`.
   - [`UserFactory`](file:///c:/xampp/htdocs/ghostModSync/database/factories/UserFactory.php) & [`SfgPilotSeeder`](file:///c:/xampp/htdocs/ghostModSync/database/seeders/SfgPilotSeeder.php): Added `superAdmin()` and `platformAdmin()` factory states and provisioned the SFG pilot admin with `PlatformRole::SuperAdmin`.

---

## 3. Role Hierarchy, Access Control & Security Enforcement

| Role Tier | Scope | `/admin/*` Access | Capabilities |
| :--- | :--- | :--- | :--- |
| **Platform Super Admin** (`PlatformRole::SuperAdmin`) | Global Platform | Allowed | Full access to all 10 `/admin/*` sections + can assign/revoke `platform_role` (`manage-platform-settings` Gate) and manage global `games` / `game_versions`. |
| **Platform Administrator** (`PlatformRole::PlatformAdmin`) | Global Platform | Allowed | Full operational access to `/admin/*` (users, groups, mods, convoy profiles, sync, storage, reports, audit logs), but forbidden (`403`) from changing platform admin roles or global system/game settings. |
| **Group Owner / Admin / Moderator** (`GroupRole::*`) | Single VTC (`group_id`) | **Denied (`403`)** | Scoped strictly to their VTC via `GroupPolicy`; never automatically granted platform administration access. |
| **Ordinary Member** (`GroupRole::Member`) | Single VTC (`group_id`) | **Denied (`403`)** | Standard authenticated user dashboard (`/dashboard`) only. |

- **Middleware ([`EnsurePlatformAdmin`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Middleware/EnsurePlatformAdmin.php) / `platform.admin` alias):**
  - Verifies the user is authenticated, not suspended (immediately logging out and invalidating sessions for suspended accounts), and holds `PlatformRole::SuperAdmin` or `PlatformRole::PlatformAdmin`.
- **Platform-Wide Suspension Enforcement:**
  - [`LoginRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Auth/LoginRequest.php) blocks suspended users from authenticating via browser sessions.
  - [`EnsureActiveDeviceToken`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Middleware/EnsureActiveDeviceToken.php) rejects Sanctum desktop client API calls from suspended users with HTTP `403 account_suspended`.
- **Self-Lockout & Secret Protection:**
  - Administrators are blocked from suspending or demoting their own account.
  - `/admin/settings` and `/admin/sync` never expose `.env` secrets (`APP_KEY`, database credentials, S3 secret keys) or Sanctum token hashes.

---

## 4. Implemented Routes, Controllers, Services & FormRequests

### Route Map ([`routes/web.php`](file:///c:/xampp/htdocs/ghostModSync/routes/web.php) — `auth`, `verified`, `platform.admin`)

| Method & URI | Route Name | Controller Action | Gate / Permission |
| :--- | :--- | :--- | :--- |
| `GET /admin` | `admin.dashboard` | `AdminDashboardController@index` | `access-admin-dashboard` |
| `GET /admin/users` | `admin.users.index` | `AdminUserController@index` | `access-admin-dashboard` |
| `GET /admin/users/{user}` | `admin.users.show` | `AdminUserController@show` | `access-admin-dashboard` |
| `PATCH /admin/users/{user}/status` | `admin.users.status.update` | `AdminUserController@updateStatus` | `access-admin-dashboard` (`manage-platform-settings` for `update_role`) |
| `DELETE /admin/users/{user}/devices/{device}` | `admin.users.devices.revoke` | `AdminUserController@revokeDevice` | `access-admin-dashboard` |
| `GET /admin/groups` | `admin.groups.index` | `AdminGroupController@index` | `access-admin-dashboard` |
| `GET /admin/groups/{group}` | `admin.groups.show` | `AdminGroupController@show` | `access-admin-dashboard` |
| `PATCH /admin/groups/{group}/status` | `admin.groups.status.update` | `AdminGroupController@updateStatus` | `access-admin-dashboard` |
| `GET /admin/mods` | `admin.mods.index` | `AdminModController@index` | `access-admin-dashboard` |
| `GET /admin/mods/{mod}` | `admin.mods.show` | `AdminModController@show` | `access-admin-dashboard` |
| `PATCH /admin/mod-versions/{modVersion}/status` | `admin.mod-versions.status.update` | `AdminModController@updateVersionStatus` | `access-admin-dashboard` |
| `PATCH /admin/mod-approvals/{approval}` | `admin.mod-approvals.review` | `AdminModController@reviewApproval` | `access-admin-dashboard` |
| `PATCH /admin/moderation-reports/{report}` | `admin.moderation-reports.review` | `AdminModController@reviewReport` | `access-admin-dashboard` |
| `GET /admin/convoys` | `admin.convoys.index` | `AdminConvoyProfileController@index` | `access-admin-dashboard` |
| `GET /admin/convoys/{profile}` | `admin.convoys.show` | `AdminConvoyProfileController@show` | `access-admin-dashboard` |
| `GET /admin/sync` | `admin.sync.index` | `AdminSyncController@index` | `access-admin-dashboard` |
| `GET /admin/storage` | `admin.storage.index` | `AdminDashboardController@storage` | `access-admin-dashboard` |
| `GET /admin/reports` | `admin.reports.index` | `AdminDashboardController@reports` | `access-admin-dashboard` |
| `GET /admin/reports/export/{type}` | `admin.reports.export` | `AdminDashboardController@exportReport` | `access-admin-dashboard` |
| `GET /admin/audit-logs` | `admin.audit-logs.index` | `AdminDashboardController@auditLogs` | `access-admin-dashboard` |
| `GET /admin/settings` | `admin.settings.index` | `AdminSettingsController@index` | `access-admin-dashboard` |
| `POST /admin/settings/game-versions` | `admin.settings.game-versions.store` | `AdminSettingsController@storeGameVersion` | `manage-platform-settings` |
| `PATCH /admin/settings/games/{game}` | `admin.settings.games.update` | `AdminSettingsController@updateGame` | `manage-platform-settings` |

### Services & FormRequests
- [`PlatformAnalyticsService`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Admin/PlatformAnalyticsService.php) & [`PlatformModerationService`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Admin/PlatformModerationService.php)
- [`UpdateUserStatusRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/UpdateUserStatusRequest.php), [`UpdateGroupAdminRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/UpdateGroupAdminRequest.php), [`UpdateModVersionStatusRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/UpdateModVersionStatusRequest.php), [`ReviewGroupModApprovalRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/ReviewGroupModApprovalRequest.php), [`ReviewModerationReportRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/ReviewModerationReportRequest.php), [`StoreGameVersionRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/StoreGameVersionRequest.php), [`UpdateGameRequest`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/Admin/UpdateGameRequest.php)

### Blade Views (`resources/views/admin/` & `resources/views/components/admin/layout.blade.php`)
- Dedicated 10-item Platform Admin layout ([`layout.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/components/admin/layout.blade.php)) with responsive mobile drawer, breadcrumb navigation, moderation attention notification indicator, administrator profile dropdown menu, and `Platform Admin` / `Super Admin` role badge.
- Views implemented for all 10 sections:
  1. [`admin/dashboard.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/dashboard.blade.php)
  2. [`admin/users/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/users/index.blade.php) & [`admin/users/show.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/users/show.blade.php)
  3. [`admin/groups/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/groups/index.blade.php) & [`admin/groups/show.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/groups/show.blade.php)
  4. [`admin/mods/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/mods/index.blade.php) & [`admin/mods/show.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/mods/show.blade.php)
  5. [`admin/convoys/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/convoys/index.blade.php) & [`admin/convoys/show.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/convoys/show.blade.php)
  6. [`admin/sync/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/sync/index.blade.php)
  7. [`admin/storage/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/storage/index.blade.php)
  8. [`admin/reports/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/reports/index.blade.php)
  9. [`admin/audit-logs/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/audit-logs/index.blade.php)
  10. [`admin/settings/index.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/admin/settings/index.blade.php)

---

## 5. Automated Testing & Outstanding Work

- **Test Suite ([`tests/Feature/AdminDashboardTest.php`](file:///c:/xampp/htdocs/ghostModSync/tests/Feature/AdminDashboardTest.php)):**
  - `test_guest_is_redirected_to_login_from_admin_routes`
  - `test_ordinary_users_and_group_admins_cannot_access_platform_admin_dashboard`
  - `test_platform_admin_and_super_admin_can_view_all_ten_sections_with_real_metrics`
  - `test_platform_admin_can_suspend_and_reactivate_user_and_suspended_user_cannot_login_or_use_api`
  - `test_only_super_admin_can_assign_platform_roles_or_modify_system_settings`
  - `test_platform_admin_can_archive_restore_and_update_group_quota_and_moderate_mods_and_reports`
  - `test_search_filtering_pagination_date_ranges_and_responsive_admin_layout`
  - Full PHPUnit 12 suite result: **87 tests, 709 assertions passing (`0` failures, `0` errors)**.
- **Outstanding / Future Phase Items:**
  - When local MySQL (`127.0.0.1:3306`) is started in XAMPP, run `php artisan migrate` to apply migration `2026_11_10_100011_add_platform_admin_and_moderation_reports_tables.php` to the `ghostModsSync` database.
  - Member-facing submission UI for filing `ModerationReport` tickets on groups/mods (currently reviewable by platform administrators via `/admin/mods` and `/admin/groups`).
