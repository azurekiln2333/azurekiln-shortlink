# Centralize deployment config and remove rewrite dependency

## Goal

Use config.php for database, OAuth2 and runtime options; generate direct PHP page and short-link URLs for deployment without rewrite rules.

## Requirements

- Centralize database, debug, OAuth2, login, API origin and analytics options in root `config.php`, with optional existing environment overrides.
- Use explicit PHP paths for public pages and all generated short links, including password forms.
- Preserve historical aliases, database-managed site settings and OAuth2 state/PKCE behavior.
- Update installation instructions for deployment without rewrite rules.

## Acceptance Criteria

- [x] Only the shared configuration loader reads environment variables.
- [x] File configuration works without environment variables; passwords retain whitespace and environment overrides retain empty strings and zero values.
- [x] Navigation, creation/API results, QR input, user/admin lists, statistics and password forms use actual PHP endpoints.
- [x] Default URLs require no Apache rewrite module or Nginx rewrite rules.
- [x] Changed PHP files pass syntax and focused configuration/URL behavior checks.

## Compatibility

- Previously distributed `/alias` URLs still need their existing rewrite rule. Stored aliases also work through `link.php?id=alias` without rewriting.
- Database schema, privileges, OAuth2 protocol and navigation layout are outside this change.
