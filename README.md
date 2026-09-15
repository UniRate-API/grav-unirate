# UniRate Plugin

The **UniRate** plugin for [Grav CMS](https://getgrav.org) exposes live currency
**exchange rates**, **conversion**, the **supported-currency list**, and **VAT rates**
from the [UniRate API](https://unirateapi.com) as Twig functions — with server-side
caching so the API is not hit on every page render. A free API key is all you need.

## Installation

Install via GPM:

```bash
bin/gpm install unirate
```

Or clone this repository into `user/plugins/unirate`.

## Configuration

Copy `user/plugins/unirate.yaml` into your `user/config/plugins/` folder and set your key:

```yaml
enabled: true
api_key: 'YOUR_UNIRATE_API_KEY'
base_url: 'https://api.unirateapi.com'
cache_lifetime: 3600
```

You can also configure it from the Admin panel.

## Usage

In any Twig template:

```twig
{# Current exchange rate #}
1 USD = {{ unirate_rate('USD', 'EUR') }} EUR

{# Convert an amount #}
{{ unirate_convert(100, 'USD', 'GBP') }} GBP

{# List supported currencies #}
{% for code in unirate_currencies() %}{{ code }} {% endfor %}

{# VAT rate for a country #}
{{ unirate_vat('DE').standard_rate }}%
```

| Function | Description |
|---|---|
| `unirate_rate(from, to)` | Current exchange rate between two currencies |
| `unirate_convert(amount, from, to)` | Convert an amount at the current rate |
| `unirate_currencies()` | List of supported currency codes |
| `unirate_vat(country)` | VAT rates for a country (or all if omitted) |

Results are cached for `cache_lifetime` seconds. If the API is unavailable, functions
return `null` and log a warning, so templates degrade gracefully.

## Dependencies

None beyond Grav itself — HTTP uses PHP's bundled cURL extension; there are no
third-party runtime dependencies.

## License

MIT — see [LICENSE](LICENSE).
