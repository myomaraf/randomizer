# Omaraf Randomizer

Omaraf Randomizer is an open-source Laravel 11 API that accepts an array of UUIDs and returns exactly one selected UUID using a transparent, auditable selection algorithm.

The API is designed to be:

- easy to audit
- deterministic after the request (using the published audit trail)
- unpredictable before the request (using cryptographic per-request entropy)

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

App homepage:

- `GET /` - minimal product page

## API

### `POST /api/randomize`

Request body:

```json
{
  "uuids": [
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
  -H "Origin: https://myomaraf.com" \
  -d '{
    "uuids": [
      "550e8400-e29b-41d4-a716-446655440000",
      "550e8400-e29b-41d4-a716-446655440001",
      "550e8400-e29b-41d4-a716-446655440002"
    ]
  }'
```

Example success response:

```json
{
  "selected_uuid": "550e8400-e29b-41d4-a716-446655440002",
  "meta": {
    "count": 3,
    "algorithm": "sha256(sorted_uuid_list|timestamp_bucket_utc|server_nonce_hex) % count",
    "audit": {
      "uuids_sha256": "02d1b6...",
      "count": 3,
      "digest_sha256": "9cc8d3...",
      "index": 2,
      "timestamp_utc": "2026-02-23T18:01:25Z",
      "timestamp_bucket_utc": "2026-02-23T18:01Z",
      "server_nonce_sha256": "442c6a...",
      "server_nonce_hex": "8b71...",
      "algorithm_version": "v1"
    }
  }
}
```

## Validation rules

- `uuids` is required
- `uuids` must be an array
- `uuids` must contain at least one element
- each entry must be a valid UUID v1-v5
- max array size is configurable (`OMARAF_MAX_UUIDS`, default `20000`)

Validation errors return HTTP `422` with a standard Laravel validation payload.

## Configuration

Environment variables:

- `OMARAF_ALLOWED_ORIGINS=https://myomaraf.com,https://www.myomaraf.com`
- `OMARAF_MAX_UUIDS=20000`
- `OMARAF_RATE_LIMIT_PER_MINUTE=60`
- `OMARAF_ALGORITHM_VERSION=v1`

Config file:

- `config/omaraf.php`

## Rate limiting

The endpoint is rate-limited with Laravel `RateLimiter`:

- default `60` requests per minute per IP
- limiter key: `randomize`
- applied to `POST /api/randomize` via middleware `throttle:randomize`

## Origin restriction

`POST /api/randomize` is protected by `EnsureAllowedOrigin` middleware.

The middleware allows requests only when `Origin` or `Referer` resolves to one of the configured allowed origins. By default:

- `https://myomaraf.com`
- `https://www.myomaraf.com`

Blocked requests receive HTTP `403`:

```json
{
  "message": "Request origin is not allowed.",
  "allowed_origins": [
    "https://myomaraf.com",
    "https://www.myomaraf.com"
  ]
}
```

### Security limitations of Origin/Referer checks

- `Origin` and `Referer` are browser-oriented headers and should be treated as abuse reduction, not strong authentication.
- Non-browser clients can spoof these headers.
- Some privacy tools / proxies may remove or alter `Referer`.
- For stronger guarantees, combine with API keys, signatures, IP controls, or gateway/WAF rules.

## Algorithm and audit trail

Algorithm steps:

1. Canonicalize UUIDs (`trim`, `lowercase`).
2. Sort UUIDs lexicographically to remove caller order influence.
3. Compute digest input: `joined_sorted_uuids | timestamp_bucket_utc | server_nonce_hex`.
4. Generate `digest_sha256 = SHA-256(digest_input)`.
5. Compute `index = digest_sha256 mod count`.
6. Select `selected_uuid = sorted_uuids[index]`.

Audit fields explain the exact request selection context:

- `uuids_sha256`: hash of the canonical sorted UUID list
- `count`: number of UUIDs processed
- `digest_sha256`: final digest used for selection
- `index`: selected index in canonical sorted list
- `timestamp_utc`: exact processing timestamp
- `timestamp_bucket_utc`: bucket included in digest input
- `server_nonce_sha256`: integrity hash of per-request nonce
- `server_nonce_hex`: nonce value used in digest input
- `algorithm_version`: configurable algorithm label

Why sorting + hashing improves transparency:

- Sorting removes order-based manipulation from callers.
- Hashes let auditors verify the exact canonical input and output derivation without ambiguity.
- Per-request cryptographic nonce keeps outcomes unpredictable before execution.

## Testing

Run tests:

```bash
php artisan test
```

Feature tests included:

- success case
- invalid UUID returns `422`
- missing `uuids` returns `422`
- blocked origin returns `403`
- large array within limit succeeds

## License

This project is licensed under the MIT License. See `LICENSE`.
