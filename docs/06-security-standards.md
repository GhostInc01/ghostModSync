# Ghost ModSync — Security Standards (`docs/06-security-standards.md`)

**Date:** 2026-11-09  
**Role:** Lead Software Architect  
**Status:** Mandatory Security Baseline

---

## 1. Authentication & Session / Token Security

### 1.1 Web Authentication (Laravel Breeze)
- **Guard:** Stateful `web` session guard backed by the `sessions` MySQL table (`SESSION_DRIVER=database`).
- **Email Verification:** `App\Models\User` implements `Illuminate\Contracts\Auth\MustVerifyEmail`. All authenticated group, mod, profile, and device routes must enforce the `verified` middleware alongside `auth`.
- **Password Policy:** Passwords are hashed using Bcrypt (`BCRYPT_ROUNDS=12`). `Password::defaults()` in [`AppServiceProvider`](file:///c:/xampp/htdocs/ghostModSync/app/Providers/AppServiceProvider.php) must enforce a minimum of 10 characters, mixed case, numbers, and symbols (with `uncompromised()` enabled in production).
- **Rate Limiting:** Login (`5` attempts per minute per email+IP), password reset (`6` attempts per minute), and email verification notifications (`6` attempts per minute) are strictly throttled.

### 1.2 Desktop & API Authentication (Laravel Sanctum)
- **Guard:** Stateless `sanctum` guard using Bearer Personal Access Tokens (`personal_access_tokens`).
- **Device Binding:** Every Sanctum token issued to the Windows WPF desktop client must be linked to a `devices` record (`personal_access_token_id`). If a device is marked `revoked_at !== null`, its Sanctum token is immediately deleted.
- **Token Abilities (Scopes):** Desktop client tokens are restricted to explicit abilities:
  - `groups:read`
  - `profiles:read`
  - `mods:download`
  - `sync:report`
  Desktop tokens must **never** be granted group administration or destructive account deletion abilities unless explicitly scoped for an admin upload workflow.
- **Client-Side Token Storage (Windows WPF):** The C# desktop application must encrypt the Sanctum token at rest using Windows Data Protection API (`System.Security.Cryptography.ProtectedData` scoped to `DataProtectionScope.CurrentUser`) or Windows Credential Manager—never in plaintext JSON/XML config files.

---

## 2. Multi-Tenant & Role-Based Authorization (RBAC)

1. **Zero Implicit Group Trust:** Because users can belong to multiple VTCs/groups with different roles in each group (`owner`, `admin`, `mod_manager`, `convoy_dispatcher`, `member`), every controller action operating on a `Group`, `Mod`, `ModVersion`, `ConvoyProfile`, or `SyncApproval` must authorize via a **Laravel Policy** (`$this->authorize('action', $model)` or `Gate::authorize(...)`).
2. **Preventing Cross-Group IDOR (Insecure Direct Object Reference):**
   - Route model binding for nested resources must use **scoped bindings** (`->scopeBindings()` in route definitions) so `/groups/{group:slug}/profiles/{profile:slug}` automatically returns `404 Not Found` if `{profile}` does not belong to `{group}`.
   - A `ConvoyProfileMod` may only reference a `ModVersion` belonging to the **same `group_id` and `game_id`** as the `ConvoyProfile`.
3. **Private Group Isolation:**
   - When `groups.visibility === 'private'`, non-members (and users whose `group_memberships.status` is `pending`, `invited`, or `banned`) must be denied access to group metadata, member lists, mod libraries, convoy manifests, and download URLs.
4. **Approval-Based Sync Enforcement:**
   - When a group or convoy profile is configured with `sync_policy = 'approval_required'`, the endpoint issuing S3 pre-signed download URLs (`POST /api/v1/profiles/{profile}/mods/{modVersion}/download-url`) must verify on the server side that an active `SyncApproval` (`status = 'approved'`) exists for that `user_id` and `convoy_profile_id` before signing the S3 URL.

---

## 3. Object Storage (S3) & File Upload Security

1. **Private Bucket Only:** The S3 bucket (`AWS_BUCKET`) must have public access completely blocked (`BlockPublicAccess = true`). Direct public URLs to mod archives are forbidden.
2. **Short-Lived Pre-Signed URLs:**
   - **Download URLs:** Generated via `Storage::disk('s3')->temporaryUrl($objectKey, now()->addMinutes(15), [...])` only after passing `ConvoyProfilePolicy` and `SyncPolicy` checks.
   - **Upload URLs:** Generated via `Storage::disk('s3')->temporaryUploadUrl($objectKey, now()->addMinutes(30), [...])` only for authorized `owner`, `admin`, or `mod_manager` roles within the group's remaining `storage_quota_bytes`.
3. **Strict Archive & Filename Validation:**
   - Allowed mod file extensions are strictly restricted to `.scs` and `.zip`. Executable extensions (`.exe`, `.dll`, `.bat`, `.cmd`, `.ps1`, `.msi`, `.vbs`, `.php`, `.sh`) are strictly rejected at both validation and S3 key generation stages.
   - Target `archive_filename` values must be sanitized using `basename()` and validated against a strict regex (`/^[a-zA-Z0-9._\-]+\.(scs|zip)$/`) to prevent **Path Traversal (`../`)** when the Windows desktop client writes files into `Documents\Euro Truck Simulator 2\mod`.
4. **Server-Generated S3 Object Keys:** Clients never choose the S3 object path. The backend constructs deterministic, non-guessable keys:
   `groups/{group_id}/games/{game_code}/mods/{mod_id}/versions/{version_uuid}/{sanitized_filename}`

---

## 4. Cryptographic File Integrity (SHA-256)

1. **Format Validation:** Every `sha256_hash` input is validated with `['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/']`.
2. **Upload Verification:** When a Mod Manager registers a new `ModVersion`, the SHA-256 hash and exact `file_size_bytes` are recorded. Before transitioning `mod_versions.status` from `pending` to `ready`, the backend verifies via S3 `HeadObject` that the uploaded object exists and its exact byte size matches `file_size_bytes`.
3. **Desktop Client Verification:** The Windows WPF client downloads every archive to a temporary `.part` staging file, computes the SHA-256 digest over the stream, compares both byte length and SHA-256 against the signed manifest, and **deletes the staging file immediately** if either check fails.

---

## 5. Secrets Management, CSRF, SQL Injection & Auditability

1. **Secrets Protection:**
   - `.env` must never be committed to Git, printed in logs, or exposed in responses.
   - `APP_DEBUG` must be `false` in staging and production environments.
2. **Database & Mass Assignment Safety:**
   - `Model::shouldBeStrict(! $this->app->isProduction())` is enabled to prevent unguarded mass assignment, silently discarded attributes, and N+1 lazy loading.
   - Every Eloquent model must explicitly define `$fillable` (never `$guarded = []`).
   - Raw SQL queries (`DB::raw`, `whereRaw`) are prohibited unless strictly necessary and must always use bound parameter arrays.
3. **CSRF & XSS:**
   - All Blade forms use `@csrf` and appropriate HTTP verbs (`@method('PATCH')`, `@method('DELETE')`).
   - Blade templates must use escaped output `{{ $value }}`. Unescaped `{!! $value !!}` is forbidden on user-supplied data.
4. **Immutable Security Audit Log:**
   - `AuditLog` records have no `updated_at` column and cannot be updated or deleted via application controllers.
