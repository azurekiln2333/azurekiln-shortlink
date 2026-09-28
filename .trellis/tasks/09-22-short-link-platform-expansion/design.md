# Design: Short Link Platform Expansion

## Boundaries

The existing `links` row remains the source of truth for alias and destination. New capability is layered through shared PHP helpers, additive MySQL schema, root user pages, admin pages, and the existing API.

## Data Flow

```text
OAuth2 sub -> shortny_users -> approved session
approved session/API key -> create_short_link_record -> links
request /alias -> link.php -> lifecycle check -> click event + hit counter -> redirect
links/click events -> user/admin aggregate queries -> dashboard/statistics UI
admin action -> audit helper -> shortny_audit_logs
admin domain rule -> shortny_blocked_domains -> creation validation
```

## Schema

- `links.owner_user_id BIGINT UNSIGNED NULL`: nullable for historical rows; indexed.
- `links.status ENUM('active','paused') NOT NULL DEFAULT 'active'`.
- `links.expires_at DATETIME NULL`.
- `links.max_hits INT UNSIGNED NULL`.
- `shortny_clicks`: link id, event time, referrer, device type, browser, OS, IP hash; indexed by link/time.
- `shortny_api_keys`: user id, label, prefix, SHA-256 key hash, created/last-used/revoked timestamps.
- `shortny_rate_limits`: subject hash, action, bucket start, count; unique action/subject/bucket.
- `shortny_blocked_domains`: normalized host, enabled flag, note, timestamps, unique host.
- `shortny_audit_logs`: actor, action, target, JSON metadata, IP hash, timestamp; indexed by time and target.

Fresh SQL includes all objects. Upgrade SQL adds columns/tables for legacy installs. Runtime schema creation covers tables, while deployment still needs the upgrade script for additive columns.

## Authentication and Authorization

- `approved_user_or_admin($db)` remains the creation authorization boundary.
- `current_user_record($db)` identifies a local user by OAuth2 subject-backed row.
- `authenticate_api_request($db)` accepts a Bearer key or falls back to the existing session + CSRF path.
- Ownership predicates always use `owner_user_id = current user id`; administrators bypass ownership but remain auditable.

## Redirect Semantics

1. Look up by alias using the existing case-sensitive query.
2. Reject missing, paused, expired, or exhausted links without changing hit counts.
3. Validate password before recording a successful click.
4. Atomically increment `links.hits` only when the lifecycle predicate still allows a click.
5. Insert an analytics event with normalized user-agent fields and a salted IP hash.
6. Use the existing splash/basic redirect behavior and current request host for generated links.

## API Contract

- Browser session: existing JSON fields and CSRF requirement remain unchanged.
- API key: `Authorization: Bearer <plaintext-key>`; no CSRF token required because the credential is not ambient browser state.
- Create response keeps `shorturl`, `stats`, and `longurl`, and may add lifecycle metadata.
- Invalid/revoked key: 401. Approved-but-rate-limited: 429. Blocked destination or invalid lifecycle input: 422.

## Rate Limiting

Use a database bucket helper with a short fixed window and deterministic 429 response. Key by hashed client IP plus account/key id where available. Limit OAuth2 starts, create operations, API operations, and excessive redirect attempts. No raw IP is persisted.

## Rollout and Rollback

- Take a database backup before running the upgrade SQL.
- Add nullable/defaulted columns first so old code can still read rows.
- Deploy code after schema migration; old links remain active by default.
- Rollback code leaves additive columns/tables unused; do not drop them during rollback.
- If analytics volume is too high, disable event writes with `SHORTNY_ANALYTICS_ENABLED=0` while preserving redirect and hit-count behavior.

## Explicitly Not Doing

No geolocation vendor, billing, workspaces, or full prepared-statement rewrite is included in this iteration.
