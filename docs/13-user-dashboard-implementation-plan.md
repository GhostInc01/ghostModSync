# 13 — User Dashboard Implementation Plan

**Status:** APPROVED (D1–D6 as recommended). **Phase 1 implemented 2026-11-10** (layout + Overview). Phase 2 (mods/convoys/sync read pages) and D5 (migrating groups/devices/profile pages) remain.

**Implementation notes:**
- D6 correction: `is_platform_admin` was already **not** in `User::$fillable` (R1 was a misreading), so no change was needed; it is also hidden from serialisation. A dashboard test confirms the flag grants no extra data.
- The sidebar only links existing routes (Overview, My Groups, Desktop Devices, Account).
- The controller re-reads the user (`fresh()`) because the models run in strict mode (missing attributes throw).
- `phpunit` cannot read `vendor/autoload.php` inside the command sandbox (`is_readable` returns false), so tests were run outside it; they use in-memory SQLite, never the MySQL database.
**Date:** 2026-11-10
**Scope:** Authenticated *user* dashboard (not platform administration).

Legend: ✅ Already implemented · 🟡 Partially implemented · ❌ Not implemented · ❓ Requires architectural decision

---

## 1. Audit Findings

### 1.1 Stack and structure
- Laravel 13, PHP 8.4, MySQL, Blade, Alpine.js, Tailwind 3 (`@tailwindcss/forms` only), Vite 7. Breeze (Blade) and Sanctum are installed. Composer also has `league/flysystem-aws-s3-v3`.
- Modular-monolith layout is in place: `app/Enums` (13), `app/Models` (24), `app/Policies` (4), `app/Services/*` (9), `Http/Controllers/{Web,Api/V1,Auth}`, `Http/Requests/{Web,Api/V1}`, `Http/Resources/V1`.
- 12 migrations (users extension, games/versions, devices, audit logs, groups/memberships, mods/versions, convoy profiles/sync, join requests/approvals, DLCs, profile revisions/sync-session items). Seeders include `SfgPilotSeeder`.
- Docs 01–12 exist. This plan is doc 13.

