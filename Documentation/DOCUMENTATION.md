# User Aggregator API - Implementation Document

## 1. Project Goal
This Laravel project fetches random users on a schedule, saves them into normalized tables, and exposes a public API to filter and return user data.

## 2. Implemented Requirements

### 2.1 Scheduled Task
- A scheduled task is configured in `routes/console.php`.
- Command: `users:fetch-random`
- Frequency: every 5 minutes using:
  - `Schedule::command('users:fetch-random')->everyFiveMinutes();`

### 2.2 Fetching Random Users
- Command entry is implemented in:
  - `app/Console/Commands/FetchRandomUsers.php`
  - `app/Console/Commands/FetchRandomUsersLegacy.php` (alias for backward compatibility)
- Business logic is implemented in:
  - `app/Services/RandomUserService.php`
- On each execution:
  - Makes **5 separate HTTP calls** to `https://randomuser.me/api/`.
  - Reads one user from each response (`results[0]`).
  - Extracts:
    - Name
    - Email
    - Gender
    - City
    - Country
- Duplicate users are skipped when email already exists in `users` table.

### 2.3 Database Structure

#### users (main table)
- Implemented in `database/migrations/0001_01_01_000000_create_users_table.php`
- Fields:
  - `id`
  - `name`
  - `email` (unique)
  - timestamps

#### user_details
- Implemented in `database/migrations/2026_02_20_034452_create_user_details_table.php`
- Fields:
  - `id`
  - `user_id` (unique, foreign key to `users.id`, cascade on delete)
  - `gender`
  - timestamps

#### locations
- Implemented in `database/migrations/2026_02_20_034452_create_locations_table.php`
- Fields:
  - `id`
  - `user_id` (unique, foreign key to `users.id`, cascade on delete)
  - `city`
  - `country`
  - timestamps

## 3. Eloquent Model Design

### `App\Models\User`
- Table: `users`
- Fillable: `name`, `email`
- Relations:
  - `detail()` -> hasOne `UserDetail`
  - `location()` -> hasOne `Location`

### `App\Models\UserDetail`
- Table: `user_details`
- Fillable: `user_id`, `gender`
- Relation:
  - `user()` -> belongsTo `User`

### `App\Models\Location`
- Table: `locations`
- Fillable: `user_id`, `city`, `country`
- Relation:
  - `user()` -> belongsTo `User`

## 4. Public API

### 4.1 Endpoint
- `GET /api/users`
- Route file: `routes/api.php`
- Controller: `App\Http\Controllers\Api\UserController@index`

### 4.2 Supported Query Params
- `gender` (optional)
- `city` (optional)
- `country` (optional)
- `limit` (optional, default 10)
- `fields` (optional enhancement)
  - Comma-separated list, for example:
  - `fields=name,email,city`

### 4.3 Response Data
By default each user includes:
- `name`
- `email`
- `gender`
- `city`
- `country`

If `fields` is provided, only requested valid fields are returned.

### 4.4 Response Shape
```json
{
  "success": true,
  "message": "Users fetched successfully.",
  "count": 1,
  "data": [
    {
      "name": "John Doe",
      "email": "john@example.com",
      "gender": "male",
      "city": "Berlin",
      "country": "Germany"
    }
  ]
}
```

## 5. Scheduling in Production
Laravel scheduler needs one cron entry on the server:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This cron runs every minute, while Laravel executes `users:fetch-random` every 5 minutes based on schedule definition.

## 6. Main Files Added/Updated
- `app/Console/Commands/FetchRandomUsers.php`
- `app/Console/Commands/FetchRandomUsersLegacy.php`
- `app/Services/RandomUserService.php`
- `app/Http/Controllers/Api/UserController.php`
- `app/Models/User.php`
- `app/Models/UserDetail.php`
- `app/Models/Location.php`
- `bootstrap/app.php`
- `routes/console.php`
- `routes/api.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_02_20_034452_create_user_details_table.php`
- `database/migrations/2026_02_20_034452_create_locations_table.php`
- `database/create_database.sql`
- `database/factories/UserFactory.php`

## 7. Basic Run Steps
1. Create database:
   - `mysql -u root -p < database/create_database.sql`
2. Configure `.env` database values.
3. Run:
   - `php artisan migrate`
4. Run command manually once (optional):
   - `php artisan users:fetch-random`
5. Run server:
   - `php artisan serve`
6. Test API:
   - `GET /api/users`
   - `GET /api/users?gender=female&country=India&limit=5`
   - `GET /api/users?fields=name,email,country&limit=3`
