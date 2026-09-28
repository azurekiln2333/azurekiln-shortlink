# Implementation

- [x] Add configuration and shared loader; connect database, OAuth2 and platform readers.
- [x] Add direct alias URL helper; update creation, lists, statistics and password forms.
- [x] Change navigation and make Apache rewrites optional.
- [x] Update installation docs and maintained configuration/URL specs.
- [x] Run PHP syntax and focused configuration, OAuth2, navigation and URL checks.
- [x] Audit remaining configuration and alias URL consumers.

## Validation

Exercise file configuration and environment precedence, raw passwords, root/admin/subdirectory URL contexts, encoded aliases and both OAuth2 callbacks. Use a PHP server without a router and a test-only database fixture for direct page/redirect/password workflows when a live database is unavailable.

## Results

- PHP 8.4.26 syntax checks passed for all 49 application/test PHP files.
- `php -n tests/portable-deployment.php` passed 41 configuration, OAuth2 and URL checks, including raw credentials, empty/zero overrides, both callbacks, administrator allowlists, nested directories and password form targets.
- A PHP development server with no router passed 11 HTTP checks using test-only database responses: six direct pages, plain redirect, password form, successful password submit, configuration access returning empty HTTP 403, and anonymous API creation rejected with HTTP 401.
- Static audit found only one environment reader (`functions/config.php`) and no remaining alias URLs generated through `public_url($alias)` or extensionless public page/form paths. The contact form now submits to `contact.php?op=contact`.
- Live MySQL connections and provider token/userinfo exchanges were not tested because deployment credentials are not supplied. The HTTP fixture and downloaded PHP runtime were temporary verification tools.

## Rollback

No stored values or schemas change. Restore original configuration readers or alias URL consumers independently; existing optional rewrite rules remain available.
