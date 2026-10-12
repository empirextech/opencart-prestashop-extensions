# OpenCart & PrestaShop Extensions

Two original platform modules that share a small PHP core:

1. **OpenCart 3** — city/postcode shipping method plus phone-or-email login identity.
2. **PrestaShop 8** — incremental product/combination XML feed for comparison engines.

This repository is a demonstration kit. It is not a client store, not an OCMOD dump, and not a fork of a commercial SuperCheckout or marketplace connector. The problems it solves — city courier rates, blocked postcodes, phone login, combination flattening, tokenised feeds — come from real platform work, reimplemented as independent modules.

## Problem

OpenCart shipping extensions often hard-code geo-zone SQL and ignore city. PrestaShop comparison feeds either dump the whole catalog or explode combinations in memory. Both platforms need modules that stay testable outside the store bootstrap.

## Architecture

```
src/QuoteCalculator.php      ─┐
src/PhoneIdentity.php        ─┼─ PHPUnit (no store required)
src/CatalogFeed.php          ─┘
        │
        ├─ opencart3/upload/...  (OC3 shipping + phone identity)
        └─ prestashop8/empirexcatalogexport/  (PS8 front controller feed)
```

## OpenCart 3 — Rule Shipping

Install by copying `opencart3/upload/` over the store root, then:

Extensions → Shipping → EMPIREX Rule Shipping → Edit → Enable.

| Setting | Example |
| --- | --- |
| City rates | `Cluj-Napoca=14.90` |
| Country rates | `RO=19.90` |
| Blocked prefixes | `07,98` |
| Free from | `200` |
| Heavy from / surcharge | `10` / `10` |

`getQuote()` builds an address, then delegates to `QuoteCalculator`. Geo-zone filtering stays in the OpenCart model because that is platform data.

Phone identity lives in `system/library/empirex/phone_identity.php`. It does not skip password verification. It classifies the login field as email vs national number so a store can look up `telephone` digits.

## PrestaShop 8 — Catalog Export

Copy `prestashop8/empirexcatalogexport/` to `modules/empirexcatalogexport/`, install in the back office.

The module generates a secret feed token on install. The URL:

```
https://shop.example/module/empirexcatalogexport/feed?token=...
```

- `hash_equals` token comparison
- Combinations flattened to one XML node per offer
- `date_upd` incremental filter
- `XMLWriter` in memory (swap `openUri` in production for very large catalogs)

## Tests

```bash
composer install
composer test
```

No OpenCart or PrestaShop bootstrap is required.

## Security

- OpenCart admin controller checks `modify` permission.
- PrestaShop feed is 403 without the module token. Rotate the token if it leaks.
- SQL uses integer casts for IDs. Do not concatenate request input into those queries.
- The feed URL is a secret; treat it like an API key.

## Performance

- Shipping quote is O(rules) in PHP, no extra SQL per city.
- Feed skips unchanged products when `EMPIREX_FEED_SINCE` is set.
- For 100k+ combinations, replace `XMLWriter::openMemory()` with `openUri` and stream.

## License

MIT for this original demonstration code. OpenCart and PrestaShop remain under their own licenses.
