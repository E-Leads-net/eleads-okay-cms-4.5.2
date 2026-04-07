# E-Leads Module for OkayCMS

## Version
- Module version: `1.0.26`

## Overview
The module integrates OkayCMS with E-Leads and provides four main feature groups:

1. Product feed export in YML/XML format.
2. Product synchronization with the E-Leads API on create, update, and delete.
3. SEO Pages integration with sitemap generation and dynamic SEO landing pages.
4. Module self-update and storefront widget loader tag injection.

The module is designed to work with multilingual stores and contains language mapping logic for cases where the store uses `ua` while the E-Leads API expects `uk`.

## Main Features

### 1. Feed export
- Generates a public product feed per language.
- Supports category filtering.
- Supports feature and option filters.
- Supports grouped and non-grouped offer export.
- Supports image limits and configurable image size source.
- Supports configurable short description source.
- Supports optional feed access key.

### 2. Incremental feed generation
- Feed files are no longer generated synchronously on public URL access.
- Feed generation is started explicitly through protected module API endpoints.
- Feed generation is processed in batches.
- Current batch size: `300`.
- Generated feed files are stored and then served as static generated artifacts.

### 3. Product synchronization
- Sends product data to E-Leads on create and update.
- Sends delete requests to E-Leads on product removal.
- Uses the current admin language as source language.
- Maps store `ua` to API `uk` where required.

### 4. SEO Pages
- Creates and maintains `/e-search/sitemap.xml`.
- Renders dynamic pages on `/e-search/{slug}` using E-Leads SEO API data.
- Supports language-aware sitemap URLs.
- Supports canonical and alternate links from API data.
- Provides protected sitemap sync and language discovery endpoints.

### 5. Widget loader tag injection
- On module activation, requests widget loader script from E-Leads API.
- Injects the returned tag into the current storefront theme.
- Removes the injected block on module deactivation.

## Compatibility
- OkayCMS: current implementation targets modern core and contains compatibility handling for older cores where possible.
- PHP: 8.1+

## Installation
1. Copy the module directory to:
   ```text
   app/Okay/Modules/ELeads/Eleads
   ```
2. Install and enable the module in OkayCMS admin.
3. Open the module settings page.
4. Enter a valid E-Leads API key.

Without a valid API key:
- settings are restricted;
- protected module endpoints reject requests;
- feed status generation controls are not usable;
- SEO tab can be hidden if E-Leads token status says `seo_status = false`.

## Admin Interface

### Export Settings
The export tab contains:
- feed generation controls per language;
- current feed status per language;
- buttons:
  - generate / regenerate feed;
  - copy URL;
  - download;
- synchronization toggle;
- category selection;
- feature filter selection;
- option filter selection;
- grouped export toggle;
- shop metadata fields;
- image and description source settings.

### Feed status in admin
For each language the module shows:
- current generation state;
- generate or regenerate button;
- download button.

Possible states:
- `Feed not generated`
- `Generation in progress`
- `Feed is ready`
- `Generation failed`

The download button is active only when the feed status is `ready`.

### API Key tab
- Saves the E-Leads project API key.
- Validates API status against E-Leads.

### SEO tab
- Shows SEO Pages activation switch only if API token status returns `seo_status = true`.
- Shows sitemap URL and actions related to SEO sitemap behavior.
- If `seo_status = false`, SEO Pages are forced off and sitemap is removed.

### Update tab
- Shows installed version.
- Checks latest version from GitHub.
- Can update the module and persist the updated module version in the database.

## Feed URLs
Public feed URL format:

```text
/eleads-yml/{lang}.xml
```

Examples:
- `/eleads-yml/ru.xml`
- `/eleads-yml/en.xml`
- `/eleads-yml/uk.xml`

Important language rule:
- if the store language label is `ua`, the public feed URL still uses `uk.xml`.

If a feed access key is configured:

```text
/eleads-yml/uk.xml?key=YOUR_KEY
```

## Feed Generation Logic

### Previous model
Previously, the feed was generated directly during a request to the public feed URL.

### Current model
Current logic is incremental:
- generation is started via protected API;
- processing continues in batches;
- final XML is written to disk;
- public feed URL only serves the generated file.

### Feed generation flow
1. Client calls:
   - `POST /eleads-yml/api/generate?lang=...`
