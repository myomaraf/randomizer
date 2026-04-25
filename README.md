# Omaraf Randomizer

Omaraf Randomizer is an open-source Laravel 11 raffle randomizer API.

The app:

- accepts a `raffle_id` and an array of ticket UUIDs
- selects exactly one winner UUID using a transparent algorithm
- stores raffle audit data and canonical ticket list in the database
- exposes public verification pages for any raffle

## Requirements

- PHP 8.2+
- Composer

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

## API

### `POST /api/randomize`

Requires header:

- `X-API-KEY: <RAW_KEY>`

Request body:

```json
{
  "raffle_id": "raffle_001",
  "ticket_uuids": [
    "550e8400-e29b-41d4-a716-446655440000",
    "550e8400-e29b-41d4-a716-446655440001"
  ]
}
```

Example `curl` request:

```bash
curl -X POST "http://127.0.0.1:8000/api/randomize" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: RAW_KEY" \
  -d '{
    "raffle_id": "raffle_001",
    "ticket_uuids": [
      "550e8400-e29b-41d4-a716-446655440000",
      "550e8400-e29b-41d4-a716-446655440001",
      "550e8400-e29b-41d4-a716-446655440002"
    ]
  }'
```

Example success response:

```json
{
  "raffle_id": "raffle_001",
  "selected_uuid": "550e8400-e29b-41d4-a716-446655440002",
  "meta": {
    "count": 3,
    "algorithm": "Omaraf Randomizer v1",
    "audit": {
      "raffle_id": "raffle_001",
      "uuids_sha256": "9f2e...",
      "nonce_hex": "37c5...",
      "digest_sha256": "b2ed...",
      "index_selected": 2,
      "count": 3,
      "timestamp_utc": "2026-02-23T19:40:15Z",
      "algorithm_version": "1"
    }
  }
}
```

## Validation rules

- `raffle_id` required, string, max 100, unique
- `ticket_uuids` required, array, min 1, max configurable
- `ticket_uuids.*` valid UUID
- duplicate `raffle_id` returns `409` with:
  - `{ "message": "raffle_id already exists" }`

Validation errors return HTTP `422` with a standard Laravel validation payload.

## API keys

`POST /api/randomize` uses DB-backed API keys.

The raw key is never stored directly. The app stores:

- `api_keys.key_hash = sha256(raw_key)`

Create a key row manually (MySQL example):

```sql
INSERT INTO api_keys (name, key_hash, is_active, created_at, updated_at)
VALUES ('Main', SHA2('RAW_KEY_HERE', 256), 1, NOW(), NOW());
```

Auth behavior:

- missing `X-API-KEY` => `401` `{ "message": "API key missing" }`
- invalid/inactive key => `401` `{ "message": "Invalid API key" }`

## Persistence model

Tables:

- `api_keys`
- `raffles`
- `raffle_tickets`

Each raffle stores:

- `raffle_id`, winner UUID, digest/audit fields, algorithm version, timestamp

Each ticket stores:

- canonical UUID and sorted position for auditability

## Public verification UI

Routes:

- `GET /raffles` - form to enter `raffle_id`
- `GET /raffles/{raffle_id}` - public details page with:
  - winner UUID
  - audit fields
  - formula + steps
  - DataTable-backed ticket list loaded via API (server-side pagination/search/sort)
- `GET /raffles/{raffle_id}/tickets/data` - DataTables JSON endpoint for ticket rows
- `GET /raffles/{raffle_id}/tickets/export.csv` - export all raffle tickets as CSV
- `GET /raffles/{raffle_id}/tickets/export.xls` - export all raffle tickets as Excel (`.xls`)

Lookup behavior:

- Typing an ID into the `/raffles` search box opens that raffle.
- Query-string auto-open is supported:
  - `GET /raffles?rafflee=raffle_123`
- If the raffle does not exist (or another lookup error occurs), the app redirects back to `/raffles` and shows a toast error message instead of returning a raw `404` page.

## Algorithm and audit

Selection algorithm:

1. Canonicalize `ticket_uuids` (`trim` + `lowercase`).
2. Sort lexicographically.
3. `nonce_hex = bin2hex(random_bytes(32))`.
4. `digest_sha256 = sha256(joined_sorted_uuids + ":" + raffle_id + ":" + nonce_hex)`.
5. Use first 16 hex chars as a 64-bit seed and compute `index_selected = seed % count`.
6. Winner is `sorted_uuids[index_selected]`.

To verify a raffle from `/raffles/{raffle_id}`:

- rebuild canonical sorted ticket list from stored tickets
- recompute `uuids_sha256`
- recompute `digest_sha256` using stored `raffle_id` and `nonce_hex`
- recompute `index_selected` from first 16 hex chars and compare with stored index/winner

## Configuration

Environment variables:

- `OMARAF_MAX_UUIDS=20000`
- `OMARAF_RATE_LIMIT=60`
- `OMARAF_ALGORITHM_VERSION=1`

The endpoint is rate-limited per IP using Laravel `RateLimiter`.

## Testing

```bash
php artisan test
```

Feature tests included:

- missing API key => `401`
- invalid API key => `401`
- valid API key => `200` and DB persistence
- duplicate `raffle_id` => `409`
- public raffle page shows winner and audit
- validation failures for invalid/missing ticket UUID inputs

## License

This project is licensed under the MIT License. See `LICENSE`.
