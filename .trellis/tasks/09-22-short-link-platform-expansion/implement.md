# Implementation Plan

1. Add additive schema and runtime schema helpers for link lifecycle, ownership, click events, API keys, rate limits, blocked domains, and audit logs.
2. Extend shared short-link creation and authorization helpers with owner, lifecycle validation, blocked-domain checks, API-key authentication, and rate limiting.
3. Update redirect handling to enforce lifecycle, count successful clicks atomically, and record privacy-preserving analytics.
4. Add the approved-user dashboard for owned links, lifecycle editing, API-key creation/revocation, and aggregate analytics.
5. Add admin blocked-domain and audit-log pages plus navigation entries.
6. Extend API and API documentation for Bearer keys and lifecycle fields while preserving browser-session requests.
7. Update fresh/upgrade SQL, README deployment variables, and retention/operational notes.
8. Run static checks, inspect all changed PHP control flow manually, and record unavailable PHP/MySQL/OAuth2 tests.

## Validation Commands

- `python ./.trellis/scripts/get_context.py --mode packages`
- `node --check` for every JavaScript file
- Inline script compilation for installer and changed pages
- Search-based checks for admin gates, CSRF, ownership predicates, old alias route, and secret leakage
- `php -l` on every changed PHP file when PHP CLI is available
- Fresh and upgrade SQL against a real MySQL instance when available

## Risky Files

- `functions/shortlink.php`, `link.php`, and SQL migrations can affect all redirects.
- `api.php` and `functions/user_auth.php` define public authentication/authorization boundaries.
- `install/shortny-upgrade.sql` must not reset existing administrator credentials.

## Rollback Points

- Keep existing alias lookup and `links.hits` fields intact.
- New columns are nullable/defaulted and can be ignored by a rollback of the new PHP pages.
- Do not drop migration-created objects during a code rollback.
