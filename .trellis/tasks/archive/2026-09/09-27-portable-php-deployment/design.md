# Design

Root `config.php` returns an array with existing environment names grouped by purpose and editable string values. `functions/config.php` loads it once and exposes `app_config($name, $default = '')`. Present environment variables take precedence, including empty values. Raw credentials retain whitespace; existing OAuth2/platform wrappers retain trimming behavior.

Add `short_link_url($alias)` beside `public_url()` to generate the current host, application base path and `link.php?id=` with an encoded alias. All alias URL consumers use this helper. Other routes use explicit PHP filenames.

Keep existing `.htaccess` rewrites as optional compatibility for previously shared aliases, guarded by their owning module. Default URLs work without rewrites. Remove root-only custom error paths so subdirectory deployments use normal server error pages. Preserve database settings, historical aliases, authorization and PKCE. The installer still only performs migrations with submitted credentials.
