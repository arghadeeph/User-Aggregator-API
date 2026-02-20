# DETAILED EXPLANATION DOCUMENT

## 1. Architecture Approach

This project follows a simple layered structure so each part has one clear responsibility:

- `RandomUserService` handles external API calls and save logic.
- Console commands (`users:fetch-random` and legacy alias) only trigger the service.
- `UserController` handles HTTP request input and API response output.
- Models (`User`, `UserDetail`, `Location`) define table mapping and relationships.

This keeps the flow readable and easy to maintain without adding unnecessary abstraction.

## 2. Separation of Concerns

### Service Layer
- File: `app/Services/RandomUserService.php`
- Responsibility:
  - Make 5 separate API calls to `https://randomuser.me/api/`
  - Validate response structure
  - Skip failed API calls safely
  - Prevent duplicate email insertion
  - Save user, detail, and location records

### Command Layer
- Files:
  - `app/Console/Commands/FetchRandomUsers.php`
  - `app/Console/Commands/FetchRandomUsersLegacy.php`
- Responsibility:
  - Trigger service method `fetchAndStoreUsers()`
  - Print command result messages
- Why this is useful:
  - The command stays very small
  - Business logic is reusable from other entry points if needed

### Controller Layer
- File: `app/Http/Controllers/Api/UserController.php`
- Responsibility:
  - Read filter inputs from request (`gender`, `city`, `country`, `limit`, `fields`)
  - Build filtered query
  - Return transformed API output

## 3. Database Design

### Normalized Structure
- `users`: core identity (`name`, `email`)
- `user_details`: additional personal detail (`gender`)
- `locations`: location information (`city`, `country`)

### Relationship Design
- `users` -> `user_details` is one-to-one
- `users` -> `locations` is one-to-one
- `user_id` is unique in related tables, enforcing one record per user per relation

### Data Integrity
- Foreign keys are used
- `cascadeOnDelete` ensures related records are cleaned automatically when a user is deleted

## 4. Scheduler Strategy

- Scheduler definition is in `routes/console.php`
- Runs every 5 minutes:
  - `Schedule::command('users:fetch-random')->everyFiveMinutes();`

Inside each run, the service performs 5 separate API calls exactly as requested.

## 5. API Fetch Safety and Data Rules

The service includes defensive behavior:

- Connection failures are caught and skipped (no command crash).
- Non-success API responses are skipped.
- Missing keys in response payload are skipped.
- Duplicate email check prevents repeated user records.

This makes the ingestion process resilient and practical for scheduled automation.

## 6. Filtering Strategy

Filtering is implemented with relational queries using `whereHas`:

- `gender` filter checks relation `detail`
- `city` and `country` filters check relation `location`

Why this approach:
- Keeps filtering in SQL-level query logic
- Avoids loading unnecessary records first
- Works cleanly with normalized relational tables

`limit` is applied to control result size.

## 7. Field Selection Strategy

The controller supports optional `fields` selection:

- Builds a full user response object (`name`, `email`, `gender`, `city`, `country`)
- If `fields` is provided, returns only requested keys

This gives frontend flexibility without changing database schema or query shape.

## 8. Code Quality Decisions

- Kept architecture simple (Service + Command + Controller + Models)
- Avoided over-engineering (no repository pattern for this scope)
- Added focused comments only where flow is non-obvious
- Added defensive checks around unreliable external API calls
- Kept implementation readable and straightforward for backend review

## 9. End-to-End Flow Summary

1. Scheduler triggers command every 5 minutes.
2. Command calls `RandomUserService::fetchAndStoreUsers()`.
3. Service calls randomuser API 5 times.
4. For each valid user:
   - skip if email exists
   - insert into `users`
   - insert into `user_details`
   - insert into `locations`
5. Public API endpoint `/api/users` serves filtered and optionally field-limited data.
