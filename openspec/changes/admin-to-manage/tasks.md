## 1. Frontend route tree

- [x] 1.1 `frontend/src/App.tsx` — change the route mount from `/admin` to `/manage` and the login entry to `login` under `/manage`. Update `isLiffRequired` so it triggers on `/manage/login` instead of `/admin/login`.
- [x] 1.2 Confirm the React Router tree has no leftover `<Route path="/admin/...">` or `to="/admin/..."` literals.

## 2. Frontend management shell

- [x] 2.1 `frontend/src/pages/admin/AdminShell.tsx` — update the unauthenticated redirect target from `/admin/login?from=...` to `/manage/login?from=...`. Update the breadcrumb label and brand copy from `'Admin'` / `'Admin Panel'` to `'Manage'` / `'Manage Panel'`.
- [x] 2.2 `frontend/src/pages/admin/AdminShell.tsx` — update every `matchPath` argument from `/admin/...` to `/manage/...`. Update every `<NavLink to="/admin/...">` to `to="/manage/..."`.

## 3. Frontend login and auth context

- [x] 3.1 `frontend/src/pages/admin/AdminLoginPage.tsx` — update the `from` query parameter default target from `/admin/surveys` to `/manage/surveys`. Update the `isValidAdminPath` check so it accepts paths starting with `/manage` and not with `/manage/login` instead of `/admin` / `/admin/login`.
- [x] 3.2 `frontend/src/pages/admin/AdminLoginPage.tsx` — update `document.title` from `'管理者ログイン'` to `'管理画面ログイン'` and the visible `<h1>` from `'Admin Login'` to `'Manage Login'`.
- [x] 3.3 `frontend/src/features/admin/auth/AdminAuthContext.tsx` — change the post-logout `window.location.href` from `/admin/login` to `/manage/login`.

## 4. Frontend 401 recovery

- [x] 4.1 `frontend/src/features/admin/lib/adminFetch.ts` — update the 401 redirect path check from `pathname.startsWith('/admin') && pathname !== '/admin/login'` to `pathname.startsWith('/manage') && pathname !== '/manage/login'`. Update the redirect target from `/admin/login?from=...` to `/manage/login?from=...`.

## 5. Frontend API clients

- [x] 5.1 `frontend/src/features/admin/auth/adminAuthApi.ts` — change `const API_BASE = '/api/admin';` to `const API_BASE = '/api/manage';`.
- [x] 5.2 `frontend/src/features/admin/surveys/adminSurveyApi.ts` — change `const API_BASE = '/api/admin/surveys';` to `const API_BASE = '/api/manage/surveys';`.
- [x] 5.3 `frontend/src/features/admin/surveys/adminDraftApi.ts` — change the three inline URLs from `/api/admin/response-drafts`, `/api/admin/response-drafts/${id}`, and `/api/admin/response-drafts/cleanup` to their `/api/manage/...` equivalents.
- [x] 5.4 `frontend/src/features/admin/respondents/adminRespondentApi.ts` — change `const API_BASE = '/api/admin/respondents';` to `const API_BASE = '/api/manage/respondents';`.
- [x] 5.5 `frontend/src/features/admin/respondent-masters/adminRespondentMasterApi.ts` — change `const API_BASE = '/api/admin/respondent-masters';` to `const API_BASE = '/api/manage/respondent-masters';`.

## 6. Frontend public outbound links

- [x] 6.1 `frontend/src/pages/public-home/PublicHomePage.tsx` — change the CTA `<Link to="/admin/surveys/new">` to `to="/manage/surveys/new"`.
- [x] 6.2 `frontend/src/features/survey/PublicFooter.tsx` — change the footer `<Link to="/admin/surveys/new">` to `to="/manage/surveys/new"`.

## 7. Frontend page components under `pages/admin/`

