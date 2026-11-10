# 16 — Platform Administrator Dashboard Implementation Plan

**Date:** 2026-11-10  
**Role:** Senior SaaS Platform Architect, Laravel Administrator Dashboard Developer & Security Engineer  
**Status:** PROPOSAL — Awaiting Approval (No application code or database migrations have been executed for this phase)

Legend:
- ✅ **Implemented in Schema/Backend** (Data model & service logic exist; needs Admin Controller + Blade UI)
- 🟡 **Partially Implemented** (Partial schema/logic exists; needs extension or dedicated Admin layer)
- ❌ **Not Implemented** (No schema or backend logic exists yet)
- ❓ **Requires Architectural Decision** (Requires explicit sign-off before adding a migration or feature)

---

## 1. Step 1: Inspection of the Existing System

### 1.1 Existing Authentication, Roles & Permissions
| Capability | Current Implementation State |
| :--- | :--- |
| **Web Authentication** | ✅ Laravel Breeze (`web` session guard) + `MustVerifyEmail` (`verified` middleware). |
| **Desktop / API Authentication** | ✅ Laravel Sanctum (`auth:sanctum` + `EnsureActiveDeviceToken` + token abilities). |
| **Platform Admin Flag** | 🟡 `users.is_platform_admin` (`boolean`, default `false`, hidden from serialization, excluded from `$fillable`). Currently acts as an implicit bypass inside `GroupPermissionService`, `DevicePolicy`, and `GroupManagementService`, but **no `/admin` routes, middleware, Gates, or UI exist yet**. |
| **Per-Group Roles** | ✅ Stored strictly on `group_memberships.role` via `App\Enums\GroupRole` (`owner`, `admin`, `moderator`, `mod_manager`, `convoy_dispatcher`, `member`). |
| **User Account Suspension** | ❌ `group_memberships.status` supports per-group `suspended` and `banned` states, and `devices.revoked_at` revokes desktop devices, but `users` has no global `suspended_at` column yet (see Decision **AD-2**). |

