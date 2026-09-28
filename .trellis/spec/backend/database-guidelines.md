# Database Guidelines

## Scope / Trigger

This project uses PHP with MySQLi and the existing `links` schema. Authentication adds the `shortny_users` table, but historical link rows and aliases must remain unchanged.

## Signatures

- `create_short_link_record($db, $long_url, $custom_alias = '', $password = '')` writes one row to `links`.
- `ensure_user_schema($db)` creates `shortny_users` idempotently for older deployments.
- `install/shortny.sql` initializes a new database; `install/shortny-upgrade.sql` upgrades an existing database.

## Contracts

`shortny_users.provider + subject` is unique. `subject` is the OAuth2 `sub` value and is the local identity key; the OAuth2 service remains the source of authentication credentials.

Runtime database credentials are configured in root `config.php` through `SHORTNY_DB_HOST`, `SHORTNY_DB_USER`, `SHORTNY_DB_PASS`, and `SHORTNY_DB_NAME`. `app_config()` in `functions/config.php` also accepts existing environment overrides, including empty strings. Credentials are returned without trimming. The installer uses submitted credentials only for migrations and never writes them into source files; use matching values in `config.php`.

## Validation & Error Matrix

| Condition | Required behavior |
|---|---|
| Existing alias | Return a creation error; do not overwrite the row |
| URL longer than 255 bytes or not HTTP(S) | Reject before INSERT |
| OAuth2 response without `sub` | Reject identity synchronization |
| Missing database configuration | Fail without exposing SQL or credentials |
| Installer SQL/connection failure | Return a non-zero installer error code |

## Good/Base/Bad Cases

- Good: an existing database is upgraded and all existing `links` rows remain readable through every host.
- Base: an old deployment has no `shortny_users`; runtime schema creation or the upgrade SQL creates it.
- Bad: an installer writes a password into `functions/database.php`, or an upgrade resets an existing administrator password.

## Tests Required

- Assert that a pre-existing alias still resolves after upgrade.
- Assert that a first OAuth2 `sub` creates `pending`, while an approved row remains approved on later login.
- Assert that installer success is exactly numeric `0` and SQL failure is non-zero.
- Run the migration against both a fresh schema and an existing legacy schema on a real MySQL instance.

## Wrong vs Correct

Wrong: `file_put_contents('../functions/database.php', $database_with_password)`.

Correct: connect with installer input for the migration only, then configure the same values in root `config.php` or existing environment variables. Never publish a deployment configuration containing real secrets.
