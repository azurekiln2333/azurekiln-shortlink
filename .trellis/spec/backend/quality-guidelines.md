# Quality Guidelines

## Scope / Trigger

These rules apply to PHP endpoints, admin pages, OAuth2 callbacks, and the public short-link route.

## Configuration and URL Signatures

- `app_config($name, $default = '')` returns a raw scalar from environment or root `config.php`; explicit empty environment values override file values.
- `oauth2_config()` returns the existing provider/client/callback/allowlist array, normalized through `oauth2_env()`.
- `short_link_url($alias)` returns an absolute `link.php?id=encoded-alias` URL for the current host and deployment directory.

## Required Patterns

- Every admin endpoint begins with `start_app_session()` and `admin_session_active()`.
- Every state-changing form/API request uses `csrf_valid()` and an explicit HTTP method.
- New short-link creation is authorized by `approved_user_or_admin($db)` in the server-side endpoint; hiding the form is not authorization.
- OAuth2 callbacks validate session state, PKCE verifier, expiry, and the userinfo `sub` before creating a local session.
- User-visible database and OAuth2 errors are generic; details go to the server error log.
- All generated URLs use the current request host so aliases remain usable on any configured domain/subdomain.
- Deployment values live in root `config.php`, loaded through `app_config()`. Only `functions/config.php` reads environment variables; present environment values override file values.
- Public navigation uses explicit PHP filenames. Every generated alias URL uses `short_link_url($alias)`, which returns `link.php?id=` with an encoded alias and the application base path. Default routes require no URL rewriting; pretty aliases are optional compatibility only.

## Forbidden Patterns

- Do not restore anonymous link creation or wildcard `Access-Control-Allow-Origin`.
- Do not store OAuth2 passwords/tokens or database passwords in the repository/database source templates.
- Do not use GET for destructive admin actions.
- Do not trust client-provided IP fields; use the server request metadata.
- Do not bind a historical alias to the domain that was used when it was created.

## Validation & Error Matrix

| Surface | Required check | Failure |
|---|---|---|
| Create/API | approved user/admin + CSRF + URL/alias limits | 403/401/419 or JSON error |
| Admin mutation | admin session + POST + CSRF | reject without mutation |
| OAuth2 callback | state + PKCE + userinfo `sub` | generic login error |
| Contact POST | CSRF + email and length limits | render validation error |
| Installer | connection and every SQL statement | numeric non-zero error |
| Config access | direct request to `config.php` | HTTP 403, no values rendered |
| Alias output | encode query value and include deployment directory | direct PHP URL, no rewrite dependency |

## Good/Base/Bad Cases

- Good: a pending OAuth2 user can view their status but cannot call `create.php` or `api.php` successfully.
- Base: a legacy administrator can only use password fallback when `ALLOW_LEGACY_ADMIN_LOGIN=1` is explicitly set.
- Bad: checking only `$_SESSION['valid_user']`, trusting a hidden IP input, or treating every installer response as success.

## Tests Required

- Run PHP syntax checks for every changed PHP file in an environment with PHP CLI.
- Run `php -n tests/portable-deployment.php`: verify file configuration, empty/zero environment overrides, raw database passwords, OAuth2 allowlists/callbacks, subdirectory URLs and password form targets.
- Exercise OAuth2 admin and user callbacks with valid, expired, mismatched-state, and missing-`sub` responses.
- Exercise create/API as anonymous, pending, approved, suspended, and admin sessions.
- Verify one historical alias through `link.php?id=alias` on each intended host/vhost and subdirectory; test optional Apache/Nginx rewrite compatibility separately when deployed.

## Wrong vs Correct

Wrong: `if ($_SESSION['valid_user']) { UPDATE ... }` as the only admin authorization check.

Correct: gate the page with `admin_session_active()`, then require POST and `csrf_valid()` for every mutation.