2. Module creates or resets a generation job.
3. Module creates a temporary XML file.
4. Module writes:
   - XML header;
   - shop metadata;
   - categories;
   - opening offers node.
5. Client polls:
   - `GET /eleads-yml/api/status?lang=...`
6. Each status call processes one next batch.
7. When processing is complete:
   - module writes closing XML tags;
   - publishes final XML file;
   - marks job status as `ready`.
8. Public URL `/eleads-yml/{lang}.xml` serves the generated file.

### Batch size
- Default batch size: `300`.

### Storage behavior
The module stores:
- generated feed file;
- temporary generation file;
- job state metadata.

### Public feed route behavior
`GET /eleads-yml/{lang}.xml`:
- checks feed access key if configured;
- serves ready file if it exists;
- returns `404` if feed has not been generated yet;
- does not generate feed on the fly.

## Feed Data Rules

### Categories
The feed exports only selected categories and required parent categories for valid tree structure.

Category nodes can contain:
- `id`
- `parentId`
- `position`
- `url`
- text value = category name

### Offers
Offers are built from visible products and their variants.

Grouped mode:
- one offer per product;
- variant data is aggregated;
- options can be combined into params.

Ungrouped mode:
- one offer per variant;
- `group_id` points to the parent product;
- variant name is appended to offer name.

### Attributes and options
- selected features can be exported with `filter="true"`;
- selected options can be exported with `filter="true"`;
- multivalue params are normalized into a single value joined by `; `.

### Images
- feed can use original or configured resized images;
- max number of images is configurable.

### Short description
Configurable source:
- annotation;
- meta description;
- full description.

## Feed API Endpoints

### 1. Start feed generation

```http
POST /eleads-yml/api/generate?lang={lang}
Authorization: Bearer <API_KEY>
Accept: application/json
```

Starts or restarts feed generation for the requested language.

Behavior:
- validates module API key;
- normalizes language;
- resets previous unfinished job for that language;
- creates new generation job;
- prepares temp XML;
- returns accepted state.

Success response example:

```json
{
  "status": "accepted",
  "lang": "ru",
  "job": {
    "status": "running",
    "lang": "ru",
    "processed": 0,
    "batch_size": 300,
    "last_product_id": 0,
    "updated_at": "2026-04-07 20:00:00",
    "finished_at": "",
    "size": 0,
    "error": ""
  }
}
```

Possible errors:
- `401` → `{"error":"unauthorized"}`
- `401` → `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`
- `500` → `{"error":"generation_start_failed"}`

### 2. Get feed generation status

```http
GET /eleads-yml/api/status?lang={lang}
Authorization: Bearer <API_KEY>
Accept: application/json
```

Returns current job state and advances generation by one batch while the job is running.

Success response example:

```json
{
  "status": "running",
  "lang": "ru",
  "processed": 300,
  "batch_size": 300,
  "last_product_id": 15420,
  "updated_at": "2026-04-07 20:01:12",
  "finished_at": "",
  "size": 0,
  "error": ""
}
```

Ready response example:

```json
{
  "status": "ready",
  "lang": "ru",
  "processed": 1842,
  "batch_size": 300,
  "last_product_id": 1888,
  "updated_at": "2026-04-07 20:03:40",
  "finished_at": "2026-04-07 20:03:40",
  "size": 2543210,
  "error": ""
}
```

Possible errors:
- `401` → `{"error":"unauthorized"}`
- `401` → `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`

### 3. Get all feed URLs

```http
GET /eleads-yml/api/feeds
Authorization: Bearer <API_KEY>
Accept: application/json
```

Returns all available public feed URLs by store language.

Response example:

```json
{
  "status": "ok",
  "count": 3,
  "items": {
    "ru": "https://example.com/eleads-yml/ru.xml?key=123",
    "ua": "https://example.com/eleads-yml/uk.xml?key=123",
    "en": "https://example.com/eleads-yml/en.xml?key=123"
  }
}
```

Rules:
- response keys are store language labels;
- `ua` language is mapped to `uk.xml` in URL;
- if feed access key exists, it is appended to each URL.

Possible errors:
- `401` → `{"error":"unauthorized"}`
- `401` → `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`

## Product Synchronization

When synchronization is enabled, the module sends product changes to E-Leads.

Supported actions:
- create
- update
- delete