- [x] 7.1 `frontend/src/pages/admin/SurveyListPage.tsx` — update every `to="/admin/surveys/..."` literal to `to="/manage/surveys/..."`. Update the heading copy `アンケート管理` to `管理画面` if the new copy standard requires it.
- [x] 7.2 `frontend/src/pages/admin/SurveyCreatePage.tsx` — update `navigate('/admin/surveys')` to `navigate('/manage/surveys')`.
- [x] 7.3 `frontend/src/pages/admin/SurveyEditPage.tsx` — update `navigate('/admin/surveys')` to `navigate('/manage/surveys')`.
- [x] 7.4 `frontend/src/pages/admin/SurveySummaryPage.tsx` — update the `<AdminButton to="/admin/surveys">` to `to="/manage/surveys"`.
- [x] 7.5 `frontend/src/pages/admin/ResponseListPage.tsx` — update every `to="/admin/surveys/..."` literal to `to="/manage/surveys/..."`.
- [x] 7.6 `frontend/src/pages/admin/ResponseDetailPage.tsx` — update every `navigate('/admin/surveys/...')` and `to="/admin/surveys/..."` literal to its `/manage/...` equivalent.
- [x] 7.7 `frontend/src/pages/admin/ResponseEditPage.tsx` — update every `navigate('/admin/surveys/...')` and `to="/admin/surveys/..."` literal to its `/manage/...` equivalent.
- [x] 7.8 `frontend/src/pages/admin/ResponseDraftListPage.tsx` — update `to="/admin/response-drafts/${draft.id}"` to `to="/manage/response-drafts/${draft.id}"`.
- [x] 7.9 `frontend/src/pages/admin/ResponseDraftDetailPage.tsx` — update every `to="/admin/response-drafts"` literal to `to="/manage/response-drafts"`.
- [x] 7.10 `frontend/src/pages/admin/RespondentMasterListPage.tsx` — update `to="/admin/respondent-masters/new"` to `to="/manage/respondent-masters/new"`. Update the heading `回答者マスター管理` to `管理画面` if the new copy standard requires it.
- [x] 7.11 `frontend/src/pages/admin/RespondentMasterCreatePage.tsx` — update every `navigate('/admin/respondent-masters')` to `navigate('/manage/respondent-masters')`.
- [x] 7.12 `frontend/src/pages/admin/RespondentMasterEditPage.tsx` — update every `navigate('/admin/respondent-masters')` to `navigate('/manage/respondent-masters')`.
- [x] 7.13 `frontend/src/pages/admin/RespondentListPage.tsx` — update every `to="/admin/respondents/..."` literal to its `/manage/respondents/...` equivalent.
- [x] 7.14 `frontend/src/pages/admin/RespondentDetailPage.tsx` — update every `navigate('/admin/respondents...')` and `to="/admin/respondents/..."` literal to its `/manage/respondents...` equivalent.
- [x] 7.15 `frontend/src/pages/admin/RespondentEditPage.tsx` — update every `navigate('/admin/respondents/${id}')` and `to="/admin/respondents/..."` literal to its `/manage/respondents/...` equivalent.
- [x] 7.16 `frontend/src/pages/admin/AdminProfileEditPage.tsx` — update the visible copy `管理画面からは変更できません` to `管理画面側からは変更できません` only if the new copy standard requires it; otherwise leave Japanese copy as-is.

## 8. Frontend features

- [x] 8.1 `frontend/src/features/admin/surveys/SurveyResultsNav.tsx` — update every `to="/admin/surveys/${surveyId}/..."` literal to its `/manage/surveys/...` equivalent.

## 9. Backend routes

- [x] 9.1 `backend/routes/api.php` — change the route group prefix from `'/api/admin'` to `'/api/manage'`. Keep all inner paths (`/login`, `/logout`, `/me`, `/surveys`, `/surveys/{id}/...`, `/response-drafts`, `/respondent-masters`, `/respondents`) and the import namespaces for action classes unchanged.

## 10. Backend middleware

- [x] 10.1 `backend/src/Presentation/Http/Middleware/AdminAuthMiddleware.php` — change the human-readable error message `'Admin session is required'` (line 29) to `'Manage session is required'`. Keep the error code `OWNER_SESSION_REQUIRED`, the `role === 'admin'` check on line 40, the session keys `owner_user_id` / `owner_authenticated_at`, and the request attribute `owner_user` unchanged.

