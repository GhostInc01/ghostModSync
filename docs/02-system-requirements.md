# Ghost ModSync — System Requirements Specification (`docs/02-system-requirements.md`)

**Date:** 2026-11-09  
**Role:** Lead Software Architect  
**Status:** Baseline Specification

---

## 1. Purpose & Scope

This document defines the functional and non-functional requirements for **Ghost ModSync**, covering the **Laravel 13 Web Platform** (developed first) and the **Windows C#/.NET WPF Desktop Client** (developed in the subsequent phase).

---

## 2. Actors & Group-Scoped Role Hierarchy

A single user account (`users`) may belong to **multiple groups/VTCs simultaneously** and hold a **different role in each group**. Global platform administration is separate from group-level roles.

### 2.1 Group-Scoped Roles (`GroupRole` Enum)

| Role | Slug | Capabilities Within the Specific Group |
| :--- | :--- | :--- |
| **Group Owner** | `owner` | Full control over the group, including billing/storage settings, transferring ownership, deleting the group, configuring group visibility (`public`/`private`), configuring sync mode (`automatic`/`approval_required`), and managing all admins/members. |
| **Group Administrator** | `admin` | Manage group settings, approve/reject join requests, invite/remove members, assign roles (up to `admin`), approve pending sync requests (when approval-based sync is active), and manage all mods and convoy profiles. |
| **Mod Manager** | `mod_manager` | Upload new mods and mod versions to S3, register Steam Workshop mods, define mod dependencies, deprecate/revoke broken mod versions, and manage convoy profiles. |
| **Convoy Dispatcher** | `convoy_dispatcher` | Create, clone, reorder, activate, and archive **Convoy Profiles** using approved mods from the group's library; view member sync readiness for upcoming convoys. |
| **Member (Driver)** | `member` | View group dashboard, browse assigned Convoy Profiles, download/sync mods via the Windows desktop client (or web fallback), and report sync status. |

---

## 3. Functional Requirements by Logical Module

### 3.1 Module 1 & 2: Authentication & Users
- **FR-AUTH-01 (Web Auth):** Users shall register, log in, log out, reset passwords, confirm passwords, and manage their profiles via the existing Laravel Breeze Blade scaffolding.
- **FR-AUTH-02 (Email Verification):** Users must verify their email address (`MustVerifyEmail` enforced on `App\Models\User`) before accessing group dashboards, creating groups, or authenticating a desktop device via API.
- **FR-AUTH-03 (Desktop API Auth):** Users shall authenticate from the Windows WPF client via Laravel Sanctum to obtain a device-scoped Personal Access Token (PAT).
- **FR-USER-01 (Steam Metadata Optional Link):** Users may optionally store their 64-bit `steam_id` on their profile to assist with Steam Workshop subscription checks.

### 3.2 Module 3: Devices
- **FR-DEV-01 (Device Registration):** When the Windows WPF desktop client authenticates, it shall register or update a `Device` record containing a unique hardware/installation identifier (`device_uuid`), machine name, OS version, client version, and configured local ETS2/ATS mod directory paths.
- **FR-DEV-02 (Device Revocation):** Users shall be able to view their registered Windows devices in the Blade web UI and revoke any device (which immediately deletes its associated Sanctum token).

### 3.3 Module 4, 5 & 6: Groups, Memberships & Permissions
- **FR-GRP-01 (Multi-VTC Support & Pilot Group):** The system shall support multiple independent VTCs/groups. **SFG** shall be provisioned as the initial pilot group.
- **FR-GRP-02 (Group Visibility):** Each group shall be configurable as:
  - `public`: Listed in the public group directory; any verified user can view public convoy profiles and join (or request to join).
  - `private`: Hidden from public directory listings; accessible only to invited or approved members.
- **FR-GRP-03 (Synchronisation Policy):** Group administrators shall configure the group's default (and per-profile overridable) synchronisation mode:
  - `automatic`: Authenticated group members' desktop clients automatically download and apply profile updates upon selection.
  - `approval_required`: Desktop synchronisation requires explicit approval (either per-member sync authorization by a group admin/dispatcher or explicit user confirmation workflow before mutating local mod files, as configured by the group).
- **FR-MEM-01 (Multi-Group Membership):** A user may belong to any number of groups simultaneously, with an independent `role` and `status` (`active`, `pending`, `invited`, `banned`) in each group.
- **FR-MEM-02 (Invitations):** Group Owners/Admins can generate time-limited, usage-capped invite codes/links or send direct email invitations.

### 3.4 Module 7: Games
- **FR-GAME-01 (ETS2 & ATS Support):** Every Mod and Convoy Profile must be associated with a supported game (`ets2` for Euro Truck Simulator 2, `ats` for American Truck Simulator).
- **FR-GAME-02 (Game Version Matrix):** Mods and Convoy Profiles shall specify target game version compatibility (e.g., ETS2 `1.52`, `1.53`) so the desktop client can warn users if their installed simulator version mismatches the convoy profile.

### 3.5 Module 8, 9, 10 & 11: Mods, Mod Versions, Mod Sources & Dependencies
- **FR-MOD-01 (Group Mod Library):** Each group maintains its own catalogue of Mods (`mods`), scoped to either `ets2` or `ats`.
- **FR-MOD-02 (Dual Source Support):** Each `ModVersion` shall be backed by a `ModSource` of type:
  1. `s3_upload`: Direct binary archive (`.scs` or `.zip`) uploaded to S3-compatible object storage.
  2. `steam_workshop`: Reference to a Steam Workshop item (`published_file_id`, Workshop URL, and expected Workshop metadata/revision).
