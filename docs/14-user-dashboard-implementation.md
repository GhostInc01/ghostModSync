# 14 — User Dashboard Implementation

**Status:** Implemented, awaiting review. Implements the approved plan in [13-user-dashboard-implementation-plan.md](13-user-dashboard-implementation-plan.md). The Admin Dashboard is **not** started.

## 1. What the user gets

| Area | Behaviour |
| :--- | :--- |
| Sidebar | Dashboard, My Groups, Discover Groups, My Devices, Account Settings are live links. **My Mods, Convoy Profiles, Synchronisation, Notifications** are planned: shown greyed with a "Soon" tag, `aria-disabled`, **no link**. Logo, active-item highlight (`aria-current`), log-out button. Off-canvas on <1024px (toggle, close button, backdrop, `Esc`). |
| Top bar | Breadcrumb/title, attention indicator (bell), avatar + account dropdown (settings, public site, log out), mobile toggle. |
| Welcome | "Welcome back, *name*!" + "Your gaming groups, convoy profiles and mod synchronisation activities, all in one place." |
| Summary cards | Groups joined · Convoy profiles available · Mods available · Registered devices · Last synchronisation (`—` if none). |
| Needs attention | Valid invitations to the user's e-mail, own pending join requests, sync approvals the user's role may review. |
| My groups | Logo (or tag initials), name, tag, description, role, membership status, visibility, supported game(s), member count, sync policy; links to the group. Empty state → *Discover Groups*. |
| Convoy profiles | Active profiles of the user's groups with the user's latest sync status. |
| Recent mod updates | Latest `ready` versions in the user's groups. |
| Synchronisation overview | Latest status, last synchronised, failed attempts, own pending approvals, approvals awaiting the user's review. If no device and no session exist: an honest "performed by the Windows client — nothing is simulated" state. |
| Recent sync sessions / Devices | Real `sync_sessions` / `devices` rows of the user. |
| Recent activity | Real `audit_logs` rows (see §4). Empty state when none. |
| Discover Groups (`/groups/discover`) | Search + paginated list of **public** groups only, with the viewer's membership state; cards link to the existing group page. |

**Loading states:** pages are fully server-rendered (no async panels), so there is no spinner state to show. This was decision D4 in doc 13. **Error state:** a service failure is caught, logged via `report()`, and a themed error card with *Try again* replaces the content.

## 2. Files

**Created**
- `app/Http/Controllers/Web/DashboardController.php`
- `app/Http/Controllers/Web/GroupDiscoveryController.php`
- `app/Http/Requests/Web/DiscoverGroupsRequest.php`
- `app/Services/Dashboard/DashboardOverviewService.php`
- `resources/views/components/dashboard/{layout,nav-item,icon,panel,badge,stat-card,empty-state}.blade.php`
- `resources/views/groups/discover.blade.php`
- `tests/Feature/UserDashboardTest.php`, `tests/Feature/UserDashboardExtendedTest.php`
- `docs/13-…`, `docs/14-…`

**Modified**
- `resources/views/dashboard.blade.php` (placeholder replaced)
- `routes/web.php` (dashboard route → controller; `groups.discover` route + import)
- `resources/css/app.css` (`[x-cloak]` rule)
- `docs/05-development-roadmap.md`, `PROJECT_CONTEXT.md`

**Untouched:** landing page, Breeze auth controllers/views/components, all migrations (no schema change), `composer.json`/`package.json`.

## 3. Routes

| Method | URI | Name | Middleware | Controller |
| :--- | :--- | :--- | :--- | :--- |
| GET | `/dashboard` | `dashboard` | `auth`, `verified` | `DashboardController@index` |
| GET | `/groups/discover` | `groups.discover` | `auth`, `verified` | `GroupDiscoveryController@index` |

`groups.discover` is registered before `/groups/{group}` so "discover" is never treated as a group slug. Both routes are read-only. No dashboard route accepts a user/group identifier.

## 4. Database queries (all in `DashboardOverviewService`)

Everything is scoped to `auth()->user()` (the controller re-reads the user with `fresh()`); no request input is used by the dashboard.

