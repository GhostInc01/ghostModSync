# 15 — User Dashboard Quality Assurance & Security Review Report

**Date:** 2026-11-10  
**Scope:** Ghost ModSync User Dashboard (`/dashboard`) and Group Discovery (`/groups/discover`)  
**Role:** Senior QA Engineer, Laravel Security Engineer & UI/UX Reviewer  
**Status:** PASSED (All 7 discovered issues remediated and verified; 80/80 automated tests passing)

---

## 1. Overall Assessment

The Ghost ModSync User Dashboard ([`resources/views/dashboard.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/dashboard.blade.php)) and Public VTC Discovery directory ([`resources/views/groups/discover.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/groups/discover.blade.php)) were audited against the confirmed business decisions in [PROJECT_CONTEXT.md](file:///c:/xampp/htdocs/ghostModSync/PROJECT_CONTEXT.md), [docs/13-user-dashboard-implementation-plan.md](file:///c:/xampp/htdocs/ghostModSync/docs/13-user-dashboard-implementation-plan.md), and [docs/14-user-dashboard-implementation.md](file:///c:/xampp/htdocs/ghostModSync/docs/14-user-dashboard-implementation.md).

- **Architecture & Data Integrity:** All KPIs, group cards, convoy profiles, sync telemetry, devices, and activity items are backed by real Eloquent queries inside [`DashboardOverviewService`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Dashboard/DashboardOverviewService.php). Zero fake or hardcoded statistics exist.
- **Security & Multi-VTC Isolation:** All dashboard queries are strictly scoped to `$request->user()->fresh()` and groups where the user holds an `Active` membership (`MembershipStatus::Active`). Request parameters cannot influence whose data is queried.
- **Visual & Responsive Design:** The dashboard uses the dark `gms.*` design system (`#080B12` background, `#101522` surface, `#151D2B` cards, `#00F0FF` cyan accent, `#8B5CF6` purple accent, `#F59E0B` amber accent) and typography (`Plus Jakarta Sans`, `Rajdhani`/`Chakra Petch`, `JetBrains Mono`) established on the landing page.

During the QA and security audit, **7 issues** (3 Medium, 4 Low) were identified, fixed, and covered by new automated regression tests.

---

## 2. Review Areas Summary

| # | Review Area | Verdict | Key Verification Notes |
| :--- | :--- | :--- | :--- |
| **1** | **Authentication & Route Protection** | **PASS** | `/dashboard` and `/groups/discover` enforce `['auth', 'verified']` middleware. Unauthenticated users redirect to `/login`; unverified users redirect to `/verify-email`. |
| **2** | **User-Specific Data Isolation** | **PASS** | Queries use `$request->user()->fresh()->id` exclusively. Query parameters (`?user_id=`, `?group_id=`) are ignored. |
| **3** | **Group Membership & Permissions** | **PASS** | Only `MembershipStatus::Active` memberships grant dashboard visibility. Pending sync approvals only appear for groups where `$membership->role->canApproveSync()` is `true`. `is_platform_admin` grants zero extra visibility on the user dashboard. |
| **4** | **Dashboard Statistics Accuracy** | **PASS** | All 5 summary cards (`groups`, `profiles`, `mods`, `devices`, `last_sync`) reflect exact database counts. Fixed QA-04 so deactivated mods (`is_active = false`) do not appear in recent updates or group game badges. |
| **5** | **Database Query Performance** | **PASS** | Member counts and per-group games use grouped batch queries (`GROUP BY group_id`). Automated N+1 test confirms constant query count (`18` queries whether the user belongs to 2 groups or 8 groups). |
| **6** | **Navigation & Route Correctness** | **PASS** | Live links (`dashboard`, `groups.index`, `groups.discover`, `devices.index`, `profile.edit`, `logout`) resolve to valid routes. Planned features (`My Mods`, `Convoy Profiles`, `Synchronisation`, `Notifications`) render as non-clickable `aria-disabled="true"` items with a `Soon` badge. Fixed QA-01 to prevent group slugs from colliding with `/groups/discover`. |
| **7** | **Mobile Responsiveness (`320px`–`767px`)** | **PASS** | Off-canvas sidebar drawer (`-translate-x-full`), backdrop overlay, close button, `Esc` listener, `min-h-[44px]` touch targets, single-column stacked grids, and `min-w-0 break-words` text wrapping. |
| **8** | **Desktop & Tablet Responsiveness (`768px`–`1440px+`)** | **PASS** | Tablet (`md`/`sm`): 2-column summary and group grids with collapsible off-canvas sidebar (`<1024px`). Desktop (`lg`/`xl`): fixed `w-64` (`16rem`) sidebar, `lg:pl-64` content offset, 5-column KPI strip, and `2/3 + 1/3` (`xl:col-span-2` + `xl:col-span-1`) main grid. |
| **9** | **UI Consistency with Landing Page** | **PASS** | Shares `gms.*` Tailwind tokens, Bunny Fonts (`Plus Jakarta Sans`, `Rajdhani`, `Chakra Petch`, `JetBrains Mono`), cyan glow primary buttons, focus-visible rings, and reduced-motion CSS rules. |
| **10** | **Error Handling & Empty States** | **PASS** | Every panel has a dedicated `<x-dashboard.empty-state>` when empty. Service exceptions are caught in `DashboardController`, reported via `report($e)`, and rendered as an accessible `role="alert"` fallback card. |
| **11** | **Security & Authorisation** | **PASS** | Audited for IDOR, cross-tenant leakage, mass assignment, wildcard injection, and XSS. Remediated QA-01, QA-02, and QA-03. |
| **12** | **Automated Test Coverage** | **PASS** | 26 dedicated dashboard/discovery feature tests (`UserDashboardTest` + `UserDashboardExtendedTest`); full suite at **80 tests, 548 assertions passing**. |

---

## 3. Issues Discovered & Fixes Implemented

### QA-01 — Reserved Group Slug Route Collision (`/groups/discover`, `/groups/create`, `/groups/invitations`)
- **Severity:** **Medium** (Routing & Functional Integrity)
- **Location:** [`app/Http/Requests/Web/StoreGroupRequest.php`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Requests/Web/StoreGroupRequest.php), [`app/Services/Groups/GroupManagementService.php`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Groups/GroupManagementService.php)
- **Description:** `routes/web.php` registers `/groups/create`, `/groups/discover`, and `/groups/invitations/{token}/accept` above `/groups/{group}`. Previously, a user could create a group with `slug: "discover"` or `name: "Discover"` (auto-slugged to `discover`), causing `route('groups.show', $group)` (`/groups/discover`) to be intercepted by `GroupDiscoveryController@index`.
- **Fix Implemented:**
  - Added `Rule::notIn(['create', 'discover', 'invitations'])` to `StoreGroupRequest::rules()['slug']`.
  - Updated `GroupManagementService::createGroup()` to treat `['create', 'discover', 'invitations']` as reserved slugs and automatically append a random 4-character suffix when a group name slugifies to a reserved word.

### QA-02 — `pendingInvitations()` Case-Sensitivity & Banned Membership Exposure
- **Severity:** **Medium** (Security / Tenant Isolation & Authorisation)
- **Location:** [`app/Services/Dashboard/DashboardOverviewService.php`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Dashboard/DashboardOverviewService.php)
- **Description:**
  1. `GroupManagementService::createInvitation()` stores invitation emails in lowercase (`strtolower($email)`), whereas `DashboardOverviewService::pendingInvitations()` queried `->where('email', $user->email)` without case normalization, failing on case-sensitive collations (such as SQLite) if the user's email contained uppercase characters.
  2. `pendingInvitations()` only excluded groups where the user had an `Active` membership. If a user was `Banned` (`MembershipStatus::Banned`) from a private group, an unrevoked invitation for their email would still appear in "Needs your attention", leaking the private group's name and presenting an unusable action.
- **Fix Implemented:**
  - Updated `pendingInvitations()` to match `LOWER(email) = ?` against `strtolower($user->email)`.
  - Excluded groups where the user holds either an `Active` or `Banned` membership.

### QA-03 — `GroupDiscoveryController` Policy Check, Enum Consistency & Card Parity
- **Severity:** **Low-Medium** (Coding Standards, Security & UI Consistency)
- **Location:** [`app/Http/Controllers/Web/GroupDiscoveryController.php`](file:///c:/xampp/htdocs/ghostModSync/app/Http/Controllers/Web/GroupDiscoveryController.php), [`resources/views/groups/discover.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/groups/discover.blade.php)
- **Description:**
  1. `GroupDiscoveryController@index` did not explicitly call `$this->authorize('viewAny', Group::class)` as required by `AGENTS.md` Rule 4, and used a raw string `'active'` instead of `MembershipStatus::Active->value`.
  2. `discover.blade.php` rendered `Banned`/`Suspended` membership badges in `amber` instead of `red` and omitted the group logo / tag-initials badge shown on dashboard group cards.
- **Fix Implemented:**
  - Added `AuthorizesRequests` and `$this->authorize('viewAny', Group::class)` to `GroupDiscoveryController@index` and replaced `'active'` with `MembershipStatus::Active->value`.
  - Updated `discover.blade.php` to display group logos/tag initials and map `Suspended`/`Banned` badges to `red`.

### QA-04 — Inactive Mods (`is_active = false`) Included in Recent Mod Updates & Group Game Badges
- **Severity:** **Medium** (Dashboard Data Accuracy)
- **Location:** [`app/Services/Dashboard/DashboardOverviewService.php`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Dashboard/DashboardOverviewService.php)
- **Description:** While `availableModCount()` filtered `Mod::where('is_active', true)`, `recentModVersions()` and `gamesByGroup()` did not filter out inactive mods (`is_active = false`). Deactivated mods could therefore still appear in "Recent mod updates" and add game badges to group cards.
- **Fix Implemented:** Added `->where('is_active', true)` to the `Mod` query in `gamesByGroup()` and to the `whereHas('mod', ...)` constraint in `recentModVersions()`.

### QA-05 — Passive Wording for Self-Authored Mod & Convoy Profile Audit Events
- **Severity:** **Low** (UX & Activity Feed Clarity)
- **Location:** [`app/Services/Dashboard/DashboardOverviewService.php`](file:///c:/xampp/htdocs/ghostModSync/app/Services/Dashboard/DashboardOverviewService.php)
- **Description:** When the signed-in user uploaded a mod version or created/updated a convoy profile in one of their active groups, `recentActivity()` displayed passive third-person text (*"A mod version was uploaded"*) rather than first-person text (*"You uploaded a mod version"*).
- **Fix Implemented:** Added `OWN_GROUP_EVENTS` mapping in `DashboardOverviewService` so group events triggered by `$user->id` render in the first person.

### QA-06 — `UserFactory` Strict-Mode Attribute Hydration & Topbar Avatar Image Rendering
- **Severity:** **Low** (Strict Model Safety & UI Completeness)
- **Location:** [`database/factories/UserFactory.php`](file:///c:/xampp/htdocs/ghostModSync/database/factories/UserFactory.php), [`resources/views/components/dashboard/layout.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/components/dashboard/layout.blade.php)
- **Description:**
  1. With `Model::shouldBeStrict()` active in non-production environments, `UserFactory` omitted the nullable columns from migration `2026_11_09_100001` (`username`, `steam_id`, `avatar_path`, `last_login_at`).
  2. The top navigation bar always rendered a 1-letter initial circle even when `$user->avatar_path` was populated.
- **Fix Implemented:**
  - Added `'username' => null`, `'steam_id' => null`, `'avatar_path' => null`, and `'last_login_at' => null` to `UserFactory::definition()`.
  - Updated `layout.blade.php` to render `<img src="{{ Storage::disk('public')->url($user->avatar_path) }}">` when `$user->avatar_path` is set, falling back to the initial circle.

### QA-07 — Accessibility Grammar & Mobile Text Wrapping (`320px`)
- **Severity:** **Low** (Accessibility & Mobile Responsiveness)
- **Location:** [`resources/views/components/dashboard/layout.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/components/dashboard/layout.blade.php), [`resources/views/dashboard.blade.php`](file:///c:/xampp/htdocs/ghostModSync/resources/views/dashboard.blade.php)
- **Description:**
  1. When `$attentionCount === 1`, the topbar bell `aria-label` rendered `"1 item need your attention"` instead of `"1 item needs your attention"`.
  2. Long unbroken group/profile names inside "Needs your attention" flex rows needed `min-w-0 break-words` and `shrink-0` on action buttons to prevent horizontal overflow on `320px` viewports.
  3. Sidebar active state for `My Groups` now highlights on `groups.*` (excluding `groups.discover`).
- **Fix Implemented:** Fixed singular/plural `aria-label` grammar, added `min-w-0 break-words` and `shrink-0` classes in `dashboard.blade.php`, and refined the `My Groups` active route matcher.

---

## 4. Security Verification Matrix

| Security Vector | Check Performed | Result |
| :--- | :--- | :--- |
| **IDOR (Insecure Direct Object Reference)** | Passed `?user_id=`, `?user=`, and `?group_id=` query parameters to `/dashboard`. | **SAFE** — `DashboardController` ignores request parameters and scopes all queries to `$request->user()->fresh()`. |
| **Unauthorised Group Access** | Created private groups (`GroupVisibility::Private`), foreign groups, and `Suspended`/`Banned` memberships. | **SAFE** — None of their names, profiles, mods, invitations, or audit events appear on `/dashboard` or `/groups/discover`. |
| **Cross-User Data Exposure** | Created devices, sync sessions, join requests, invitations, and personal audit logs for other users in the same group. | **SAFE** — Personal telemetry (`devices`, `sync_sessions`, `joinRequests`, `invitations`, `OWN_EVENTS` audit logs) is strictly filtered by `user_id = $user->id`. |
| **Privilege Escalation / Role Bypass** | Tested `SyncApproval` review visibility across `Member`, `ModManager`, and `ConvoyDispatcher`, plus `is_platform_admin = true`. | **SAFE** — Only roles where `GroupRole::canApproveSync()` is `true` see pending sync approvals. `is_platform_admin` is not `$fillable`, is `$hidden`, and grants no extra dashboard data. |
| **SQL / LIKE Wildcard Injection** | Passed `%`, `_`, `\`, and array payloads (`?q[]=x`) to `/groups/discover`. | **SAFE** — `DiscoverGroupsRequest` validates `q` as `nullable|string|max:100`, and `GroupDiscoveryController` escapes `\`, `%`, and `_` with `addcslashes()`. |
| **Sensitive Data Exposure** | Inspected `AuditLog` and `User` queries and rendered HTML. | **SAFE** — `AuditLog` selects only `id, group_id, user_id, event, created_at` (never `ip_address`, `user_agent`, `old_values`, or `new_values`). |

---

## 5. Visual & Responsive Review

- **Desktop (`1440px` / `1024px+`):**
  - Fixed `w-64` (`16rem`) left sidebar (`lg:translate-x-0`) paired with `lg:pl-64` main wrapper — zero sidebar overlap.
  - 5-column KPI strip (`xl:grid-cols-5`) and `2/3 + 1/3` (`xl:grid-cols-3` with `xl:col-span-2`) content layout align cleanly inside `max-w-7xl`.
- **Tablet (`768px`–`1023px`):**
  - Sidebar collapses off-canvas (`-translate-x-full`) and opens via the topbar toggle (`lg:hidden`) with a semi-transparent backdrop (`bg-black/60`).
  - Summary cards (`sm:grid-cols-2`) and group cards (`md:grid-cols-2`) arrange in balanced 2-column grids.
- **Mobile (`320px`–`767px`):**
  - Single-column stack (`grid-cols-1`), compact topbar (breadcrumb and username text hide below `sm`, leaving the hamburger button, page title, attention bell, and avatar menu).
  - All interactive controls meet `min-h-[44px]` touch target sizing, and `truncate` / `line-clamp-2` / `min-w-0 break-words` prevent horizontal overflow.

---

## 6. Automated Tests Executed & Actual Results

Executed via PHPUnit 12.5.43 (`PHP 8.4.27`) and Laravel Pint:

```text
{"tool":"pint","result":"passed"}
PHPUnit 12.5.43 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.4.27
Configuration: C:\xampp\htdocs\ghostModSync\phpunit.xml

OK (80 tests, 548 assertions)
```

Frontend production build verified via Vite 7.3.7 (`npm.cmd run build`):
```text
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-C5hJmJbR.css   71.55 kB │ gzip: 11.81 kB
public/build/assets/app-DDpvETUX.js   114.55 kB │ gzip: 41.40 kB
✓ built in 10.82s
```

---

## 7. Outstanding Items (Planned Future Phases)

1. **Restyling Legacy Breeze Views (Decision D5):** Existing `groups.index`, `groups.show`, `groups.create`, `devices.index`, and `profile.edit` views remain functional on the default Breeze layout and are scheduled to be migrated onto `<x-dashboard.layout>` in the next UI phase.
2. **Standalone Read Pages for Planned Sidebar Items:** `My Mods`, `Convoy Profiles`, `Synchronisation`, and `Notifications` are intentionally marked `Soon` (`aria-disabled="true"`) until their dedicated standalone routes and views are scheduled.
3. **Per-Group Mod Approval Filtering on KPI Count:** `availableModCount` currently counts active mods in the user's groups with a `ready` version; once group mod approval workflows (`GroupModApproval`) are exposed in the web UI, this count can optionally filter by approval status when `group.require_mod_approval` is enabled.

---

## 8. Final Recommendation

**APPROVED FOR SIGN-OFF.** The Ghost ModSync User Dashboard is secure, tenant-isolated, N+1-free, visually aligned with the Ghost ModSync design language, and backed by 80 passing automated tests. Ready to proceed to the next approved development stage upon your confirmation.
