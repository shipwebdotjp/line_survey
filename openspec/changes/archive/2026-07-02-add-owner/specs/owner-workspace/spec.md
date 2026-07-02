## ADDED Requirements

### Requirement: Owner authentication and session
The system MUST authenticate survey owners with LINE login and MUST create or reuse a `users` record for the authenticated LINE user. The system MUST establish an owner session that is separate from respondent session state and MUST require that session for all `/api/admin/*` endpoints.

#### Scenario: Owner login grants admin access
- **WHEN** a valid LINE identity token is submitted for owner login
- **THEN** the system MUST create or reuse the matching owner user
- **AND THEN** the system MUST establish an owner session for subsequent admin requests

#### Scenario: Missing owner session is rejected
- **WHEN** a request is sent to any `/api/admin/*` endpoint without a valid owner session
- **THEN** the system MUST reject the request with an unauthorized response

### Requirement: Surveys are owned by a single user
Each survey MUST have exactly one owner user. Survey creation, duplication, listing, retrieval, update, deletion, summary, and CSV export MUST only operate on surveys owned by the current owner session.

#### Scenario: Owner cannot access another owner's survey
- **WHEN** an owner requests a survey that belongs to a different owner
- **THEN** the system MUST not return that survey's data
- **AND THEN** the system MUST treat the resource as unavailable to the requester

#### Scenario: Newly created survey belongs to current owner
- **WHEN** an authenticated owner creates a survey
- **THEN** the system MUST assign the current owner's user ID as the survey owner

### Requirement: Respondent masters are scoped to the owner
The system MUST store `owner_user_id` on each respondent master. `master_code` MUST be unique per owner, and `line_display_name` MUST NOT be globally unique. Respondent master listing, creation, update, deletion, and import MUST only apply to the current owner's records.

#### Scenario: Duplicate master codes are allowed across owners
- **WHEN** two different owners import respondent masters that use the same `master_code`
- **THEN** the system MUST allow both records as long as each is unique within its owner scope

#### Scenario: Owner sees only own respondent masters
- **WHEN** an owner lists respondent masters
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
The system MUST allow response and response-draft administration only when the current owner session matches the owning survey's `owner_user_id`. Response list, detail, edit, delete, and CSV export operations MUST not expose responses from another owner's surveys.

#### Scenario: Owner cannot inspect another owner's response
- **WHEN** an owner requests a response whose survey belongs to a different owner
- **THEN** the system MUST not return that response's data
- **AND THEN** the system MUST treat the resource as unavailable to the requester

#### Scenario: Response export is owner-restricted
- **WHEN** an owner exports responses for a survey
- **THEN** the system MUST include only responses for surveys owned by that owner
