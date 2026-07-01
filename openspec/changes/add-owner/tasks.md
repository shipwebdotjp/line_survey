## 1. Database and migration

- [ ] 1.1 Add a `users` table and `owner_user_id` columns to `surveys`, `respondent_masters`, and `respondents` with owner-scoped indexes and foreign keys.
- [ ] 1.2 Backfill the current fixed administrator into an initial owner user and migrate existing surveys, respondent masters, and respondents to that owner.
- [ ] 1.3 Tighten the migrated columns to `NOT NULL` and replace global unique constraints with owner-scoped uniqueness rules.

## 2. Owner authentication and session handling

- [ ] 2.1 Add a LINE-based owner login flow that upserts `users` and establishes an owner session.
- [ ] 2.2 Replace `BasicAuth` protection on `/api/admin/*` with an owner session guard.
- [ ] 2.3 Add owner logout and session invalidation behavior that clears owner-specific session state.

## 3. Tenant-scoped survey and respondent data

- [ ] 3.1 Update survey repositories and use cases so create, list, get, update, delete, duplicate, summary, and CSV operations are filtered by the current owner.
- [ ] 3.2 Update respondent master repositories and import logic so all reads and writes are scoped by `owner_user_id`.
- [ ] 3.3 Update respondent lookup and creation so LIFF identification accepts `public_id` and resolves respondents within the owning survey's tenant scope.

## 4. Response authorization

- [ ] 4.1 Enforce survey-owner checks in admin response list, detail, edit, delete, export, and response-draft endpoints.
- [ ] 4.2 Keep public respondent flows working with respondent session state while ensuring response access still resolves through survey ownership.

## 5. Frontend owner and LIFF flows

- [ ] 5.1 Add an owner login entrypoint and make the admin shell react to owner session state.
- [ ] 5.2 Pass `public_id` through the LIFF identification flow and surface tenant-aware login failures to the public survey UI.
- [ ] 5.3 Remove BasicAuth assumptions from admin API clients and handle session-expiration redirects cleanly.