General behavior:
- language is taken from current admin language;
- if current admin language is `ua`, API payload uses `uk`;
- for create/update the payload includes source, product, category, attributes, images, and related fields;
- for delete the request is sent with required language parameter.

Synchronization is performed through E-Leads dashboard API routes configured in the module route helper classes.

## Widget Loader Tag Injection

On module activation:
- the module requests widget code from:
  - `https://stage-api.e-leads.net/v1/widgets-loader-tag`
- if request succeeds, returned tag is injected into the active storefront theme footer area.

On module deactivation:
- the previously injected block is removed.

If request fails:
- nothing is inserted.

## SEO Pages

### Public SEO routes
- `GET /e-search/sitemap.xml`
- `GET /e-search/{slug}`

### SEO activation behavior
If SEO Pages are enabled:
- sitemap file is created;
- sitemap entries are built from E-Leads SEO slug API;
- dynamic SEO landing pages become available.

If disabled:
- sitemap is removed.

### SEO page rendering
When a user opens `/e-search/{slug}`:
1. module resolves current store language;
2. module requests SEO page data from E-Leads API;
3. module renders page using product listing/search template logic;
4. module applies:
   - products;
   - filters;
   - SEO metadata;
   - canonical URL;
   - alternate links.

### SEO sitemap sync endpoint

```http
POST /e-search/api/sitemap-sync
Authorization: Bearer <API_KEY>
Content-Type: application/json
Accept: application/json
```

Optional query parameter:

```text
?lang={language_label}
```

Supported payloads:

```json
{"action":"create","slug":"telefon"}
{"action":"delete","slug":"telefon"}
{"action":"update","slug":"telefon-old","new_slug":"telefon-new"}
```

Language-aware payloads are also supported:

```json
{"action":"create","slug":"telefon","lang":"uk"}
{"action":"delete","slug":"telefon","language":"ru"}
{"action":"update","slug":"old","new_slug":"new","lang":"uk","new_lang":"ru"}
```

Rules:
- `action` is required;
- allowed actions: `create`, `update`, `delete`;
- `slug` is required;
- `new_slug` is required for `update`;
- language may be passed as `lang` or `language`;
- target language for update may be passed as `new_lang` or `new_language`;
- query `?lang=` has priority over payload language.

Success response:

```json
{
  "status": "ok",
  "url": "https://example.com/ua/e-search/telefon"
}
```

Possible errors:
- `401` → `{"error":"unauthorized"}`
- `401` → `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`
- `422` → `{"error":"invalid_payload"}`
- `422` → `{"error":"invalid_action"}`
- `500` → `{"error":"sitemap_update_failed"}`

### SEO languages endpoint

```http
GET /e-search/api/languages
Authorization: Bearer <API_KEY>
Accept: application/json
```

Response example:

```json
{
  "status": "ok",
  "count": 3,
  "items": [
    {
      "id": 1,
      "label": "ua",
      "code": "ua",
      "href_lang": "uk",
      "enabled": true
    },
    {
      "id": 2,
      "label": "ru",
      "code": "ru",
      "href_lang": "ru",
      "enabled": true
    }
  ]
}
```

Purpose:
- allows external systems to discover actual store languages;
- exposes API-facing hreflang mapping.

Possible errors:
- `401` → `{"error":"unauthorized"}`
- `401` → `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`

## Authentication Model

The module uses two different security mechanisms:

### 1. Module API key
Used for protected module endpoints:
- `/eleads-yml/api/generate`
- `/eleads-yml/api/status`
- `/eleads-yml/api/feeds`
- `/e-search/api/sitemap-sync`
- `/e-search/api/languages`

Format:

```http
Authorization: Bearer <API_KEY>
```

### 2. Feed access key
Used only for public feed download route:
- `/eleads-yml/{lang}.xml?key=...`

This key is independent from the module API key.

## Version Update Behavior
- Local module version is read from `Init/module.json`.
- Update checker compares local version with GitHub release tags.
- After module update, installed module version in database is also updated.

## Notes
- `ua` to `uk` mapping is implemented in both feed and SEO logic where required by API or public URL format.
- Feed generation status does not automatically mean data freshness after catalog changes; it means the last generation job completed successfully and the generated file exists.
- Public feed URL does not regenerate data. If product data changes, feed regeneration must be started again.
