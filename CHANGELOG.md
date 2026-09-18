# v0.2.0
## 09/18/2026

1. [](#improved)
    * Declared Grav 2 compatibility (`compatibility: grav: ['1.7', '2.0']` in `blueprints.yaml`).
    * Verified against Grav 2.1.6 on PHP 8.3: plugin lifecycle (`onPluginsInitialized`/`onTwigInitialized`), Twig 3 function registration, and cache/log integration all use stable core APIs unchanged since Grav 1.7. No functional changes.

# v0.1.0
## 09/15/2026

1. [](#new)
    * Initial release.
    * Twig functions `unirate_rate`, `unirate_convert`, `unirate_currencies`, `unirate_vat`.
    * Server-side caching via Grav's cache; graceful degradation on API errors.
    * Zero third-party runtime dependencies.