### 1.2 Landing page ↔ authenticated app
| Item | Finding |
| :--- | :--- |
| Landing | `WelcomeController@index` → `welcome.blade.php` + 15 `components/landing/*` partials, standalone `<body>`; it does **not** use `x-app-layout`. |
| Landing → app | Navbar links to `route('login')`, `route('register')`, and `route('dashboard')` (when authenticated). |
| Post-login redirect | `AuthenticatedSessionController` → `redirect()->intended(route('dashboard'))`. Registration → `route('dashboard')`. Email verification → `route('dashboard').'?verified=1'`. |
| `/dashboard` | A closure in `routes/web.php` returning `dashboard.blade.php` (the stock Breeze “You're logged in!” card). Protected by `auth` + `verified`. |
| Authenticated shell | `layouts/app.blade.php` + `layouts/navigation.blade.php`: **stock Breeze light/gray styling**. Nav items: Dashboard, VTCs & Groups, Desktop Devices, plus the Breeze profile dropdown. |

**Key finding:** the landing page has a mature dark “gms” identity, but every authenticated page (dashboard, groups, devices, profile) still uses default Breeze gray/indigo styling. Visually the landing → login → dashboard journey is inconsistent, and `groups.*` / `devices.*` views are unthemed.

### 1.3 Design system (from the landing page)
- Tokens already in `tailwind.config.js` (`gms.*`): `bg #080B12`, `surface #101522`, `card #151D2B`, `cyan #00F0FF` (primary), `purple #8B5CF6`, `amber #F59E0B`, `text #F5F7FA`, `muted #A7B0C0`, `border #1E293B`.
- Typography: Plus Jakarta Sans (body), plus Chakra Petch / Rajdhani / JetBrains Mono (loaded in `welcome.blade.php` via fonts.bunny.net). `layouts/app.blade.php` loads only Figtree.
- Recurring patterns: `rounded-lg border border-gms-border bg-gms-card/60`, cyan solid primary button with glow `shadow-[0_0_20px_rgba(0,240,255,0.25)]`, `focus-visible:ring-2 focus-visible:ring-gms-cyan`, `prefers-reduced-motion` handling in `app.css`.
- Landing components are in `components/landing/*` (including an `icon` component) and are **not** generic; the dashboard must not depend on them, but should reuse the `icon` component pattern and tokens.
- Tailwind `darkMode` is not configured (default `media`). Dashboard will be **dark-only**, so no dark-mode toggling is needed. Use the `gms` tokens directly rather than `dark:` variants.
- Breeze form components (`text-input`, `primary-button`, `modal`, `dropdown`, …) are gray/indigo themed. They are shared with login/register, so **they must not be restyled globally** in this phase.

### 1.4 Authentication and access
| Question | Answer |
| :--- | :--- |
| How do users authenticate? | ✅ Breeze session (`web` guard); `MustVerifyEmail` enforced via `verified` middleware on all app routes. Desktop: ✅ Sanctum device tokens (`/api/v1/auth/device-token`, `auth:sanctum` + `active.device` + abilities). |
| Where redirected? | ✅ `/dashboard` (see 1.2). |
| Route protection | ✅ `auth`+`verified` group; `scopeBindings()` on group-nested routes; `throttle` on API. |
| Multi-group membership | ✅ `users` ↔ `groups` via `group_memberships` (`User::groups()`, `User::groupMemberships()`). |
| Per-group roles | ✅ `GroupRole` enum: owner, admin, moderator, mod_manager, convoy_dispatcher, member, with capability helpers; stored on the pivot. |
| Group permissions | ✅ `GroupPermissionService` + `GroupPolicy`, `ModPolicy`, `ConvoyProfilePolicy`, `DevicePolicy`. |
| Platform admin vs group admin | 🟡 `users.is_platform_admin` boolean exists and is **honoured as a super-user bypass** in `GroupPermissionService`, `DevicePolicy`, `GroupManagementService`. There is **no platform-admin route, middleware, Gate, UI, or tests** (no `/admin`). Nothing platform-admin related is exposed to ordinary users today. |
| Can ordinary users reach platform admin? | No — because no such functionality exists. The risk is future: `is_platform_admin` is mass-assignable? (`User.php` line 53 lists it — see Risk R1). |

### 1.5 Existing domain and data
Models and relationships present: `Group` (owner, memberships, members, invitations, joinRequests, modApprovals, mods, convoyProfiles), `GroupMembership`, `Mod`/`ModVersion`/`ModSource`/`ModDependency`, `GroupModApproval`, `ConvoyProfile` (+ mods, revisions, currentRevision, syncApprovals, syncSessions), `SyncApproval`, `SyncSession`/`SyncSessionItem`, `Device`, `Game`/`GameVersion`/`GameDlc`, `AuditLog`.

Enums that drive dashboard states: `MembershipStatus` (active/pending/invited/suspended/banned), `SyncStatus` (in_sync/out_of_sync/syncing/pending_approval/failed), `SyncPolicyMode`, `ModApprovalStatus`, `ModVersionStatus`, `JoinRequestStatus`.

### 1.6 Existing web routes (names)
`welcome`, `pricing.notify`, `legal.*`, `dashboard`, `devices.{index,update,destroy}`, `groups.{index,create,store,show,members,join-requests,invitations,settings,update,destroy,transfer-ownership,join,invitations.accept,memberships.*,invitations.*,join-requests.review}`, `groups.mods.store`, `groups.mods.versions.store`, `groups.mods.versions.dependencies.store`, `groups.profiles.store`, `groups.profiles.mods.sync`, `groups.profiles.approvals.review`, `profile.*`. Convention: dotted resource-style names, `{group}` binding with `scopeBindings()`.

**Gap:** mods and convoy profiles have write (POST/PUT/PATCH) web routes but **no read/index/show web routes or views**. The group `show` page is the only place they can currently surface.

### 1.7 Existing tests
`DeviceManagementTest`, `GroupManagementTest`, `ModAndConvoySyncTest`, `Phase1IntegrationAuditTest`, `WelcomeLandingPageTest`, `ProfileTest`, `Auth/*` (6 files), `Api/V1/AuthenticationAndApiFoundationTest`. **No test covers `/dashboard` content.** Baseline test run was not executed during this audit; it must be run before implementation (see §9).

---

## 2. Design Direction

Goal: a **dark SaaS control-centre** that is visibly the same product as the landing page.

- **Palette:** `gms.bg` page, `gms.surface` sidebar/header, `gms.card` panels, `gms.border` dividers, cyan for primary/active, purple for secondary/info, amber for warnings/pending, red (Tailwind default `red-400/500`) for failures — *(red is the only addition, and it is a standard Tailwind colour, not a new design system)*.
- **Typography:** Plus Jakarta Sans for body; Chakra Petch for page titles/KPI numbers; JetBrains Mono for versions, hashes, and tags. Load the same Bunny Fonts URL used by the landing page.
- **Components:** panels with `rounded-xl border border-gms-border bg-gms-card/60`, cyan active nav indicator, status badges (colour + text/icon, never colour alone), subtle cyan glow only on primary actions.
- **Accessibility:** WCAG AA contrast (muted `#A7B0C0` on `#101522` passes), visible focus rings, skip link, `aria-current` on active nav, `prefers-reduced-motion` respected.

**Approach to the shell (❓ Decision D1):** create a **new** `layouts/dashboard.blade.php` + `<x-dashboard-layout>` component rather than restyling `layouts/app.blade.php`. This leaves login/register/profile/Breeze untouched and allows existing pages (`groups.*`, `devices.*`) to be migrated one at a time.

---

## 3. Proposed Layout

```
┌───────────┬──────────────────────────────────────────────┐
│ Sidebar   │ Top bar: [☰] page title · group switcher ·   │
│ (logo)    │          notifications*(later) · user menu   │
│ Overview  ├──────────────────────────────────────────────┤
│ My Groups │ Content (max-w-7xl, grid)                    │
│ Mods      │                                              │
│ Convoys   │                                              │
│ Sync      │                                              │
│ Devices   │                                              │
│ Settings  │                                              │
└───────────┴──────────────────────────────────────────────┘
```

### 3.1 Sidebar navigation
| Item | Route | Status |
| :--- | :--- | :--- |
| Overview | `dashboard` | 🟡 route exists, page is a placeholder |
| My Groups | `groups.index` | 🟡 page exists (unthemed) |
| Mods | `mods.index` (new) | ❌ no read route/view |
| Convoy Profiles | `convoys.index` (new) | ❌ no read route/view |
| Sync Activity | `sync.index` (new) | ❌ no web view (data model ✅) |
| Desktop Devices | `devices.index` | 🟡 page exists (unthemed) |
| Account | `profile.edit` | ✅ (Breeze, unthemed) |
| Download Desktop Client | — | ❓ D3: no installer/release exists; show only a “coming soon” disabled item, or omit |
| Platform Admin | — | ❌ not built; **excluded from this phase**; if ever added, gated server-side by `is_platform_admin` and never rendered for other users |

Mods / Convoys / Sync entries are shown **only after their pages exist** (no dead links).

### 3.2 Header
Page title + breadcrumb, mobile hamburger, **active group switcher** (❓ D2, see below), user menu (profile, log out via existing POST route). Email-verified badge if relevant. Notifications bell is **not implemented and excluded** (no notifications model).

### 3.3 Overview page components
Each component displays only real queried data, scoped to the signed-in user's **active** memberships.

| # | Component | Source | Status |
| :--- | :--- | :--- | :--- |
| 1 | Welcome header (name, last login) | `users.name`, `last_login_at` | ✅ data / ❌ UI |
| 2 | KPI strip: groups joined, pending actions, registered devices, profiles out of sync | counts via relations | ❌ UI; data ✅ |
| 3 | “Needs your attention” list: pending invitations, own pending join requests, sync approvals awaiting review (managers only), failed sync sessions | `GroupInvitation`, `GroupJoinRequest`, `SyncApproval`, `SyncSession` | 🟡 models ✅; invitations are token-based and address by email (see R4) |
| 4 | My groups cards (tag, name, role badge, visibility, game(s), member count) | `groupMemberships` + `group` | 🟡 `groups.index` already lists these |
| 5 | Convoy profiles status per group (game, revision, sync policy, my last `SyncStatus`) | `ConvoyProfile`, `SyncSession` | ❌ UI; relation chain ✅ |
| 6 | Recent sync activity (last 5 sessions) | `SyncSession` + `items` | ❌ UI; data ✅ |
| 7 | Devices summary (name, last seen, status) | `Device.last_seen_at` | ❌ UI; data ✅ |
| 8 | Recent mod updates in my groups (new `ModVersion`s) | `ModVersion` via groups | ❌ UI; data ✅ (needs eager-loading plan) |
| 9 | Quick actions (Create group, Join group, Register device*) filtered by policy | `GroupPolicy::create` etc. | ❌ |
| 10 | Onboarding checklist (join a group → register device → run first sync) derived from real data | computed | ❌ |

\* Device registration is performed by the desktop client via API; the web only lists/renames/revokes. The quick action would link to the device page, not create devices.

**Explicitly excluded:** download counts, traffic graphs, storage usage, “online users”, and trend percentages — there is no data source to support them. We will not fabricate or hardcode them.

---

## 4. Backend Plan

### 4.1 Routes (names follow existing conventions)
| Route | Name | Status |
| :--- | :--- | :--- |
| `GET /dashboard` → `DashboardController@index` | `dashboard` (**name preserved**) | 🟡 replace closure |
| `GET /dashboard/summary` (JSON, optional, for deferred panels) | `dashboard.summary` | ❓ D4 |
| `GET /mods` (aggregate across user's groups) | `mods.index` | ❌ phase 2 |
| `GET /convoys` | `convoys.index` | ❌ phase 2 |
| `GET /sync` | `sync.index` | ❌ phase 2 |

Phase 1 (this dashboard) needs only the first row. Phase 2 pages are follow-ups so that no sidebar link is dead.

### 4.2 Controllers / services / requests
- `App\Http\Controllers\Web\DashboardController@index` (thin; no inline queries).
- `App\Services\Dashboard\DashboardOverviewService::forUser(User): DashboardOverview` returning a DTO/array of the components above. Single place for queries, easy to unit-test and cache later.
- No FormRequest needed for the read-only GET (rule: FormRequests for mutating actions). If the group switcher persists a choice: `SwitchActiveGroupRequest` (❓ D2).
- Authorisation: use `GroupPermissionService`/policies — managers-only widgets (sync approvals, join requests) are included only when `canApproveSync` / `canManageMembers` is true for that group.

### 4.3 Queries and relationships
All existing relations suffice; **no migrations are required for Phase 1**:
- `users → group_memberships (status=active) → groups (soft-deleted excluded)`.
- `groups → convoy_profiles → current revision`, `sync_sessions (user_id = me)`.
- `devices (user_id = me)`.
- `group_invitations` matching the user's email; `group_join_requests (user_id = me, pending)`.
- `sync_approvals` where `convoy_profile.group_id` ∈ groups where I `canApproveSync`.
- Use eager loading + `withCount` to avoid N+1; assert query count in tests. Existing indexes should be reviewed (R5); add indexes only after the need is proven and with a migration.

### 4.4 Tenancy rules
Every query is constrained by `group_id IN (my active group ids)`. Private-group data never appears for non-members. Public groups the user hasn't joined are **not** on the dashboard (discovery stays in `groups.index`).

---

## 5. States

| State | Behaviour |
| :--- | :--- |
| **Empty – no groups** | Hero empty state: “Join or create your first VTC” with `groups.index` / `groups.create` CTAs; KPI strip shows real zeros; onboarding checklist visible. |
| **Empty – panel** | Each panel has its own message (e.g. “No sync activity yet — sessions appear once the desktop client reports in”). Never sample rows. |
| **Loading** | Server-rendered by default (no spinner needed). For optional deferred panels (D4), Alpine-driven skeletons (`animate-pulse`, disabled under reduced motion). |
| **Error** | Panel-level failure fallback (try/catch in service; log, show “Couldn't load this section · Retry”). Full-page errors use the existing Laravel 403/404/500 pages (to be themed — D5). Flash messages from the existing `status` session key. |
| **Unverified** | Handled by existing `verified` middleware → Breeze verification screen (unchanged). |
| **Suspended/banned membership** | Group excluded from active data; show a neutral notice, no private details. |

---

## 6. Responsive behaviour
- **≥1024px:** fixed 16rem sidebar; 3–4 column KPI grid; 2/3 + 1/3 content columns.
- **768–1023px:** collapsible icon-only (or off-canvas) sidebar; 2-column grid.
- **<768px:** off-canvas sidebar via Alpine (`x-data`, focus trap, `Esc` closes, backdrop); single column; tables become stacked cards; touch targets ≥44px.
- No horizontal page scroll at 320px.

---

## 7. Authorisation Requirements
1. `/dashboard` stays `auth` + `verified`.
2. Dashboard shows only data of the authenticated user's active memberships; platform-admin status gives **no** extra data on the user dashboard.
3. Manager-only widgets use existing permission service methods server-side (not just hidden in Blade).
4. Add negative tests: cross-group leakage, suspended members, private groups, guest redirect.
5. Platform admin area (❌ not built) is out of scope. Before any such feature, add an `EnsurePlatformAdmin` middleware/Gate and tests (see R1).

---

## 8. Feature Classification Summary

| Feature | Class |
| :--- | :--- |
| Breeze login/registration, email verification | ✅ |
| Redirect to `dashboard` after auth | ✅ |
| Sanctum device auth + API | ✅ |
| Multi-group membership, per-group roles, policies | ✅ |
| Group list/detail/members/invitations/join requests/settings views | 🟡 (functional, Breeze-styled) |
| Devices list/rename/revoke | 🟡 (functional, Breeze-styled) |
| Dark `gms` design tokens | 🟡 (tokens ✅, only used on landing page) |
| Dashboard overview page | 🟡 (route ✅, placeholder content) |
| Dashboard layout/sidebar/header | ❌ |
| Dashboard data service and KPI components | ❌ (data ✅) |
| Mods / convoy profiles / sync history **read** pages | ❌ (write routes + data ✅) |
| Platform-admin area & middleware | ❌ (flag exists, bypass logic exists) |
| Notifications | ❌ |
| Desktop client download link | ❌ ❓ |
| Active-group switcher | ❓ D2 |
| Deferred/async panels | ❓ D4 |

### Decisions requested
- **D1** New dashboard layout alongside Breeze (recommended) vs restyle `layouts/app.blade.php`.
- **D2** Group switcher: dashboard is cross-group (recommended, no persistence), vs a stored “active group” (needs session/DB field).
- **D3** Sidebar entry for the desktop client download: omit until a release exists (recommended).
- **D4** Server-rendered only (recommended for v1) vs deferred JSON panels.
- **D5** Should we also migrate `groups.*`, `devices.*`, `profile.*` to the dashboard layout? (recommended: yes, as a separate step after Overview.)
- **D6** Whether `is_platform_admin` should be removed from `$fillable` now (recommended; see R1).

---

## 9. Implementation Plan (after approval)

1. **Baseline:** run `php artisan test` and `npm run build`; record results.
2. **Layout:** `components/dashboard/{sidebar,topbar,nav-item,stat-card,panel,badge,empty-state,skeleton}.blade.php`, `layouts/dashboard.blade.php`, font links; add only minimal `app.css` utilities. No new packages.
3. **Service + controller:** `DashboardOverviewService`, `DashboardController`; route swap keeping the name `dashboard`.
4. **Views:** `dashboard.blade.php` rebuilt with panels and empty/error states.
5. **Tests** (PHPUnit): 
   - guest redirected; unverified redirected to verification;
   - verified user without groups sees empty state with real zeros;
   - counts match seeded factories; role badges correct;
   - user never sees another group's or private group's data;
   - suspended/banned membership excluded;
   - manager-only widgets hidden/absent from payload for members;
   - platform admin flag grants no extra data on the dashboard;
   - query-count ceiling (N+1 guard);
   - existing auth and landing tests still pass (`WelcomeLandingPageTest`, `Auth/*`).
6. **Docs:** update `PROJECT_CONTEXT.md` (status, roadmap), `docs/05-development-roadmap.md`, and this file.
7. **Follow-ups:** Phase 2 read pages (mods, convoys, sync), then migrating existing group/device pages.

## 10. Dependencies
- None new (Composer or NPM). Alpine.js, Tailwind, Vite already present.
- Requires existing: `SfgPilotSeeder` / factories for dev data (confirm factories exist for `Group`, `GroupMembership`, `ConvoyProfile`, `SyncSession`, `Device`; add if missing).
- Fonts via fonts.bunny.net (already used).

## 11. Risks

| ID | Risk | Mitigation |
| :--- | :--- | :--- |
| R1 | `is_platform_admin` appears in `User` fillable list and acts as a universal bypass; there is no admin middleware. Mass-assignment during profile/registration could escalate privileges. | Verify `$fillable` vs `$guarded` usage; confirm requests never pass it; remove from `$fillable` (D6); test. |
| R2 | Visual inconsistency between dashboard and unmigrated Breeze pages. | D5 migration step; shared layout. |
| R3 | Restyling shared Breeze components could break login/register. | Do not touch them; dashboard uses own components. |
| R4 | Invitations are token/email based, so matching “my pending invitations” needs care. | Inspect `GroupInvitation` schema before coding; decide on email-match semantics. |
| R5 | N+1 and slow aggregates as data grows. | Eager loading, `withCount`, query-count tests, review indexes, optional caching later. |
| R6 | Empty-data dashboards look “broken” for new users. | Strong empty states and onboarding checklist. |
| R7 | Tailwind purge: dynamic class names for badge colours. | Use full static class maps in a PHP/Blade lookup. |
| R8 | Breeze `Auth::user()->name` shown in the top bar must be escaped. | Always use `{{ }}`. |
| R9 | Tailwind `darkMode` defaults to `media`; mixing `dark:` variants would flip with OS setting. | Use `gms-*` tokens only. |
| R10 | Existing `app.blade.php` loads only Figtree; unthemed pages will look off beside the new fonts. | Part of D5. |

## 12. Acceptance Criteria
1. Landing page files unchanged; `WelcomeLandingPageTest` passes.
2. Login, registration, verification, password reset flows and all `Auth/*` tests pass; redirect still lands on `route('dashboard')`.
3. `/dashboard` uses the dark `gms` identity: tokens, typography, focus states, consistent with the landing page.
4. Every number and list on the dashboard comes from the database; no hardcoded or placeholder statistics.
5. New user with no groups sees an informative empty state; each panel has an empty state; errors degrade per panel.
6. No cross-group or private-group leakage; manager widgets respect `GroupPermissionService`.
7. No platform-admin UI or data is visible to ordinary users; no admin route exists without middleware.
8. Responsive at 320, 768, 1024, 1440px with a working mobile off-canvas menu, keyboard-navigable, AA contrast, reduced-motion respected.
9. No new Composer/NPM packages; no migrations unless a specific requirement was documented and approved.
10. New tests (listed in §9) pass together with the full suite; `npm run build` succeeds.
11. `PROJECT_CONTEXT.md` and roadmap docs updated.

---

## 13. Approval Request
Please approve (or amend) decisions **D1–D6** and the Phase 1 scope (layout + Overview). No changes will be made until you do.