- **FR-MOD-03 (Cryptographic Integrity):** Every `s3_upload` `ModVersion` must record its exact `file_size_bytes` (`BIGINT UNSIGNED`) and lowercase 64-character `sha256_hash` (`CHAR(64)`).
- **FR-MOD-04 (Immutable Versioning):** Once a `ModVersion` is published and attached to an active `ConvoyProfile`, its binary file and `sha256_hash` are immutable. Updating a mod requires publishing a new `ModVersion`.
- **FR-MOD-05 (Mod Dependencies):** Mod Managers can declare directed dependencies (`requires` or `conflicts_with`) between mods (and optional minimum/exact version constraints). Circular dependencies must be rejected on save.

### 3.6 Module 12: Convoy Profiles
- **FR-PROF-01 (Profile Definition):** A `ConvoyProfile` represents a named, versioned mod configuration for a specific group and game (`ets2` or `ats`), e.g., *"SFG Weekly Heavy Haul Convoy - ETS2 1.53"*.
- **FR-PROF-02 (Deterministic Load Order):** Each `ConvoyProfile` contains an ordered list of `ConvoyProfileMod` items, each pinning a specific `mod_version_id`, an integer `priority` (1 = highest priority / top of mod manager, or ascending order as defined by the manifest contract), and whether the mod is `is_required` or `is_optional`.
- **FR-PROF-03 (Manifest Hash):** Whenever a `ConvoyProfile`'s pinned mods or load order change, the backend computes a deterministic `manifest_hash` (SHA-256 of the canonical JSON manifest) and increments the profile's `revision` number.

### 3.7 Module 13: Synchronisation
- **FR-SYNC-01 (Manifest Comparison):** The Windows desktop client fetches the target `ConvoyProfile` manifest via `/api/v1/groups/{group}/profiles/{profile}/manifest` and compares each entry against the user's local mod folder (checking file existence, byte size, and SHA-256 hash) and Steam Workshop directory.
- **FR-SYNC-02 (Pre-Signed Downloads):** For missing or mismatched `s3_upload` mods, the desktop client requests short-lived S3 pre-signed download URLs from the backend (`/api/v1/profiles/{profile}/mods/{modVersion}/download-url`).
- **FR-SYNC-03 (Sync Session Reporting):** The desktop client reports synchronisation progress and final status (`in_sync`, `out_of_sync`, `failed`, `pending_approval`) back to the backend (`sync_sessions`) so Convoy Dispatchers can see who is ready for a convoy in real time.

### 3.8 Module 14: Audit Logging
- **FR-AUD-01 (Immutable Audit Trail):** All security-sensitive and operational mutations (group settings changes, role changes, member removals, mod version uploads/deletions, convoy profile publishing, and sync approvals) must write a structured entry to `audit_logs` capturing `user_id`, `group_id`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, and `user_agent`.

---

## 4. Non-Functional Requirements (NFRs)

1. **NFR-PERF-01 (Zero Binary Streaming Through PHP):** Mod archives (`.scs`/`.zip`, which can range from 10 MB to 5+ GB) must **never** be streamed through Laravel PHP workers during upload or download. All binary transfers must use direct S3 pre-signed URLs (`PUT` / `MultipartUpload` for uploads, `GET` for downloads).
2. **NFR-SEC-01 (Tenant Isolation):** Queries for private groups, mods, convoy profiles, and sync sessions must always be scoped by `group_id` and authorized via Laravel Policies.
3. **NFR-INT-01 (End-to-End SHA-256 Integrity):** The C# desktop client must compute the SHA-256 hash of every downloaded file in a staging directory and verify it matches the manifest's `sha256_hash` before moving the file into the live ETS2/ATS `mod/` directory.
4. **NFR-REL-01 (Idempotent Sync API):** Desktop synchronisation endpoints must be idempotent and resilient to intermittent network drops (supporting HTTP Range requests natively via S3 pre-signed URLs).

---

## 5. Key Decision Evaluation: Synchronisation Workflow Design

### Reason for Choosing Dual-Mode (`automatic` vs. `approval_required`) Manifest Synchronisation
Different VTCs operate with different trust and governance models. Competitive or tightly regulated VTCs (such as private divisions) want administrators to approve member sync access or want members to explicitly review file changes before modifying their local ETS2/ATS directory, whereas casual convoys prefer zero-click automatic updates.

### Alternatives Considered
1. **Automatic-Only Sync:** Simpler, but violates confirmed Requirement #3 and prevents VTC admins from gating access to proprietary/private convoy mods per event.
2. **Server-Pushed WebSockets Sync:** Complex infrastructure (Reverb/Pusher) required from day one; unnecessary when pull-based manifest polling + S3 pre-signed URLs is simpler and more reliable for desktop clients.

### Advantages & Disadvantages
- **Advantages:** Supports both casual public communities and strict private VTCs using a single unified manifest endpoint and state machine (`sync_sessions` + `sync_approvals`).
- **Disadvantages:** Requires an explicit approval state check in both `ModDownloadService` (backend) and the WPF client UI before issuing S3 download URLs when `sync_policy = approval_required`.

### Risks & Mitigations
- **Risk:** A user bypasses the desktop UI prompt and calls the download endpoint directly.
- **Mitigation:** When `approval_required` is configured for admin-gated sync, the backend `SyncService` verifies an approved `SyncApproval` record exists in MySQL before generating S3 pre-signed download URLs.
