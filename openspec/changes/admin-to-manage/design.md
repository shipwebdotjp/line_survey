## Context

The product has pivoted to a multi-tenant "owner" model in which each LINE-authenticated user manages their own surveys. Owner authentication is now session-based (see the recently archived `add-owner` change), and the `admin` role value on `users.role` is reserved for a future super-admin role. The user-facing URL namespace, the React Router management tree, and the visible chrome still use the old "admin" terminology:

- The frontend mounts the management tree at `/admin/*` and the login entry at `/admin/login` (`frontend/src/App.tsx:85-107`).
- The backend mounts the owner-facing API at `/api/admin/*` (`backend/routes/api.php:100-141`).
- Every API client base path under `frontend/src/features/admin/` is `/api/admin/*`.
- The 401 recovery in `frontend/src/features/admin/lib/adminFetch.ts:32-39` redirects unauthenticated users to `/admin/login?from=...`.
- The post-logout redirect in `frontend/src/features/admin/auth/AdminAuthContext.tsx:56` is `window.location.href = '/admin/login'`.
- The `from` validation in `frontend/src/pages/admin/AdminLoginPage.tsx:24-29` only accepts targets starting with `/admin`.
- The management shell's breadcrumb and brand copy say "Admin" and "Admin Panel" (`frontend/src/pages/admin/AdminShell.tsx:50, 124`).
- The login page heading is "Admin Login" and the document title is "管理者ログイン" (`frontend/src/pages/admin/AdminLoginPage.tsx:16, 71`).
- Public CTAs and the public footer link to `/admin/surveys/new` (`frontend/src/pages/public-home/PublicHomePage.tsx:61`, `frontend/src/features/survey/PublicFooter.tsx:43`).
- The `MailService` confirmation-email body hard-codes the URL `{$this->appUrl}/admin/surveys/{id}/responses/{id}` for the owner copy (`backend/src/Infrastructure/Mail/MailService.php:127`).
- `AdminAuthMiddleware` returns the human-readable error message `'Admin session is required'` for missing sessions (`backend/src/Presentation/Http/Middleware/AdminAuthMiddleware.php:29, 37`).
- The Apache BasicAuth block in `public_html/.htaccess.prod:15-24` and the matching emission in `deploy.sh:57-66` gate `/admin` at the web-server layer.
- `backend/.env.example:33-35` still lists `ADMIN_USER` / `ADMIN_PASS` (BasicAuth leftovers that are not consumed by `backend/src/Config/Settings.php`).
- `docs/spec.md`, `docs/design.md`, `docs/task.md`, `docs/structure.md`, `docs/requirements.md`, `docs/session/plan.md`, `docs/summary/task.md` and the AI wiki pages still reference `/admin` and `/api/admin`.

This change is a hard cutover to a `/manage` and `/api/manage` namespace for the user-facing surface, with no aliases, redirects, or compatibility layer. Internal PHP namespaces, class names, frontend folder names, component names, and CSS class names stay as they are; only URL strings, user-facing copy, the email body URL, the error message, the Apache config, the env stub, and the documentation references change.

## Goals / Non-Goals

**Goals:**
- Move the user-facing React Router management tree to `/manage/*` and the login entry to `/manage/login`.
- Rename the backend route group from `/api/admin` to `/api/manage`, with all inner paths preserved.
- Update every frontend API client base path and inline URL to target `/api/manage/*`.
- Update redirect, 401-recovery, logout, and `from`-validation logic on the frontend to target `/manage` and `/manage/login`.
- Replace user-visible "Admin" / "Admin Login" / "Admin Panel" labels in the management shell and login page with "Manage" / "管理画面".
- Update public CTAs, the public footer, and the confirmation-email body to emit `/manage/...` URLs.
- Remove the Apache BasicAuth block in `public_html/.htaccess.prod` and the matching emission in `deploy.sh`; tighten the commented block in `public_html/.htaccess` to a one-line comment.
- Drop the `ADMIN_USER` / `ADMIN_PASS` env stub from `backend/.env.example`.
- Update `/admin` and `/api/admin` references in `docs/*` and `ai_wiki/*` to `/manage` and `/api/manage`.
- Lock the new namespace into the `owner-workspace` spec so the contract is preserved in future changes.

