# Short Link Platform Production and Feature Expansion

## Goal

Turn the current controlled short-link site into a usable multi-user short-link platform while preserving every historical alias and the current multi-domain behavior.

## Confirmed Facts

- OAuth2 `sub` is the local user identity; first-time users are pending until an administrator approves them.
- New link creation is already restricted to approved users or administrators.
- Historical rows in `links` contain no owner and must remain resolvable from any configured host/subdomain.
- The application is PHP/MySQLi without a Composer or test framework in this checkout.
- The current product has custom aliases, password links, basic hit counts, QR generation, a browser-session API, site settings, ads, and basic user moderation.

## Requirements

### R1. User-owned link management

Approved users can view, search, paginate, pause, resume, edit, and delete links created after this migration. Historical links with no owner remain visible to administrators and continue resolving.

### R2. Link lifecycle controls

New links support active/paused state, optional expiration time, and optional maximum successful click count. The redirect endpoint enforces these controls server-side. Existing links default to active with no expiration and no click limit.

### R3. Analytics

Successful redirects record an event containing timestamp, referrer, user-agent-derived device/browser/OS, and a privacy-preserving IP hash. User and admin views show aggregate totals and a recent daily trend without exposing raw IP addresses.

### R4. API credentials

Approved users can create and revoke personal API keys. API-key requests use `Authorization: Bearer`; browser-session requests remain supported with CSRF protection. The raw key is displayed only at creation time and only its hash is stored.

### R5. Production controls

Apply database-backed rate limits to OAuth2 starts, link creation, API creation, and redirect abuse signals. Administrators can block destination domains; new links reject blocked hosts. Record security-relevant user/admin/link actions in an audit log.

### R6. Compatibility and operations

Fresh install and upgrade SQL must create the new schema. Runtime fallback creation must support an older deployment that has not manually run the migration. No secret is written to source. API and deployment documentation must describe the new contracts.

## Out of Scope

- Third-party GeoIP database or external geolocation service.
- Team workspaces, billing, subscriptions, SSO provider management, and custom branded domains.
- Changing the historical alias format or binding aliases to one canonical domain.
- Replacing the existing PHP/MySQLi stack with a framework.

## Acceptance Criteria

- [ ] A pre-migration link resolves unchanged from multiple hosts and is not blocked by missing owner metadata.
- [ ] An approved user can create a link, see it in the user dashboard, edit its destination, pause/resume it, and delete it; another user cannot mutate it.
- [ ] Expired, paused, and click-limited links stop redirecting with a clear error page; successful click counts remain consistent.
- [ ] The dashboard shows total clicks, recent daily counts, referrer/device/browser/OS aggregates, and never displays raw IP addresses.
- [ ] A newly created API key can create links with Bearer auth; revoked/invalid keys receive 401 and browser-session CSRF behavior remains intact.
- [ ] Blocked destination domains are rejected for both web and API creation.
- [ ] Rate-limit responses are deterministic and do not reveal database details.
- [ ] Admin audit and blocked-domain pages are protected by admin session and CSRF.
- [ ] Fresh and upgrade SQL contain the same new tables/columns, and existing administrator credentials are preserved during upgrade.
- [ ] Static JavaScript checks pass; PHP syntax and real MySQL/OAuth2 smoke tests are documented as requiring a server environment.

## Risks and Deferred Items

- The current database wrapper uses escaped SQL rather than prepared statements; new code must centralize escaping and preserve existing behavior, with a prepared-statement migration as follow-up.
- Analytics increases write volume on redirects; indexes and a configurable retention cleanup path are required before high-volume deployment.
- IP hashing requires `SHORTNY_ANALYTICS_SALT`; without it, analytics collection must not store raw IP data.
