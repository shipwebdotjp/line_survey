## ADDED Requirements

### Requirement: Management URL namespace
The system MUST expose all owner-facing management surface under the URL namespace `/manage` (frontend) and `/api/manage` (backend). The application MUST NOT emit `/admin` or `/api/admin` URLs in any frontend route, API client base path, outbound link, or generated email body. The `admin` role value on `users.role` is reserved for a future super-admin role and MUST NOT be used to authorize access to the `/api/manage/*` endpoints in the current implementation; the `user` role continues to authorize management access through the owner session.

#### Scenario: Frontend management routes live under /manage
- **WHEN** the React Router configuration is inspected
- **THEN** the management tree MUST be mounted at `/manage`
- **AND THEN** the login entry MUST be at `/manage/login`
- **AND THEN** no `<Route path="/admin/...">` element MUST be present in the route table

#### Scenario: Backend management routes live under /api/manage
- **WHEN** the Slim route group for owner-facing endpoints is inspected
- **THEN** the group prefix MUST be `/api/manage`
- **AND THEN** no route MUST be registered under `/api/admin`

#### Scenario: Frontend API clients target /api/manage
- **WHEN** any owner-facing API client constructs its base path
- **THEN** the base path MUST start with `/api/manage`
- **AND THEN** no API client base path or inline URL MUST start with `/api/admin`

#### Scenario: Unauthenticated access redirects to /manage/login
- **WHEN** an unauthenticated request reaches any `/manage/*` path other than `/manage/login`
- **THEN** the management shell MUST redirect the user to `/manage/login` with the original path preserved in the `from` query parameter
- **AND THEN** when the user completes LINE login and `POST /api/manage/login` succeeds
- **AND THEN** the application MUST return the user to the original `/manage/...` path
- **AND THEN** `GET /api/manage/surveys` MUST respond with 200

#### Scenario: 401 from any /api/manage endpoint redirects to /manage/login
- **WHEN** any `/api/manage/*` request returns 401 while the current page is on a `/manage/*` path other than `/manage/login`
- **THEN** the admin fetch wrapper MUST redirect the browser to `/manage/login?from=<current-path-and-search>`
- **AND THEN** the in-flight request MUST be short-circuited

#### Scenario: Public links and emails emit /manage URLs
- **WHEN** the public landing page, public footer, or confirmation-email body renders a link to a management screen
- **THEN** the link MUST target a `/manage/...` path
- **AND THEN** the rendered text MUST NOT contain `/admin` or `/api/admin`

#### Scenario: Logout redirects to /manage/login
- **WHEN** an authenticated owner logs out
- **THEN** the admin auth context MUST redirect the browser to `/manage/login`
- **AND THEN** the redirect target MUST NOT be `/admin/login`

## MODIFIED Requirements

### Requirement: Owner authentication and session
The system MUST authenticate survey owners with LINE login and MUST create or reuse a `users` record for the authenticated LINE user. The system MUST establish an owner session that is separate from respondent session state and MUST require that session for all `/api/manage/*` endpoints. When the session is missing or invalid, the system MUST reject the request with the error code `OWNER_SESSION_REQUIRED` and the human-readable message `Manage session is required` (or `Invalid owner session` for an invalid session).

#### Scenario: Owner login grants manage access
- **WHEN** a valid LINE identity token is submitted to `POST /api/manage/login`
- **THEN** the system MUST create or reuse the matching owner user
- **AND THEN** the system MUST establish an owner session for subsequent `/api/manage/*` requests

#### Scenario: Missing owner session is rejected
- **WHEN** a request is sent to any `/api/manage/*` endpoint without a valid owner session
- **THEN** the system MUST reject the request with an unauthorized response
- **AND THEN** the response body MUST contain the error code `OWNER_SESSION_REQUIRED` and the message `Manage session is required`

### Requirement: Surveys are owned by a single user
Each survey MUST have exactly one owner user. Survey creation, duplication, listing, retrieval, update, deletion, summary, and CSV export under `/api/manage/surveys*` MUST only operate on surveys owned by the current owner session.

#### Scenario: Owner cannot access another owner's survey
- **WHEN** an owner requests a survey under `/api/manage/surveys/{id}` that belongs to a different owner
- **THEN** the system MUST not return that survey's data
- **AND THEN** the system MUST treat the resource as unavailable to the requester

#### Scenario: Newly created survey belongs to current owner
- **WHEN** an authenticated owner posts to `/api/manage/surveys`
- **THEN** the system MUST assign the current owner's user ID as the survey owner

### Requirement: Respondent masters are scoped to the owner
The system MUST store `owner_user_id` on each respondent master. `master_code` MUST be unique per owner, and `line_display_name` MUST NOT be globally unique. Respondent master listing, creation, update, deletion, and import under `/api/manage/respondent-masters*` MUST only apply to the current owner's records.

#### Scenario: Duplicate master codes are allowed across owners
- **WHEN** two different owners import respondent masters through `/api/manage/respondent-masters/import` that use the same `master_code`
- **THEN** the system MUST allow both records as long as each is unique within its owner scope

#### Scenario: Owner sees only own respondent masters
- **WHEN** an owner lists respondent masters through `GET /api/manage/respondent-masters`
- **THEN** the system MUST return only respondent masters owned by that owner

### Requirement: Respondents are tenant-scoped and resolved from public_id
The system MUST store `owner_user_id` on each respondent. LIFF identification MUST accept `public_id`, resolve the owning survey from that identifier, and create or reuse respondents using the pair `(owner_user_id, line_user_id)` as the uniqueness boundary.

#### Scenario: Same LINE user can exist under different owners
- **WHEN** the same LINE user identifies through two surveys owned by different users
- **THEN** the system MUST create or reuse a separate respondent record for each owner scope

#### Scenario: Identification uses survey ownership
- **WHEN** LIFF identification is submitted with a valid `public_id`
- **THEN** the system MUST resolve the survey owner from that `public_id`
- **AND THEN** the system MUST identify the respondent within that owner scope

### Requirement: Responses are visible only to the survey owner
The system MUST allow response and response-draft administration only when the current owner session matches the owning survey's `owner_user_id`. Response list, detail, edit, delete, and CSV export operations under `/api/manage/surveys/{id}/responses*` and `/api/manage/response-drafts*` MUST not expose responses from another owner's surveys.

#### Scenario: Owner cannot inspect another owner's response
- **WHEN** an owner requests a response through `/api/manage/surveys/{id}/responses/{response_id}` whose survey belongs to a different owner
- **THEN** the system MUST not return that response's data
- **AND THEN** the system MUST treat the resource as unavailable to the requester

#### Scenario: Response export is owner-restricted
- **WHEN** an owner exports responses through `/api/manage/surveys/{id}/responses.csv`
- **THEN** the system MUST include only responses for surveys owned by that owner
