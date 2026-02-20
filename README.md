# User Aggregator API

Laravel API that:
- fetches random users every 5 minutes from `https://randomuser.me/api/`
- stores data into normalized tables (`users`, `user_details`, `locations`)
- provides a public filtering endpoint

## Requirements
- PHP 8.2+
- Composer
- MySQL

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create database:

```bash
mysql -u root -p < database/create_database.sql
```

Update database settings in `.env`, then run:

```bash
php artisan migrate
```

## Run
```bash
php artisan serve
```

## Scheduler
The app schedules this command every 5 minutes:

```bash
php artisan users:fetch-random
```

For production, add cron:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Endpoint
`GET /api/users`

### Query params
- `gender` (optional)
- `city` (optional)
- `country` (optional)
- `limit` (optional, default 10)
- `fields` (optional, comma-separated): `name,email,gender,city,country`

### Example
```bash
GET /api/users?gender=female&country=India&limit=5&fields=name,email,country
```

### Response
- Returns a JSON object with:
  - `success` (boolean)
  - `message` (string)
  - `count` (number)
  - `data` (array of users)
- If `fields` is passed, only requested fields are returned for each user.

## Detailed Documentation
See `DOCUMENTATION.md` for a full breakdown of architecture, schema, flow, and implementation notes.