1. Active memberships of the user with their groups (`group_memberships` + `groups`, soft-deleted groups excluded) — 1 query + 1 eager load.
2. Active member counts for those groups — one grouped `COUNT` query (not per group).
3. User's devices; active = `revoked_at IS NULL`.
4. Active convoy profiles `WHERE group_id IN (my active groups)` with `group`, `game`.
5. User's sync sessions for those profiles (`with device, convoyProfile.group`); the latest per profile is derived in PHP.
6. Count of the user's pending `sync_approvals`.
7. Games per group: distinct `(group_id, game_id)` from `mods`, merged with profiles, one `games` lookup.
8. Mods available: `mods` in my groups, `is_active`, with a `ready` version (`COUNT`).
9. Recent mod versions: `ready`, mod in my groups, newest 5 (`with mod.group`).
10. Invitations: `email = user.email`, not already a member, `isValid()`.
11. Own pending join requests.
12. Sync approvals `pending` whose profile belongs to groups where the user's **membership role** `canApproveSync()`.
13. Recent activity from `audit_logs`, newest 8, only selected columns, limited to:
    - **own events** (`user_id = me`): `group.created`, `membership.joined`, `membership.requested`, `invitation.accepted`, `device.registered`, `sync_session.reported`;
    - **group events by anyone**, only in the user's active groups: `mod.created`, `mod_version.published_s3`, `mod_version.published_steam_workshop`, `convoy_profile.created`, `convoy_profile.manifest_updated`.
    IP address, user agent and old/new values are never selected or rendered.

Query count is independent of the number of groups (asserted by a test). Discover uses 3 queries per page (groups with counts, pagination count, viewer memberships).

## 5. Components

`<x-dashboard.layout title attention-count>` (shell) · `nav-item` · `icon` · `panel` · `badge` (static colour map so Tailwind keeps the classes) · `stat-card` · `empty-state`. Styling uses only existing `gms.*` tokens, fonts and the landing page's button/focus patterns; dark-only (no `dark:` variants). Attention bell shows a real count (invitations + approvals to review); it is **not** a notification inbox.

## 6. Permissions and security

- Guests → redirected to login; unverified → verification notice (existing middleware).
- Personal data = rows with `user_id = me`; group data = only groups where the membership is `active`. Suspended/banned/pending memberships contribute nothing.
- Approval widgets use the membership role (`GroupRole::canApproveSync()`); `is_platform_admin` deliberately gives **no** extra dashboard data (tested).
- Discover lists `visibility = public` only; the search term is validated (`DiscoverGroupsRequest`: string, ≤100) and LIKE wildcards are escaped. Opening a group still goes through the existing `GroupPolicy`.
- Invitation tokens appear only on the invited user's own dashboard.
- All output is escaped with `{{ }}`. The only raw-looking value is the group logo URL built from `Storage::disk('public')`.
- No mutating action was added, so no new FormRequest/Policy beyond the read-only search request was needed. Log-out reuses Breeze's POST + CSRF route.

## 7. Tests (run: full suite **76 tests, 533 assertions, OK**)

`UserDashboardTest` (9) and `UserDashboardExtendedTest` (13) cover: guest and unverified redirect; empty state with real zero counts; own-groups-only scoping; multiple groups with different roles, status, game and description; suspended membership excluded; private/foreign data not leaked; approvals visible only to approver roles; platform-admin flag grants nothing; invitations and join requests scoped to the user; mods-available counts; activity filtering (own vs group vs foreign vs non-whitelisted) and its empty state; honest sync overview without devices and failed-sync display with data; request-parameter tampering ignored; attention count; sidebar links (live links present, planned items disabled "Soon", no `/admin`, mobile toggle and `aria-current` present); error state; Discover (auth, public-only, membership badge, search, wildcard literal, input validation); N+1 guard.

**Not covered by automated tests / not verified:** visual rendering and the actual desktop/tablet/mobile breakpoints, the Alpine sidebar/dropdown interaction, and colour contrast. These were implemented with responsive Tailwind classes and ARIA attributes but **need a manual browser check** at 320/768/1024/1440px. `phpunit` was run outside the command sandbox (see doc 13 notes); tests use in-memory SQLite, never the MySQL database. `npm run build` succeeds.

## 8. Known limitations

- Planned features are not built: My Mods, Convoy Profiles, Synchronisation pages and an in-app Notifications inbox (shown as "Soon"). No desktop client exists yet, so sync data will be empty until Milestone 6.
- Group, device and profile pages still use the old Breeze layout; the sidebar's *My Groups*, *My Devices*, *Account Settings* links lead to those unthemed pages (D5).
- "Supported game" is derived from a group's mods and convoy profiles (groups have no game column), so a new group shows none.
- "Mods available" counts active mods with a `ready` version; it does not yet apply per-group mod-approval state (`GroupModApproval`).
- Recent activity is limited to audit events that exist today; "receiving a profile update" is shown at group level, not per user.
- Group logos use the `public` disk; if the storage symlink is absent the image will not load (no broken-image fallback).
- Fonts load from fonts.bunny.net (same as the landing page).

## 9. Remaining work

1. Migrate `groups.*`, `devices.*`, `profile.*` pages to `<x-dashboard.layout>` (D5).
2. Read pages for mods, convoy profiles and sync history; then enable those sidebar entries.
3. Notifications model/inbox; then replace the attention indicator or extend it.
4. Manual responsive/accessibility QA and a theme for the 403/404/500 pages.
5. Admin Dashboard — **not started; awaiting separate approval.**
