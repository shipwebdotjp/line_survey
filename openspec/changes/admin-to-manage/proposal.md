## Why

The current user-facing management surface lives at `/admin` and `/api/admin`. The product has pivoted to a multi-tenant "owner" model where each LINE-authenticated user manages their own workspace, and `admin` is now reserved for a future super-admin role. The URL namespace, the React Router tree, and the visible chrome must be rebranded to "Manage" so the user-facing language and the new owner model stay aligned, while internal PHP namespaces, frontend folder names, and CSS class names stay as they are.

This is a hard cutover. Old bookmarks, emails, and external links may break by design. No aliases, redirects, or compatibility shims are introduced.

## What Changes

- **Frontend routing** — move the React Router management tree from `/admin/*` to `/manage/*`, including the login entry at `/manage/login`. Update the LIFF enablement check, the unauthenticated redirect target, the post-logout redirect, and the 401 recovery redirect to the new path. **BREAKING** for any user who bookmarked or linked the old URL.
- **Frontend API clients** — change the base path of every admin-flavoured API client from `/api/admin/*` to `/api/manage/*`. Session semantics stay unchanged: the existing `owner_user_id` session still gates access. **BREAKING** for any direct call to the old path.
- **Frontend visible chrome** — replace user-facing "Admin" / "Admin Login" / "Admin Panel" labels in the management shell and login page with "Manage" / "管理画面". Internal CSS classes (`admin-button`, `admin-card`, `admin-layout`, etc.) are internal and stay.
- **Outbound links** — public CTAs, public footer, and confirmation-email body lines that point at management screens are updated to emit `/manage/...` URLs. **BREAKING** for any link captured before the cutover.
- **Backend route group** — rename the Slim route group prefix from `/api/admin` to `/api/manage`. Internal PHP namespaces (`App\Application\Admin\*`, `App\Presentation\Http\Admin\*`) and class names stay.
- **Backend middleware copy** — update the `AdminAuthMiddleware` error message from `'Admin session is required'` to `'Manage session is required'`. The `OWNER_SESSION_REQUIRED` error code and the `role === 'admin'` check stay as-is.
- **Mail template URL** — `MailService::buildEmailBody()` no longer hard-codes `/admin/...` in the owner-copy link; it emits `/manage/...`. Internal result keys (`admin_result`, `$isAdmin`, `$subject_admin`, `$body_admin`) stay.
- **Deployment config** — remove the Apache BasicAuth blocks in `public_html/.htaccess.prod` and `deploy.sh` (the `REQUEST_URI =~ m#^/admin#` gate is dead weight; the PHP middleware is the only auth path). Tighten the commented block in `public_html/.htaccess` to a one-line comment so a future reader does not re-enable it.
- **Environment stubs** — drop `ADMIN_USER` / `ADMIN_PASS` from `backend/.env.example` (they were BasicAuth leftovers and are not consumed by `Settings.php`).
- **Documentation and AI wiki** — replace `/admin` and `/api/admin` references in `docs/*` and `ai_wiki/*` with `/manage` and `/api/manage`. Keep the Japanese "管理画面" terminology in user-facing copy and the privacy policy.
- **OpenSpec wiki** — `openspec/changes/add-owner/*` is left untouched for this cutover; the new namespace will be reflected there the next time that change is revisited.

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- `owner-workspace`: requirements are extended to assert the `/manage` and `/api/manage` URL namespace, the login redirect behaviour, the outbound link emission, and the `admin` role value's reserved status. Implementation-level references to `/api/admin` in the existing scenarios are updated to `/api/manage`, and the `Admin session is required` error copy becomes `Manage session is required`.

## Impact

- **Frontend** — `frontend/src/App.tsx`, `frontend/src/pages/admin/AdminShell.tsx`, `frontend/src/pages/admin/AdminLoginPage.tsx`, `frontend/src/pages/admin/AdminProfileEditPage.tsx` (visible copy only), the 13 management page components under `frontend/src/pages/admin/`, `frontend/src/features/admin/auth/AdminAuthContext.tsx`, `frontend/src/features/admin/auth/adminAuthApi.ts`, `frontend/src/features/admin/lib/adminFetch.ts`, `frontend/src/features/admin/surveys/adminSurveyApi.ts`, `frontend/src/features/admin/surveys/adminDraftApi.ts`, `frontend/src/features/admin/surveys/SurveyResultsNav.tsx`, `frontend/src/features/admin/respondents/adminRespondentApi.ts`, `frontend/src/features/admin/respondent-masters/adminRespondentMasterApi.ts`, `frontend/src/pages/public-home/PublicHomePage.tsx`, `frontend/src/features/survey/PublicFooter.tsx`. Internal component names, folder names, and CSS class names are not changed.
- **Backend** — `backend/routes/api.php` (route group prefix only; import namespaces for action classes stay), `backend/src/Presentation/Http/Middleware/AdminAuthMiddleware.php` (error copy only; class name and `role` check stay), `backend/src/Infrastructure/Mail/MailService.php` (line-127 hard-coded URL only; internal keys stay).
- **Public web root and deploy** — `public_html/.htaccess` (comment-only), `public_html/.htaccess.prod` (remove the active BasicAuth block), `deploy.sh` (remove the BasicAuth emission).
- **Environment** — `backend/.env.example` (drop `ADMIN_USER` / `ADMIN_PASS`).
- **Documentation** — `docs/spec.md`, `docs/design.md`, `docs/task.md`, `docs/structure.md`, `docs/requirements.md`, `docs/session/plan.md`, `docs/summary/task.md`.
- **AI wiki** — `ai_wiki/10-Decisions.md`, `ai_wiki/20-Worklog.md`.
- **Out of scope** — `openspec/changes/add-owner/*`, all PHP namespaces, all PHP class names, all frontend folder names, all frontend class names, all `admin-*` CSS classes, the `users.role = 'admin'` DB value, the `MailService` `admin_result` / `$isAdmin` / `$subject_admin` / `$body_admin` internal symbols, the `ResponseRepository::findHistoryForAdmin` method name, the seed users `seed-admin` / `dev-admin`, and the `AGENTS.md` files.
- **No database schema change**, no role-value change, no PHP namespace change, no folder rename, no class rename, no CSS class rename.
