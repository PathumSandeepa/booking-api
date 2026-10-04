# Booking API

A small Laravel 13 booking service for appointments in fixed daily slots. Data access goes through repository interfaces, and the domain logic lives in explicit action classes rather than in controllers.

## Requirements

- Docker + Docker Compose (recommended), or PHP 8.3+, Composer and PostgreSQL

## Running it locally (Docker)

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed   # optional sample data
```

The API is served on <http://localhost:3000>. The `.env` used by the container points at the `db` service:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=booking
DB_USERNAME=booking
DB_PASSWORD=secret
```

### Running it without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

SQLite works out of the box: set `DB_CONNECTION=sqlite` and create `database/database.sqlite`.

### Tests

```bash
docker compose exec app php artisan test   # or: php artisan test
```

Tests run against an in-memory SQLite database, so they need no external services.

- `tests/Feature/Booking/CreateBookingTest.php` — success, double-booking, same slot on another date, missing fields, past date, unknown slot, today, beyond the booking window.
- `tests/Feature/Booking/ListBookingsTest.php` — list all, filter by date, empty result, malformed filter.
- `tests/Feature/Booking/CancelBookingTest.php` — cancel, unknown id, slot freed after cancelling.
- `tests/Feature/Booking/ConcurrentBookingTest.php` — the unique constraint, and two identical requests.
- `tests/Unit/Booking/CreateBookingActionTest.php` — the action against a mocked repository, no database.

### Formatting

```bash
docker compose exec app ./vendor/bin/pint
```

GitHub Actions runs `pint --test` and the test suite on every push, see [.github/workflows/ci.yml](.github/workflows/ci.yml).

## Endpoints

Base URL: `http://localhost:3000`

| Method | Path             | Description                                |
| ------ | ---------------- | ------------------------------------------ |
| POST   | `/bookings`      | Create a booking                           |
| GET    | `/bookings`      | List bookings, optional `?date=YYYY-MM-DD` |
| DELETE | `/bookings/{id}` | Cancel a booking                           |

### POST /bookings

```json
{
    "name": "Jane Silva",
    "email": "jane@example.com",
    "date": "2026-12-15",
    "slot": "10:00"
}
```

Responses:

- `201 Created` with the created booking
- `422 Unprocessable Content` for validation failures
- `409 Conflict` when the date + slot pair is already taken

```json
{
    "data": {
        "id": "01m43j754r5gz00bkr51trey7r",
        "name": "Jane Silva",
        "email": "jane@example.com",
        "date": "2026-12-15",
        "slot": "10:00",
        "created_at": "2026-10-04T13:38:54+00:00"
    }
}
```

A conflict looks like this:

```json
{
    "message": "The 10:00 slot on 2026-12-15 is already booked.",
    "errors": { "slot": ["The 10:00 slot on 2026-12-15 is already booked."] }
}
```

### GET /bookings

Optional `?date=YYYY-MM-DD` filter. Returns `200 OK` with bookings ordered by date then slot, or `422` if the filter is not a valid `YYYY-MM-DD` date.

```json
{
    "data": [
        {
            "id": "01m43j754r5gz00bkr51trey7r",
            "name": "Jane Silva",
            "email": "jane@example.com",
            "date": "2026-12-15",
            "slot": "10:00",
            "created_at": "2026-10-04T13:38:54+00:00"
        }
    ]
}
```

### DELETE /bookings/{id}

```json
{ "message": "Booking cancelled." }
```

Returns `200 OK` when the booking existed, `404 Not Found` otherwise:

```json
{ "message": "Booking [nonexistent-id-12345] was not found." }
```

## Assumptions

- **Slots** are hourly, `09:00` to `17:00` inclusive (9 slots per day), defined in the `BookingSlot` enum. Sending anything else is a validation error listing the valid values.
- **Storage** is PostgreSQL via Docker; tests use in-memory SQLite. Nothing in the code is Postgres-specific.
- **IDs** are ULIDs stored in a plain string column. A string column (rather than a native `uuid` type) means a nonsense id such as `nonexistent-id-12345` returns a clean `404` instead of blowing up on a database cast error.
- A booking may be made for **today** — only dates strictly in the past are rejected.
- Bookings are limited to **90 days** ahead (`BOOKING_MAX_ADVANCE_DAYS`), to stop obviously unrealistic dates.
- Emails are lowercased and names trimmed before storage, so `Jane@Example.com` and `jane@example.com` are stored identically.
- There is no authentication; the endpoints are public and only rate limited (`BOOKING_RATE_LIMIT`, default 60 requests/minute).
- Cancellation is a hard delete. There is no audit trail.

## Project structure

```
app/
  Domain/Booking/
    Actions/              CreateBookingAction, ListBookingsAction, CancelBookingAction
    DataTransferObjects/  CreateBookingData
    Enums/                BookingSlot
    Exceptions/           SlotAlreadyBookedException, BookingNotFoundException
    Models/               Booking
    Repositories/         BookingRepository (interface)
  Infrastructure/Booking/
    Repositories/         EloquentBookingRepository (implementation)
  Http/
    Controllers/Booking/  StoreBookingController, IndexBookingController, DestroyBookingController
    Middleware/           ForceJsonResponse
    Requests/Booking/     StoreBookingRequest, IndexBookingRequest
    Resources/            BookingResource
  Providers/              RepositoryServiceProvider
```

The reasoning behind the split:

