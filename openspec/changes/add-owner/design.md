## Context

The current application is built around a single fixed administrator and a respondent-centric LIFF session. That model works for an internal tool, but it does not support a SaaS where many LINE-authenticated users create their own surveys and only see their own responses.

This change introduces a real owner boundary. Surveys, respondent masters, and respondents become owner-scoped data, while response visibility is derived from survey ownership. The backend must also replace BasicAuth on `/api/admin/*` with an owner session so each user can manage only their own workspace.

## Goals / Non-Goals

**Goals:**
- Introduce a first-class `users` owner model backed by LINE login.
- Scope surveys, respondent masters, and respondents to the owning user.
- Make owner access control the source of truth for admin endpoints.
- Keep response visibility restricted to the survey owner without duplicating ownership columns across every response row.
- Preserve the current public survey flow while making LIFF identification tenant-aware through `public_id`.

**Non-Goals:**
- Reworking the public survey UX beyond the minimum needed to pass tenant context.
- Introducing third-party auth providers beyond LINE.
- Building shared team workspaces or per-survey collaborators in this change.
- Re-architecting public respondent history, drafts, or response storage beyond the ownership boundary.

## Decisions

### 1. Use `users` as the owner identity and keep `respondents` separate

We will add a `users` table to represent survey owners. `respondents` will remain a distinct concept because it stores respondent-specific profile data and LINE identity per owner workspace.

Alternative considered: reuse `respondents` for owners. Rejected because respondent rows are tenant data, while owners are platform users with a different lifecycle, permissions, and navigation surface.

### 2. Scope tenant data with `owner_user_id`

`surveys`, `respondent_masters`, and `respondents` will each carry `owner_user_id`. Uniqueness becomes owner-scoped, for example `(owner_user_id, line_user_id)` and `(owner_user_id, master_code)`.

Alternative considered: introduce a separate tenant table and reference it everywhere. Rejected because the ownership model is currently one-to-one with a user, and an extra tenant abstraction adds complexity without a product need yet.

### 3. Keep `responses` ownership derived from `surveys`

`responses` and `response_drafts` will not gain redundant owner columns. Authorization will resolve ownership through `survey_id -> surveys.owner_user_id`.

Alternative considered: denormalize `owner_user_id` into every response row. Rejected because it duplicates the same fact, increases migration cost, and creates consistency risk if a survey owner ever changes.

### 4. Replace BasicAuth with owner sessions for `/api/admin/*`

Admin APIs will require a logged-in owner session instead of a static username/password. This lets each owner see only their own surveys and related data while keeping the route surface stable.

Alternative considered: keep BasicAuth and add ownership filters underneath. Rejected because BasicAuth cannot represent per-user identity and would block a multi-tenant SaaS workflow.

### 5. Resolve respondent tenant context from `public_id`

`/api/liff/identify` will receive `public_id` so the backend can resolve the survey owner before finding or creating a respondent. This prevents the client from choosing a tenant directly and keeps identity resolution server-side.

Alternative considered: send `owner_user_id` from the client. Rejected because the client should not be trusted to choose tenant scope.

### 6. Keep owner and respondent sessions logically separate

The same LINE account may act as an owner in one context and a respondent in another. Session state will therefore distinguish owner login state from respondent login state instead of trying to reuse the same session attributes for both roles.

Alternative considered: merge owner and respondent into a single session model. Rejected because the permissions, navigation, and target tables differ enough that collapsing them would create ambiguity.

## Risks / Trade-offs

- [Migration complexity] Existing data must be backfilled to an initial owner user -> Create a seed owner, backfill all surveys/respondents/respondent masters, and only then tighten constraints.
- [Authorization bugs] Owner checks must be applied consistently across all admin reads and writes -> Centralize ownership resolution in repositories/use cases and add integration coverage around owner mismatch cases.
- [Session confusion] Two LINE-based flows can be mistaken for each other -> Use separate session keys and keep owner login and respondent identify flows isolated.
- [Tenant leakage] A missing ownership filter could expose another owner's data -> Treat owner mismatch as not found for read paths and validate ownership on every mutation path.
- [Operational coupling] Owner changes on a survey could invalidate response access assumptions -> Keep survey ownership immutable in the first iteration or require an explicit migration path before allowing reassignment.

## Migration Plan

1. Add `users` and the new `owner_user_id` columns with nullable staging where needed.
2. Seed or migrate the current fixed administrator into the first owner user.
3. Backfill existing `surveys`, `respondent_masters`, and `respondents` to that owner.
4. Update owner login/session handling and route guards for `/api/admin/*`.
5. Update LIFF identification to require `public_id` and to scope respondent lookup by owner.
6. Switch owner-facing queries to filter through `owner_user_id`.
7. Tighten constraints to `NOT NULL` and owner-scoped unique indexes after backfill is complete.

Rollback is limited because this change alters both schema and auth behavior. A rollback would require restoring the previous application version and the pre-change database backup.

## Open Questions

- Should owner login be exposed as a dedicated API endpoint or handled through the existing LIFF entrypoints with a separate owner-facing front end?
- Should survey ownership be immutable after creation, or should reassignment be allowed later with an explicit migration flow?
- Should owner sessions and respondent sessions share the same cookie name but different session keys, or should they use separate session names entirely?