**Non-Goals:**
- Renaming any PHP namespace, PHP class name, frontend folder name, React component name, CSS class name, or any internal method/variable name. The migration is about URL strings, user-facing copy, and the public/deploy config.
- Re-architecting authentication, session, or ownership semantics. The owner session model, the `OWNER_SESSION_REQUIRED` error code, and the `role === 'admin'` check stay as they are.
- Renaming or removing the `users.role = 'admin'` value. The role value is reserved for a future super-admin role and the column stays; the new namespace spec only asserts that the value is not used to authorize `/api/manage/*` in the current implementation.
- Renaming or removing internal mail symbols (`admin_result`, `$isAdmin`, `$subject_admin`, `$body_admin`), the `ResponseRepository::findHistoryForAdmin` method, or the `seed-admin` / `dev-admin` seed users.
- Retroactively rewriting the in-flight `openspec/changes/add-owner/*` change. It still references `/api/admin` in its scenarios; that will be revisited the next time the change is reopened.
- Introducing a redirect or compatibility shim from `/admin*` to `/manage*`. The cutover is hard by design.
- Adding tests (per the project's `AGENTS.md` testing guideline, the repo is currently development-stage and tests are not required).

## Decisions

### 1. Hard cutover, no aliases

There will be no rewrite from `/admin*` to `/manage*` at the web server, no React Router alias, no API compatibility shim. Bookmarks, emails, and external links captured before the cutover will break by design. This matches the explicit assumption in the proposal and keeps the codebase from carrying two parallel route trees.

Alternative considered: a temporary `mod_rewrite` redirect from `/admin*` to `/manage*`. Rejected because the Apache BasicAuth block is being removed, and adding a redirect layer in its place would re-introduce the same web-server-level coupling the change is trying to remove. It also conflicts with the React Router catch-all that already 404s on unknown paths.

### 2. Rename URL strings and visible chrome only — leave internal identifiers alone

The migration touches only the strings that the user sees or that wire to a public URL: route definitions, `<Link to>` and `navigate()` arguments, `API_BASE` constants, the email body line, the `Admin session is required` error message, the `Admin Panel` brand label, and the `Admin Login` heading. PHP namespaces (`App\Application\Admin\*`, `App\Presentation\Http\Admin\*`), PHP class names (`AdminAuthMiddleware`, `AdminLoginAction`, `AdminShell`, `AdminButton`, `adminSurveyApi`, etc.), frontend folder names (`pages/admin/`, `features/admin/`, `components/admin/`), and CSS class names (`admin-button`, `admin-card`, `admin-layout`, etc.) are kept.

Alternative considered: a full rename of namespaces, classes, folders, and CSS classes to match. Rejected because the migration is about URL namespace and user-facing terminology, per the proposal; a full rename would touch hundreds of files for no user-visible benefit and would block the cutover behind a much larger refactor.

### 3. Keep `users.role = 'admin'` as a reserved value

The DB column stays. The new namespace spec only asserts that the `admin` value is not used to authorize `/api/manage/*` in the current implementation; the `user` value (set on every new LINE-authenticated owner) continues to authorize management access through the owner session. The check in `AdminAuthMiddleware.php:40` (`if (($user['role'] ?? '') !== 'admin') { ... }`) is therefore a future-looking placeholder: today it accepts only the `seed-admin` and `dev-admin` seed users, and once a super-admin feature is built, that check will be re-purposed.

Alternative considered: collapse `users.role` to a boolean or remove the `admin` value entirely. Rejected because the value is reserved by design and removing it now would force a future super-admin change to re-introduce the column and re-migrate the data.

### 4. Remove the Apache BasicAuth block entirely

`public_html/.htaccess.prod:15-24` and `deploy.sh:57-66` both emit an `<If "%{REQUEST_URI} =~ m#^/admin#">` block that gates `/admin` with BasicAuth. This block is dead weight: it was already commented out in `public_html/.htaccess`, and the PHP `AdminAuthMiddleware` is the only path that actually authorizes the management API. The block in `public_html/.htaccess.prod` is removed; the emission in `deploy.sh` is removed; the commented block in `public_html/.htaccess` is collapsed into a one-line comment so a future reader cannot re-enable it by accident. No `AuthType Basic` block is added for `/manage`; the PHP middleware is the sole auth layer.

Alternative considered: repoint the existing block to `/manage` so BasicAuth remains a defense-in-depth layer. Rejected because BasicAuth is a shared-username password and does not represent per-user identity; the owner session model is the only auth path that supports the multi-tenant product. Keeping BasicAuth would either block real owners (shared password) or be a no-op (every owner knows the shared password).

### 5. Update the confirmation-email URL to `/manage/...`

`MailService::buildEmailBody()` line 127 currently hard-codes the URL `{$this->appUrl}/admin/surveys/{$survey['id']}/responses/{$response['id']}` for the owner copy of the confirmation email. The new line emits `{$this->appUrl}/manage/surveys/{$survey['id']}/responses/{$response['id']}`. The internal symbols around it (`$isAdmin` parameter, `$subject_admin` / `$body_admin` local variables, `admin_result` result key, `buildEmailBody(..., bool $isAdmin = false)` signature) stay; only the rendered URL changes.

Alternative considered: pass the URL prefix as a constructor-injected setting so the mail template can be re-pointed without a code change. Rejected because the email body is the only place this URL is hard-coded, and the underlying requirement (the new namespace is the only public surface) is enforced by the spec, not by configuration.

### 6. Update `AdminAuthMiddleware` error copy to `Manage session is required`

`AdminAuthMiddleware.php:29` returns the human-readable message `'Admin session is required'` and line 37 returns `'Invalid owner session'`. The first message is updated to `'Manage session is required'` to match the new user-facing chrome. The error code `OWNER_SESSION_REQUIRED` (which the frontend already handles generically) and the `role === 'admin'` check at line 40 stay.

Alternative considered: leave the error message as `'Admin session is required'` because the error code is what the frontend actually branches on. Rejected because the message is surfaced in the toast notification through `AdminApiError`, and a user-facing message that says "Admin" while the chrome says "Manage" is a small but visible regression.

### 7. Tighten the login `from` validation to `/manage`

`AdminLoginPage.tsx:24-29` validates the `from` query parameter by checking that the value starts with `/admin` and not with `/admin/login`. After the cutover, the same logic checks for `/manage` and not for `/manage/login`. The default target becomes `/manage/surveys` instead of `/admin/surveys`.

Alternative considered: accept any path that is not `/manage/login` to allow deep-linking to the public survey flow after login. Rejected because the login page is owner-only, and accepting a public-survey `from` would route the owner to the public flow as if they were a respondent, which is the wrong shell.

### 8. Lock the namespace in the `owner-workspace` spec

The `owner-workspace` capability (created by the recently archived `add-owner` change) gets a new `### Requirement: Management URL namespace` with seven scenarios covering: frontend route mount, backend route mount, API client base paths, login redirect, 401 redirect, public link emission, and logout redirect. The five existing requirements are also MODIFIED to replace `/api/admin/*` with `/api/manage/*` in the body and to update the `Owner login grants admin access` scenario's endpoint from `/api/admin/login` to `/api/manage/login`. The MODIFIED requirements' bodies must include the full updated content per the spec-driven rules.

Alternative considered: put the namespace assertions into a new capability called `manage-namespace` and leave `owner-workspace` alone. Rejected because the namespace is a property of the owner-workspace capability, not a separate concern; splitting them would scatter the contract across two specs and require readers to correlate them every time the namespace is touched.

### 9. Leave the in-flight `add-owner` openspec change alone

`openspec/changes/add-owner/{proposal.md,design.md,tasks.md,specs/owner-workspace/spec.md}` still mentions `/api/admin/*` in a few places. These files are not edited as part of this change. The new namespace spec in `admin-to-manage` already supersedes those references at the requirements level, and the next time the `add-owner` change is revisited, its wiki will be updated to match. This keeps the cutover atomic and avoids creating a half-synced state between the two changes.

Alternative considered: edit the `add-owner` files in the same change. Rejected because the change is in flight (its `tasks.md` still has two `[-]` markers) and editing it would mix two unrelated intents in one PR.

## Risks / Trade-offs

- [Broken bookmarks] Old `/admin*` and `/api/admin*` URLs are no longer reachable. → Accepted by design; documented in the proposal. Public users are informed through the updated copy in the management shell, the login page, and the email body.
- [Missed URL string] A `/admin*` literal could be left in a comment, a log line, a test fixture, or an admin-only page that is not exercised by `make test`. → Mitigated by the `grep /admin` and `grep /api/admin` sweeps in tasks 11-13, which cover routes, public links, mail, env, deploy, and docs. Internal `App\Application\Admin\*` namespaces, internal class names, internal folder names, and internal CSS class names are intentionally left and do not need to be swept.
- [Apache footgun] If a future deploy forgets to remove the BasicAuth block from `.htaccess.prod`, the new `/manage` route would either be wrongly gated (if repointed) or left gated for the old `/admin` path with no effect. → Mitigated by removing the block from `deploy.sh` so future deploys do not re-emit it, and by tightening the commented block in `public_html/.htaccess` to a one-line comment.
- [Stale error toast] The frontend `AdminApiError` propagates the backend's human-readable message; if any code path still returns the old `'Admin session is required'` string, the toast will say "Admin" while the chrome says "Manage". → Mitigated by the single-string update in `AdminAuthMiddleware.php:29` and by the spec scenario that pins the message.
- [Privacy policy copy] The privacy page (`PrivacyPolicyPage.tsx`) uses the Japanese "管理画面" for "management screen". This is localization, not a URL; it is left as-is. → No action; the proposal's "管理画面" replacement example is for English user-facing chrome, not Japanese.
- [Drift between main spec and in-flight change] The `add-owner` openspec change still mentions `/api/admin/*`. → Mitigated by decision 9; the next revisit of `add-owner` will sync.
- [Owner-role vs admin-role ambiguity] The middleware checks `role === 'admin'`, but new owners get `role = 'user'`. Today this means the only accounts that can pass the middleware are `seed-admin` / `dev-admin`; every real owner is blocked. → Pre-existing bug, not introduced by this change. Out of scope; will be addressed in a future change that revisits the role semantics. Logged here so reviewers do not think this change regressed it.

## Migration Plan

1. Apply the change in the order laid out in `tasks.md`: frontend route tree → frontend pages → frontend shell → frontend auth → frontend API clients → frontend public links → frontend remaining copy → backend routes → backend middleware → backend mail → public/deploy → env → docs → AI wiki.
2. After `tasks.md` is fully checked off, run `make test` inside the container to exercise the PHP test suite, and run `npm run build` in `frontend/` to confirm the React build is clean.
3. Manual smoke test (matches the proposal's test plan): (a) visit `/manage/surveys` while logged out, confirm the redirect to `/manage/login?from=...`; (b) complete LINE login and confirm the return to `/manage/...` and a 200 from `GET /api/manage/surveys`; (c) inspect the public landing page, public footer, and a fresh confirmation email and confirm every management link starts with `/manage/`; (d) curl `/admin*` and `/api/admin*` and confirm 404.
4. Cut over by deploying from the updated branch: `deploy.sh` will regenerate `public_html/.htaccess` without the BasicAuth block; `public_html/.htaccess.prod` is replaced with the version that has no BasicAuth block; the React bundle is rebuilt and uploaded; the backend is redeployed.
5. No DB migration is required. The role value `admin` is preserved on `users.role`; the `seed-admin` and `dev-admin` rows are preserved.

Rollback: revert the deploy. The change has no DB migration and no destructive schema change, so a code-only revert is sufficient. The seed users stay untouched.

## Open Questions

- Should a follow-up change revisit the `add-owner` openspec wiki to update its `/api/admin/*` references and the two `[-]` tasks (`1.2` and `3.3`)? Out of scope here; tracked as a follow-up.
- Should the role check in `AdminAuthMiddleware` be relaxed to also accept `role === 'user'`? Pre-existing issue; out of scope here; tracked as a follow-up.