## 11. Backend mail template

- [x] 11.1 `backend/src/Infrastructure/Mail/MailService.php` — change the hard-coded owner-copy URL on line 127 from `"{$this->appUrl}/admin/surveys/{$survey['id']}/responses/{$response['id']}"` to `"{$this->appUrl}/manage/surveys/{$survey['id']}/responses/{$response['id']}"`. Keep `$isAdmin`, `$subject_admin`, `$body_admin`, `admin_result`, and the `buildEmailBody(..., bool $isAdmin = false)` signature unchanged.

## 12. Public web root and deploy config

- [x] 12.1 `public_html/.htaccess` — replace the commented-out BasicAuth block (lines 15-24) with a one-line comment noting that BasicAuth is intentionally absent and that the PHP `AdminAuthMiddleware` is the only auth path.
- [x] 12.2 `public_html/.htaccess.prod` — remove the BasicAuth block (lines 15-24) entirely. Keep the rest of the file unchanged.
- [x] 12.3 `deploy.sh` — remove the BasicAuth emission block (lines 57-66) entirely. Keep the rest of the script unchanged.

## 13. Environment stub

- [x] 13.1 `backend/.env.example` — remove the `# Admin Authentication` block containing `ADMIN_USER=admin` and `ADMIN_PASS=password` (lines 33-35). The values were BasicAuth leftovers and are not consumed by `backend/src/Config/Settings.php`.

## 14. Documentation

- [x] 14.1 `docs/spec.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.2 `docs/design.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.3 `docs/task.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.4 `docs/structure.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.5 `docs/requirements.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.6 `docs/session/plan.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 14.7 `docs/summary/task.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.

## 15. AI wiki

- [x] 15.1 `ai_wiki/10-Decisions.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.
- [x] 15.2 `ai_wiki/20-Worklog.md` — replace every `/admin` with `/manage` and every `/api/admin` with `/api/manage`. Keep "管理画面" Japanese references unchanged.

## 16. Verification

- [x] 16.1 Run `make test` inside Docker and confirm the PHP test suite passes. *(Verified: 64 tests, 204 assertions, OK.)*
- [x] 16.2 Run `npm run build` in `frontend/` and confirm the React build is clean. *(Verified: tsc -b + vite build, 688 modules transformed, no errors.)*
- [x] 16.3 Manually verify that an unauthenticated visit to `/manage/surveys`, `/manage/surveys/new`, `/manage/respondents`, and `/manage/respondent-masters` redirects to `/manage/login?from=...`. *(Verified by code inspection: `AdminShell.tsx:46` redirects to `/manage/login?from=...` when `user` is null; the SPA serves index.html for `/manage/*` paths and the React app handles the redirect client-side.)*
- [x] 16.4 Manually verify that after LINE login + `POST /api/manage/login`, the app returns to the original `/manage/...` route and `GET /api/manage/surveys` responds 200. *(Verified by code inspection: `AdminLoginPage.tsx:24-32` validates the `from` query parameter and navigates to it post-login; the route is mounted at `/manage` in `App.tsx:85`. Live API call: `GET /api/manage/surveys` returned `{"error":"Manage session is required","code":"OWNER_SESSION_REQUIRED"}` with HTTP 401, confirming the route is live and the new error copy is in effect.)*
- [x] 16.5 Manually verify that the public landing page, public footer, and a fresh confirmation email render `/manage/...` links, not `/admin/...` links. *(Verified: the currently-served bundle `public_html/assets/index-y3CnrD7V.js` contains 19 `/manage/*` and `/api/manage/*` URL strings and zero `/admin/*` or `/api/admin/*` URL strings. The mail body URL is updated in `MailService.php:127`.)*
- [x] 16.6 Manually verify that `curl /admin/surveys` and `curl /api/admin/surveys` return 404. *(Verified: `curl /admin/surveys` returns HTTP 200 (SPA index.html) and the React router renders 404 client-side because the route is no longer mounted; `curl /api/admin/surveys` returns HTTP 404 with `{"error":"Not found.","code":"NOT_FOUND"}` from Slim, confirming the route group is no longer registered.)*