- **`Domain`** holds everything that describes the business: what a slot is, what a booking is, what rules apply, and what can go wrong. It does not know about HTTP.
- **`Infrastructure`** holds the one Eloquent-flavoured detail — the repository implementation. If storage changed, this is the only directory that would change.
- **`Http`** is a thin translation layer: validate the request, build a DTO, call an action, serialise the result.

Controllers are single-action (`__invoke`) classes. Each one does three things and then gets out of the way, which keeps the HTTP layer boring and the business rules in one readable place.

## The repository layer

`App\Domain\Booking\Repositories\BookingRepository` is an interface owned by the domain:

```php
public function all(?CarbonImmutable $date = null): Collection;
public function existsForSlot(CarbonImmutable $date, BookingSlot $slot): bool;
public function create(CreateBookingData $data): Booking;
public function deleteById(string $id): bool;
```

`App\Infrastructure\Booking\Repositories\EloquentBookingRepository` implements it, and `RepositoryServiceProvider` binds the two. Controllers and actions only ever type-hint the interface, so no Eloquent query builder ever appears outside the infrastructure directory.

Two consequences worth calling out:

- The unit test for `CreateBookingAction` mocks the interface and touches no database at all.
- `deleteById` returns a `bool` rather than throwing. Deciding that "nothing was deleted" means "404" is a domain decision, so the action makes it, not the repository.

## The double-booking rule

This is enforced in two places, deliberately.

**1. An availability check in the action.** `CreateBookingAction` asks the repository whether that date + slot is taken and throws `SlotAlreadyBookedException` if so. This exists to produce a clear, friendly `409` with a message naming the date and slot.

**2. A unique index on `(date, slot)` in the database.** This is the part that is actually authoritative. The check in step 1 is a read followed by a write, and between those two statements another request can insert the same pair — a classic time-of-check-to-time-of-use race. Under concurrency, step 1 will eventually let a duplicate through.

So the repository catches Laravel's `UniqueConstraintViolationException` on insert and rethrows it as the same `SlotAlreadyBookedException`. The loser of a race gets the identical `409` response as someone who simply asked for a taken slot; it is indistinguishable from the outside. The database, not the application, is the thing guaranteeing correctness.

`ConcurrentBookingTest` covers both paths: one test goes through the repository directly to prove the constraint fires when the availability check is bypassed, and one goes through the HTTP endpoint twice.

### If this had to handle many simultaneous requests

The unique index already makes the system _correct_ under load — it cannot double-book, regardless of traffic. What I would change is how gracefully it behaves:

- Wrap create in a transaction with `SELECT ... FOR UPDATE` on a slot-inventory row, so contention blocks briefly instead of failing. This matters more if a slot ever gains capacity greater than one.
- Drop the pre-check and rely solely on the constraint. It saves a query, and the pre-check buys nothing under concurrency anyway. I kept it here because it makes intent obvious when reading the action.
- For a distributed deployment, a short-lived Redis lock keyed on `date:slot` would absorb the contention before it reaches the database.
- Idempotency keys on `POST /bookings` so a client retry after a timeout does not create a second booking.

## What I would add with more time

- Authentication and per-user ownership, so people can only cancel their own bookings.
- Soft deletes plus a status field (`confirmed` / `cancelled`) instead of hard deletes, to keep history.
- A `GET /slots?date=...` endpoint returning availability, so a client does not have to guess and get rejected.
- Business rules that a real venue would want: closed weekends and holidays, per-slot capacity, and blocking a past slot on the current day.
- Pagination on `GET /bookings`; today it returns everything.
- Confirmation emails dispatched to a queue.

## Notes on working in Laravel and the repository pattern

A few things that cost me time and are worth recording.

**What was straightforward.** Form requests, the service container binding an interface to a concrete class, API resources, and factories are all well-signposted. Writing an interface and binding it in a provider took about five minutes.

**What was less obvious.**

- Moving models out of `App\Models` breaks Laravel's factory discovery, since it resolves factories by naming convention. The fix was adding a `newFactory()` method on the model pointing at `BookingFactory`.
- I named a helper method `date()` on a form request. `Illuminate\Http\Request` already has a `date()` method with a different signature, so every list request returned a `500`. Renaming it to `filterDate()` fixed it. The lesson is that extending a framework base class means inheriting its whole method surface.
- Dates needed care. `date` is cast to `immutable_date`, and `CarbonImmutable::addDay()` returns a new instance rather than mutating — I wrote a test that quietly asserted the wrong day before noticing.
- Deciding where the double-booking rule belonged took the most thought. Putting it only in the action reads well but is racy; putting it only in the database gives an ugly error. Doing both, with the repository translating the database error into the domain exception, kept the domain honest without leaking SQL details upward.

**How I worked through it.** Mostly by building the endpoints first, running the attached Postman scenarios against the running container with `curl`, and then converting each scenario into a test so regressions would surface automatically.

## Self-testing

The supplied `Booking-API.postman_collection.json` collection runs against `http://localhost:3000`. All eight requests behave as expected:

| #   | Request                  | Result |
| --- | ------------------------ | ------ |
| 1   | Create booking           | `201`  |
| 2   | Conflicting date + slot  | `409`  |
| 3   | Missing / invalid fields | `422`  |
| 4   | Date in the past         | `422`  |
| 5   | List all                 | `200`  |
| 6   | List filtered by date    | `200`  |
| 7   | Delete existing booking  | `200`  |
| 8   | Delete unknown id        | `404`  |

For request 7, copy the `id` from request 1's response into the `bookingId` collection variable.
