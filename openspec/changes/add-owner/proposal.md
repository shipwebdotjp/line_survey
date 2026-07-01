## Why

The current system assumes a single fixed administrator, which blocks the product from becoming a true SaaS. We need multi-owner support so each LINE-authenticated user can create and manage their own surveys, while keeping respondent data and survey responses isolated per owner.

## What Changes

- Add a `users` concept for LINE-authenticated survey owners.
- Make each survey belong to exactly one owner via `owner_user_id`. **BREAKING**
- Scope `respondent_masters` to the survey owner. **BREAKING**
- Scope `respondents` to the survey owner and identify them from `public_id` plus LINE login. **BREAKING**
- Replace BasicAuth-protected admin access with owner session-based authorization. **BREAKING**
- Keep response visibility restricted to the survey owner through survey ownership checks.
- Preserve the existing public survey flow, but make LIFF identification tenant-aware.

## Capabilities

### New Capabilities
- `owner-workspace`: LINE-authenticated survey owners, owner-scoped surveys/respondents/respondent masters, and owner-only response access.

### Modified Capabilities
- None.

## Impact

- Database schema: add `users`, add `owner_user_id` to `surveys`, `respondent_masters`, and `respondents`, and revise uniqueness constraints.
- Backend auth: replace `/api/admin/*` BasicAuth with owner session authentication.
- Survey and response APIs: add ownership checks on every admin-facing read and write path.
- LIFF identification: accept `public_id` so respondent identity is resolved within the correct owner scope.
- Frontend/admin UI: introduce owner login flow and remove assumptions that admin access is globally shared.