### 1.2 Existing Domain Schema & Capabilities (20-Table Baseline)
- **Users & Devices:** `users`, `devices` (`device_uuid`, `device_name`, `os_version`, `client_version`, `last_seen_at`, `revoked_at`), `personal_access_tokens`.
- **Groups & Memberships:** `groups` (`owner_id`, `visibility`, `sync_policy`, `require_join_approval`, `require_mod_approval`, `storage_quota_bytes`, `deleted_at` via `SoftDeletes`), `group_memberships`, `group_invitations`, `group_join_requests`.
- **Games, Versions & DLCs:** `games` (`ets2`, `ats`, `steam_app_id`, `default_mod_dir_name`, `is_active`), `game_versions` (`version_branch`, `is_current`, `released_at`), `game_dlcs` (`code`, `name`, `steam_app_id`, `category`, `is_active`), `mod_version_dlc`.
- **Mods & Storage:** `mods` (`group_id`, `game_id`, `source_type`, `is_active`, `deleted_at`), `mod_versions` (`version_number`, `min_game_version`, `max_game_version`, `archive_filename`, `file_size_bytes`, `sha256_hash`, `status`, `published_at`, `deleted_at`), `mod_sources` (`s3_disk`, `s3_object_key`, `s3_etag`, `steam_published_file_id`, `steam_workshop_url`), `mod_dependencies`, `group_mod_approvals` (`status`, `checksum_verified`, `reviewed_by`).
- **Convoy Profiles & Revisions:** `convoy_profiles` (`group_id`, `game_id`, `current_revision_id`, `target_game_version`, `sync_policy_override`, `revision`, `manifest_hash`, `is_default`, `is_active`, `scheduled_convoy_at`, `published_at`, `deleted_at`), `convoy_profile_mods`, `convoy_profile_revisions` (`snapshot_json`), `convoy_profile_revision_mods`.
- **Synchronisation Telemetry:** `sync_sessions` (`convoy_profile_id`, `convoy_profile_revision_id`, `user_id`, `device_id`, `profile_revision`, `status`, `missing_mods_count`, `mismatched_mods_count`, `extra_local_mods_count`, `bytes_downloaded`, `duration_ms`, `error_message`, `started_at`, `synced_at`), `sync_session_items`, `sync_approvals`.
- **Audit Logging:** `audit_logs` (`group_id`, `user_id`, `device_id`, `event`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`).

---

## 2. Step 2: Role Hierarchy & Platform Administrator Access Control

### 2.1 Strict Separation Between Platform Roles and Group Roles

A **Group Owner**, **Group Administrator**, or **Group Moderator** only holds authority inside their specific `Group` (`group_memberships`). **They never gain access to `/admin/*` or platform-wide data.**

| Tier | Scope | Role | Identifier / Storage | Capabilities |
| :--- | :--- | :--- | :--- | :--- |
| **1** | **Platform** | **Platform Super Admin** | `users.is_platform_admin = true` (and `platform_role = 'super_admin'` if **AD-1** is approved) | Full platform oversight: manage users, groups, mods, convoy profiles, sync telemetry, storage quotas, audit logs, system/game settings, and promote/demote Platform Administrators. |
| **2** | **Platform** | **Platform Administrator** | ❓ **AD-1:** Optional `users.platform_role = 'platform_admin'` (or single-tier `is_platform_admin`) | Day-to-day platform moderation & operations (review users, groups, mods, sync errors, reports, and audit logs), but **cannot** promote/demote Super Admins or alter critical security/system configuration. |
| **3** | **Group (VTC)** | **Group Owner** | `group_memberships.role = 'owner'` | Full control of their own VTC group only (`groups.owner_id`). **Zero `/admin/*` access.** |
| **4** | **Group (VTC)** | **Group Administrator** | `group_memberships.role = 'admin'` | Manage group settings, members, mods, and convoy profiles in their group only. **Zero `/admin/*` access.** |
| **5** | **Group (VTC)** | **Group Moderator / Managers** | `group_memberships.role = 'moderator' \| 'mod_manager' \| 'convoy_dispatcher'` | Scoped group operational permissions only. **Zero `/admin/*` access.** |
| **6** | **Group (VTC)** | **Ordinary Member** | `group_memberships.role = 'member'` | View group mods/profiles and run desktop sync. **Zero `/admin/*` access.** |

### 2.2 Server-Side Enforcement Architecture
1. **Dedicated Middleware (`EnsurePlatformAdmin` aliased as `platform.admin`):**
   - Applied to the entire `/admin` route group (`['auth', 'verified', 'platform.admin']`).
   - Returns HTTP `403 Forbidden` (never redirects with hints) if `! $request->user()?->is_platform_admin` (or if the user account is suspended).
2. **Laravel Gates & Policies (`AppServiceProvider` + `AdminPolicy`):**
   - `Gate::define('access-admin-dashboard', fn (User $user): bool => (bool) $user->is_platform_admin)`
   - `Gate::define('manage-platform-settings', ...)` (Super Admin only if two-tier platform roles in **AD-1** are enabled).
   - Every mutating admin controller action must authorize via `$this->authorize(...)` / `Gate::authorize(...)` and validate input via a dedicated `App\Http\Requests\Web\Admin\*` `FormRequest`.
3. **Zero Security-by-Obscurity:**
   - The link to the Platform Admin area is only rendered when `@can('access-admin-dashboard')` is true, **and** every `/admin/*` route enforces `platform.admin` + Gate checks on the server.

---

## 3. Step 3: Admin Dashboard Structure & Feature Classification

### 3.1 Overview (`/admin`)
| Metric / Component | Data Source | Status |
| :--- | :--- | :--- |
| Total registered users & verified users | `User::count()`, `User::whereNotNull('email_verified_at')->count()` | ✅ Data ready / ❌ Admin UI |
| Active users (last 7d / 30d) | `users.last_login_at`, `devices.last_seen_at`, `sync_sessions.updated_at` | ✅ Data ready / ❌ Admin UI |
| Total groups (Public vs. Private vs. Archived/Soft-Deleted) | `Group::withTrashed()` grouped by `visibility` and `deleted_at` | ✅ Data ready / ❌ Admin UI |
| Total mods & approved/ready mod versions | `Mod::count()`, `ModVersion::where('status', 'ready')->count()`, `GroupModApproval::where('status', 'approved')->count()` | ✅ Data ready / ❌ Admin UI |
| Total published convoy profiles | `ConvoyProfile::where('is_active', true)->whereNotNull('published_at')->count()` | ✅ Data ready / ❌ Admin UI |
| Synchronisation activity summary (24h / 7d success vs. failure rate, bytes transferred) | `SyncSession` aggregates (`status`, `bytes_downloaded`) | ✅ Data ready / ❌ Admin UI |
| Total S3 storage consumption vs. allocated group quotas | `SUM(mod_versions.file_size_bytes)` where `source_type = s3_upload` vs. `SUM(groups.storage_quota_bytes)` | ✅ Data ready / ❌ Admin UI |

### 3.2 User Management (`/admin/users`, `/admin/users/{user}`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View, search & filter users (by name, username, email, Steam ID, verified state, admin state) | `User::query()` with wildcard-escaped search + pagination | ✅ Data ready / ❌ Admin UI |
| View user profile, registered devices & revoke compromised devices | `User` with `devices`, `personalAccessTokens` | ✅ Data ready / ❌ Admin UI |
| View user's group memberships & roles across all VTCs | `GroupMembership::with('group')` | ✅ Data ready / ❌ Admin UI |
| Review user account activity | `AuditLog::where('user_id', $user->id)` + `SyncSession::where('user_id', $user->id)` | ✅ Data ready / ❌ Admin UI |
| **Suspend & reactivate user accounts globally** | ❓ **AD-2:** Requires adding `suspended_at` (`timestamp nullable`) and `suspended_reason` (`string nullable`) to `users` + middleware check blocking suspended users from web/API sessions and revoking active Sanctum tokens | ❓ Requires additive migration |

### 3.3 Group Management (`/admin/groups`, `/admin/groups/{group}`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View, search & filter all groups (including `Private` and soft-deleted/archived groups) | `Group::withTrashed()` with owner, member counts, mod counts, profile counts | ✅ Data ready / ❌ Admin UI |
| View group owner, members, roles & membership statuses | `Group::with(['owner', 'memberships.user'])` | ✅ Data ready / ❌ Admin UI |
| Archive (soft-delete) or restore a group, or adjust `storage_quota_bytes` | `Group::delete()` (`SoftDeletes`), `Group::restore()`, `storage_quota_bytes` update (logged to `audit_logs`) | ✅ Schema supports `SoftDeletes` & quota / ❌ Admin UI |
| Suspend a group (distinct from soft-delete) | ❓ **AD-3:** Can use existing `SoftDeletes` (`deleted_at` archives/suspends the group) OR add `suspended_at` to `groups` | 🟡 `SoftDeletes` exists; `suspended_at` optional |
| Investigate reported groups | ❓ **AD-4:** No `abuse_reports` table exists yet (see §3.4 & AD-4) | ❌ / ❓ |

### 3.4 Mod Management (`/admin/mods`, `/admin/mods/{mod}`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View & filter all uploaded S3 and Steam Workshop mods across groups | `Mod::withTrashed()->with(['group', 'game', 'creator'])` | ✅ Data ready / ❌ Admin UI |
| Review pending approvals (`GroupModApproval` & `ModVersionStatus::Pending`) | `GroupModApproval::where('status', 'pending')`, `ModVersion::where('status', 'pending')` | ✅ Data ready / ❌ Admin UI |
| Monitor mod versions & inspect game/DLC compatibility | `ModVersion::with(['source', 'requiredDlcs', 'dependencies.relatedMod'])` (`min_game_version`, `max_game_version`) | ✅ Data ready / ❌ Admin UI |
| **Identify duplicate or invalid uploads** | Duplicate `sha256_hash` detection across `mod_versions` (`GROUP BY sha256_hash HAVING COUNT(*) > 1`), plus `Pending`/`Revoked`/`Deprecated` versions and unverified checksums (`checksum_verified = false`) | ✅ Data ready / ❌ Admin UI |
| Revoke or deprecate a malicious/broken mod version | Update `ModVersion::status` to `ModVersionStatus::Revoked` / `Deprecated` + `AuditLog` | ✅ Data ready / ❌ Admin UI |
| Review reported mods | ❓ **AD-4:** Requires `moderation_reports` table if user-submitted reporting is desired, or shows system-flagged mods (checksum mismatch / duplicate hash / revoked) without a new table | ❌ / ❓ |

### 3.5 Convoy Profile Management (`/admin/convoys`, `/admin/convoys/{convoyProfile}`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View & filter all convoy profiles across groups and games (`ETS2` / `ATS`) | `ConvoyProfile::withTrashed()->with(['group', 'game', 'creator', 'currentRevision'])` | ✅ Data ready / ❌ Admin UI |
| Review profile revisions & immutable `snapshot_json` load orders | `ConvoyProfileRevision::with(['mods.modVersion.mod', 'publisher'])` | ✅ Data ready / ❌ Admin UI |
| Monitor publication status, `target_game_version`, `manifest_hash`, and scheduled convoy times | `ConvoyProfile` attributes (`is_active`, `published_at`, `scheduled_convoy_at`) | ✅ Data ready / ❌ Admin UI |

### 3.6 Synchronisation Monitoring (`/admin/sync`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View & filter synchronisation sessions (`in_sync`, `out_of_sync`, `syncing`, `pending_approval`, `failed`) | `SyncSession::with(['user', 'device', 'convoyProfile.group'])` | ✅ Data ready / ❌ Admin UI |
| Investigate sync errors & per-mod item failures | `SyncSession::where('status', 'failed')` (`error_message`) + `SyncSessionItem::whereNotNull('error_detail')` | ✅ Data ready / ❌ Admin UI |
| Monitor pending sync approvals across groups | `SyncApproval::where('status', 'pending')->with(['convoyProfile.group', 'user', 'device'])` | ✅ Data ready / ❌ Admin UI |
| Review desktop client activity & client/OS version distribution | `Device::query()` (`client_version`, `os_version`, `last_seen_at`, `revoked_at`) | ✅ Data ready / ❌ Admin UI |

### 3.7 Storage Management (`/admin/storage`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| View total S3 storage consumption vs. platform/group quotas | `ModVersion` + `ModSource` (`source_type = 's3_upload'`, `SUM(file_size_bytes)`) | ✅ Data ready / ❌ Admin UI |
| Storage usage breakdown by group (`used_bytes` vs. `storage_quota_bytes` %) | Grouped sum of `mod_versions.file_size_bytes` per `mods.group_id` compared to `groups.storage_quota_bytes` | ✅ Data ready / ❌ Admin UI |
| Review largest uploaded archives & duplicate S3 binaries | Top `ModVersion` rows by `file_size_bytes` and duplicate `sha256_hash` groups | ✅ Data ready / ❌ Admin UI |
| Monitor stalled/uncompleted uploads (`status = 'pending'`) | `ModVersion::where('status', ModVersionStatus::Pending)` older than threshold | ✅ Data ready / ❌ Admin UI |

### 3.8 Reports (`/admin/reports`)
| Report Type | Data Source (Real Historical Data Only) | Status |
| :--- | :--- | :--- |
| User registration report (daily/weekly/monthly cohorts) | `users.created_at`, `users.email_verified_at` | ✅ Data ready / ❌ Admin UI |
| Group activity report (top groups by active members, mods, profiles, syncs) | `groups` with `memberships`, `mods`, `convoy_profiles`, `sync_sessions` counts | ✅ Data ready / ❌ Admin UI |
| Mod upload report (S3 vs. Steam Workshop volume & byte growth) | `mod_versions.created_at`, `file_size_bytes`, `mod_sources.source_type` | ✅ Data ready / ❌ Admin UI |
| Synchronisation reliability report (success vs. failure rates, total bytes synced, avg `duration_ms`) | `sync_sessions` grouped by `status` and date | ✅ Data ready / ❌ Admin UI |
| CSV export of report tables (streamed server-side, native PHP `fputcsv`) | Native Laravel `StreamedResponse` | ✅ Native PHP / ❌ Admin UI |

### 3.9 Audit Logs (`/admin/audit-logs`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| Searchable, filterable immutable audit log viewer | `AuditLog::with(['user', 'device'])` filtered by `event` category (`admin.*`, `user.*`, `group.*`, `mod.*`, `sync.*`), actor, group, date range | ✅ Data ready / ❌ Admin UI |
| Inspect structured diff (`old_values` vs. `new_values`) with secret redaction | JSON diff viewer in Blade (explicitly scrubbing any sensitive keys) | ✅ Data ready / ❌ Admin UI |

### 3.10 System Settings (`/admin/settings`)
| Capability | Data Source & Mechanism | Status |
| :--- | :--- | :--- |
| Manage supported games (`ETS2`, `ATS`), game version branches (`1.53.*`), and official DLC catalogue | `Game`, `GameVersion`, `GameDlc` models (`is_active`, `is_current`, `version_branch`) | ✅ Tables & models exist / ❌ Admin UI |
| Inspect platform environment, S3 disk configuration (non-secret metadata only), rate limits, and maintenance status (`app()->isDownForMaintenance()`) | Read-only config inspection (`config('filesystems.default')`, rate limiter limits, PHP/Laravel versions) — **never** exposes `.env` secrets or keys | ✅ Framework ready / ❌ Admin UI |
| Editable runtime platform settings (e.g., default group storage quota, max upload size, registration toggle) | ❓ **AD-5:** Requires a lightweight `platform_settings` key-value table if runtime-editable settings beyond `games`/`game_versions`/`game_dlcs` are desired | ❓ Optional additive migration |

---

## 4. Step 4: Admin Dashboard Visual & UI/UX Design

The Admin Dashboard will use the established **Ghost ModSync (`gms.*`) dark design system** while clearly distinguishing itself as the **Platform Control Plane** so an administrator never confuses it with the ordinary User Dashboard:

1. **Visual Distinction within the Same Design Language:**
   - Uses `<x-admin.layout>` sharing the core `gms.*` palette (`#080B12` background, `#101522` surface, `#151D2B` cards) with a distinct **"PLATFORM ADMIN" badge** (`bg-gms-purple/20 border-gms-purple text-gms-purple`) in the sidebar header and a quick **"Return to User Dashboard"** link (`route('dashboard')`).
2. **Sidebar Navigation (10 Sections):**
   - `Overview` (`/admin`)
   - `Users` (`/admin/users`)
   - `Groups (VTCs)` (`/admin/groups`)
   - `Mods & Catalogue` (`/admin/mods`)
   - `Convoy Profiles` (`/admin/convoys`)
   - `Sync Monitoring` (`/admin/sync`)
   - `Storage & Quotas` (`/admin/storage`)
   - `Reports & Analytics` (`/admin/reports`)
   - `Audit Logs` (`/admin/audit-logs`)
   - `System & Games` (`/admin/settings`)
3. **Reusable Admin UI Components (`resources/views/components/admin/*`):**
   - `layout.blade.php` — Responsive dark shell with off-canvas mobile drawer (`<1024px`) and topbar breadcrumb.
   - `data-table.blade.php` — Responsive table container with sticky headers, monospace ID/hash columns, and empty state slot.
   - `filter-bar.blade.php` — Search input + status/game/role dropdown filters + reset button (`GET` query strings with `withQueryString()` pagination).
   - `confirm-modal.blade.php` — Alpine.js confirmation modal requiring explicit confirmation (and reason input where applicable) for high-impact actions (suspending users, archiving groups, revoking mod versions).
   - `metric-bar.blade.php` — Pure-CSS/SVG progress and time-series bar visualisations driven strictly by real database aggregates (no external JS chart libraries required).

---

## 5. Step 5: Technical Implementation Plan

### 5.1 Proposed Routes (`routes/web.php` under `/admin` prefix, `admin.*` name prefix)
All routes are wrapped in `Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'platform.admin'])->group(...)`:

| Method | URI | Route Name | Controller Action |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin` | `admin.dashboard` | `AdminDashboardController@index` |
| `GET` | `/admin/users` | `admin.users.index` | `AdminUserController@index` |
| `GET` | `/admin/users/{user}` | `admin.users.show` | `AdminUserController@show` |
| `PATCH` | `/admin/users/{user}/status` | `admin.users.status.update` | `AdminUserController@updateStatus` (suspend/reactivate) |
| `DELETE` | `/admin/users/{user}/devices/{device}` | `admin.users.devices.revoke` | `AdminUserController@revokeDevice` |
| `GET` | `/admin/groups` | `admin.groups.index` | `AdminGroupController@index` |
| `GET` | `/admin/groups/{group}` | `admin.groups.show` | `AdminGroupController@show` (withTrashed binding) |
| `PATCH` | `/admin/groups/{group}/status` | `admin.groups.status.update` | `AdminGroupController@updateStatus` (archive/restore/quota) |
| `GET` | `/admin/mods` | `admin.mods.index` | `AdminModController@index` |
| `GET` | `/admin/mods/{mod}` | `admin.mods.show` | `AdminModController@show` |
| `PATCH` | `/admin/mod-versions/{modVersion}/status` | `admin.mod-versions.status.update` | `AdminModController@updateVersionStatus` (revoke/deprecate) |
| `GET` | `/admin/convoys` | `admin.convoys.index` | `AdminConvoyProfileController@index` |
| `GET` | `/admin/convoys/{convoyProfile}` | `admin.convoys.show` | `AdminConvoyProfileController@show` |
| `GET` | `/admin/sync` | `admin.sync.index` | `AdminSyncController@index` |
| `GET` | `/admin/storage` | `admin.storage.index` | `AdminStorageController@index` |
| `GET` | `/admin/reports` | `admin.reports.index` | `AdminReportController@index` |
| `GET` | `/admin/reports/export/{type}` | `admin.reports.export` | `AdminReportController@export` (CSV) |
| `GET` | `/admin/audit-logs` | `admin.audit-logs.index` | `AdminAuditLogController@index` |
| `GET` | `/admin/settings` | `admin.settings.index` | `AdminSettingsController@index` |
| `POST` | `/admin/settings/game-versions` | `admin.settings.game-versions.store` | `AdminSettingsController@storeGameVersion` |
| `PATCH` | `/admin/settings/games/{game}` | `admin.settings.games.update` | `AdminSettingsController@updateGame` |

### 5.2 Controllers, Services & FormRequests
- **Middleware:** `App\Http\Middleware\EnsurePlatformAdmin` (registered as `platform.admin` in `bootstrap/app.php`).
- **Controllers (`App\Http\Controllers\Web\Admin\*`):** Thin controllers delegating to services and existing domain services (`GroupManagementService`, `DeviceRegistrationService`, `ModCatalogueService`, `AuditLoggerService`).
- **Services (`App\Services\Admin\*`):**
  - `PlatformAnalyticsService` — Overview KPIs, storage calculations, and historical report aggregates using single grouped SQL queries (`GROUP BY` / `withCount` / `withSum`).
  - `PlatformModerationService` — Account suspension/reactivation, group archive/restore/quota updates, and mod version revocation, all wrapped in `DB::transaction()` with mandatory `AuditLoggerService::log('admin.*', ...)` entries.
- **FormRequests (`App\Http\Requests\Web\Admin\*`):**
  - `UpdateUserStatusRequest`, `UpdateGroupAdminRequest`, `UpdateModVersionStatusRequest`, `StoreGameVersionRequest`, `UpdateGameRequest`.

### 5.3 Performance & Query Discipline
- Every index table uses server-side pagination (`paginate(20)->withQueryString()`).
- Aggregate counts (`memberships_count`, `mods_count`, `convoy_profiles_count`) and byte sums (`withSum('versions as s3_bytes_sum', 'file_size_bytes')`) are computed in SQL rather than hydrating collections in PHP.
- LIKE searches escape `%`, `_`, and `\` using `addcslashes($search, '\\%_')`.

---

## 6. Step 6: Security, Safeguards & Accountability

1. **Mandatory Audit Logging for Every Admin Mutation:**
   - Every state change executed in `/admin/*` records an immutable `AuditLog` entry via `AuditLoggerService` with `event` prefixed `admin.` (e.g. `admin.user.suspended`, `admin.user.reactivated`, `admin.group.archived`, `admin.group.restored`, `admin.group.quota_updated`, `admin.mod_version.revoked`, `admin.game_version.created`), capturing the administrator's `user_id`, target `auditable_type`/`auditable_id`, `old_values`, `new_values`, `ip_address`, and `user_agent`.
2. **Self-Lockout & Super-Admin Safeguards:**
   - An administrator **cannot** suspend, demote, or delete their own account (`$actor->id === $targetUser->id` is rejected in both Policy and Service layers).
   - Group deletion uses soft-deletion (`archive`) rather than destructive hard `DELETE`, preserving audit and sync referential integrity.
   - High-impact mutations require a reason (`reason` field validated in `FormRequest`, stored in `audit_logs.new_values['reason']`) and a confirmation modal in the UI.
3. **Zero Secret Exposure:**
   - `User::$hidden` already hides `password` and `remember_token`; personal access tokens (`token` hashes) and `.env` secrets (`APP_KEY`, `DB_PASSWORD`, `AWS_SECRET_ACCESS_KEY`) are **never** queried, logged, or rendered on `/admin/settings` or `/admin/audit-logs`.

---

## 7. Architectural Decisions Requested Before Implementation

Please confirm or amend the following 5 decisions (**AD-1** through **AD-5**):

1. **AD-1 (Platform Admin Tiers):**
   - *(Recommended)* Add a nullable `platform_role` string column (`super_admin` | `platform_admin`) to `users` alongside `is_platform_admin` so we can distinguish **Platform Super Admin** (full access including system settings & admin role management) from **Platform Administrator** (operational moderation & monitoring), while keeping `is_platform_admin` in sync for backward compatibility.
2. **AD-2 (User Account Suspension):**
   - *(Recommended)* Add `suspended_at` (`timestamp nullable`) and `suspended_reason` (`string nullable`, `max:500`) to `users` via a non-destructive migration so administrators can suspend and reactivate user accounts globally (automatically revoking active Sanctum tokens and blocking login/session access when suspended).
3. **AD-3 (Group Suspension vs. Archiving):**
   - *(Recommended)* Use the existing `groups.deleted_at` (`SoftDeletes`) column to archive/suspend groups and restore them, without adding a redundant status column on `groups`.
4. **AD-4 (Reported Groups & Reported Mods):**
   - *(Recommended)* Add a lightweight polymorphic `moderation_reports` table (`id`, `reporter_id`, `reportable_type`, `reportable_id`, `reason`, `status`, `reviewed_by`, `review_notes`, `reviewed_at`, `timestamps`) so "Investigate reported groups" and "Review reported mods" are backed by real database records rather than placeholders.
5. **AD-5 (System Settings Scope):**
   - *(Recommended)* Manage `games`, `game_versions`, and `game_dlcs` directly in the database via `/admin/settings` and display read-only non-secret platform/storage/maintenance configuration (no `platform_settings` table needed in Phase 1).

---

## 8. Implementation Sequence (Upon Approval)

1. **Phase A — Schema & Access Control Foundation:**
   - Create additive migration for **AD-1**, **AD-2**, and **AD-4** (`platform_role`, `suspended_at`, `suspended_reason` on `users`; `moderation_reports` table).
   - Implement `EnsurePlatformAdmin` middleware (`platform.admin`), suspended-user check, Gates (`access-admin-dashboard`, `manage-platform-settings`), and unit/feature security tests.
2. **Phase B — Admin Layout, Overview, Storage & Reports:**
   - Build `<x-admin.layout>` + reusable table/filter/modal components.
   - Implement `PlatformAnalyticsService`, `AdminDashboardController`, `AdminStorageController`, `AdminReportController` (with CSV export), and `AdminAuditLogController`.
3. **Phase C — Entity Management & Moderation (Users, Groups, Mods, Convoys, Sync, Settings):**
   - Implement `PlatformModerationService` and `AdminUserController`, `AdminGroupController`, `AdminModController`, `AdminConvoyProfileController`, `AdminSyncController`, and `AdminSettingsController` with dedicated `FormRequest` validation and `AuditLog` recording.
4. **Phase D — Automated Testing & Documentation:**
   - Write comprehensive PHPUnit 12 feature tests covering positive admin workflows, cross-role negative tests (ensuring Group Owners/Admins/Moderators/Members receive `403 Forbidden` on every `/admin/*` route), self-suspension prevention, and audit log verification.

---

## 9. Acceptance Criteria

1. Ordinary users, Group Moderators, Group Administrators, and Group Owners receive `403 Forbidden` on every `/admin/*` route.
2. Only authenticated, verified users with platform administrator privileges can access `/admin/*`.
3. Every statistic, table, chart, and report on the Admin Dashboard is powered by real database queries (zero fabricated or hardcoded metrics).
4. All mutating admin actions validate input via `FormRequest`, authorize via Gate/Policy, prevent self-lockout, and write an immutable `AuditLog` entry recording the administrator and target.
5. Sensitive fields (`password`, `remember_token`, Sanctum token hashes, `.env` keys) are never exposed in HTML, JSON, or CSV exports.
6. Responsive layout works cleanly across desktop, tablet, and mobile viewports using the Ghost ModSync dark visual identity.
7. Full PHPUnit test suite and Vite asset build pass with zero regressions.
