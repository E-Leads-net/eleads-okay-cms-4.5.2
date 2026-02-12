# E‑Leads — Module for OkayCMS 4.5.2

## Overview
E‑Leads adds a product export feed (YML/XML) and optional product synchronization with the E‑Leads platform.
The module provides:
- A configurable export feed: categories, attributes, options, shop info, images, descriptions.
- A public feed URL for each language.
- Optional synchronization of product changes (create/update/delete) to E‑Leads via API.
- An API Key gate that locks settings until a valid key is provided.
- Built‑in module update from GitHub.
- Optional widget loader tag injection into the current storefront theme.
- Optional SEO Pages integration (sitemap + dynamic pages).

## Compatibility
- OkayCMS: 4.5.2
- PHP: 8.1+

## Installation
1. Copy the module directory to:
   ```
   app/Okay/Modules/ELeads/Eleads
   ```
2. In admin: **Modules → Install / Enable**.

## Feed URL
The feed is available at:
```
/eleads-yml/{lang}.xml
```
Examples:
- `/eleads-yml/uk.xml`
- `/eleads-yml/ru.xml`
- `/eleads-yml/en.xml`

If an access key is configured:
```
/eleads-yml/uk.xml?key=YOUR_KEY
```

## Admin Tabs
### 1) Export Settings
- **Feed URLs** per language (copy / download).
- **Categories and subcategories**: only selected categories are exported.
  - If no categories are selected, the feed contains no categories and no offers.
- **Attribute filters** (optional): selected attributes are marked with `filter="true"`.
- **Option filters** (optional): selected options are marked with `filter="true"`.
- **Group products**:
  - **Enabled**: one `<offer>` per product, options are aggregated into `<param>` values.
  - **Disabled**: product variants are exported as separate `<offer>` entries and options are not included as `<param>`.
- **Shop name / Email / Shop URL / Currency**: used in `<shop>`.
- **Picture limit**: max number of `<picture>` tags per offer.
- **Short description source**: defines which product field is used for `<short_description>`.
- **Sync toggle**: enables/disables API sync of product changes.

### 2) API Key
- Enter and validate the E‑Leads API Key.
- The module verifies the token on every access to the settings page.
- Without a valid key, settings are locked.

### 3) Update
- Shows local version and latest version from GitHub.
- Updates the module directly from the repository.

### 4) SEO
- **SEO Pages toggle**:
  - When enabled, the module creates `/e-search/sitemap.xml`.
  - When disabled, the sitemap file is removed.
- **Sitemap URL** is shown with a copy button.
- The SEO tab is shown only when the API token status returns `seo_status = true`.
  - If `seo_status = false`, the tab is hidden and SEO Pages are forced OFF (sitemap removed).

## Feed Structure (Excerpt)
```
<yml_catalog date="YYYY-MM-DD HH:MM">
  <shop>
    <shopName>...</shopName>
    <email>...</email>
    <url>...</url>
    <language>...</language>
    <categories>
      <category id="..." parentId="..." position="..." url="...">...</category>
    </categories>
    <offers>
      <offer id="..." group_id="..." available="true|false">
        <url>...</url>
        <name>...</name>
        <price>...</price>
        <old_price>...</old_price>
        <currency>...</currency>
        <categoryId>...</categoryId>
        <quantity>...</quantity>
        <stock_status>...</stock_status>
        <picture>...</picture>
        <vendor>...</vendor>
        <sku>...</sku>
        <label/>
        <order>...</order>
        <description>...</description>
        <short_description>...</short_description>
        <param name="...">...</param>
        <param filter="true" name="...">...</param>
      </offer>
    </offers>
  </shop>
</yml_catalog>
```
## Widget Loader Tag Injection
On module enable:
- The module requests the loader tag from:
  ```
  https://api.e-leads.net/v1/widgets-loader-tag
  ```
- The tag is injected into the current theme `index.tpl` (before `</body>`, or appended if not found).

On module disable:
- The injected block is removed.

If the tag request fails, nothing is inserted.

## SEO Pages
### Module API routes (with `/api`)
- `POST /e-search/api/sitemap-sync`
- `GET /e-search/api/languages`

These are module API endpoints and always include `/api`.
Public SEO page routes are different:
- `/e-search/sitemap.xml`
- `/e-search/{slug}`

### Sitemap
- URL: `/e-search/sitemap.xml`
- Generated when **SEO Pages** is enabled.
- Contains language-aware links:
  - main language: `https://your-site.com/e-search/{slug}`
  - non-main language: `https://your-site.com/{lang}/e-search/{slug}`
- Slug language from API is mapped to store language labels.
  - Example: API `uk` is mapped to store `ua` when the shop uses `ua`.

### SEO Page route
- URL: `/e-search/{slug}`
- The module requests page data from the E‑Leads API using:
  - path param `{slug}`
  - query param `lang` (current store language, `ua` mapped to `uk` for API)
- The page is rendered using the standard product search results template (with filters).
- Canonical is taken from API field `page.url` (fallback: local route URL).
- Alternate links are rendered only for languages returned by API `page.alternate`, plus current page language URL.

### Sitemap sync endpoint (module)
The module exposes a protected endpoint to keep the sitemap in sync with external updates:

```
POST /e-search/api/sitemap-sync
Authorization: Bearer <API_KEY>
Content-Type: application/json
```

Optional query parameter:
```
?lang=<language_label>
```

Payload:
```
{"action":"create","slug":"komp-belyy"}
{"action":"delete","slug":"komp-belyy"}
{"action":"update","slug":"old-slug","new_slug":"new-slug"}
```

Payload with language:
```
{"action":"create","slug":"komp-belyy","lang":"uk"}
{"action":"delete","slug":"komp-belyy","language":"ru"}
{"action":"update","slug":"old-slug","new_slug":"new-slug","lang":"uk","new_lang":"ru"}
```

Rules:
- `action` is required: `create | update | delete`
- `slug` is required for all actions
- `new_slug` is required for `update`
- language can be passed as `lang` or `language`
- for `update`, target language can be passed as `new_lang` or `new_language`
- if `?lang=` is present, it has priority over payload language
- `Authorization` must match the module API key

Success response:
```json
{
  "status": "ok",
  "url": "https://example.com/lang/e-search/telefon"
}
```

Error responses:
- `401` → `{"error":"unauthorized"}` or `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`
- `422` → `{"error":"invalid_payload"}` or `{"error":"invalid_action"}`
- `500` → `{"error":"sitemap_update_failed"}`

### Languages endpoint (module)
Returns enabled/available store languages for external integrations.

```
GET /e-search/api/languages
Authorization: Bearer <API_KEY>
Accept: application/json
```

Success response:
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
    }
  ]
}
```

Error responses:
- `401` → `{"error":"unauthorized"}` or `{"error":"api_key_missing"}`
- `405` → `{"error":"method_not_allowed"}`

## Module Structure
```
ELeads/Eleads/
├─ Backend/                Admin controllers and templates
├─ Config/                 API routes and constants
├─ Controllers/            Frontend feed controller
├─ Extenders/              Entity hooks (products, modules)
├─ Helpers/                Feed generation, sync logic, widgets injection
├─ Init/                   Module bootstrap (routes, permissions, extenders)
├─ design/                 Feed XML template
└─ README.md               This file
```

Key files:
- Feed controller: `Controllers/ELeadsController.php`
- Feed template: `design/html/eleads.xml.tpl`
- Admin UI: `Backend/Controllers/ELeadsAdmin.php`
- Admin templates: `Backend/design/html/partials/*`
- SEO endpoints: `Controllers/SeoSitemapSyncController.php`, `Controllers/SeoLanguagesController.php`
- SEO page render: `Controllers/SeoPagesController.php`, `Helpers/SeoPagesApiHelper.php`, `Helpers/SeoSitemapHelper.php`
- Sync helpers: `Helpers/Sync*`

## Notes for Marketplace Review
- The module does not modify core files.
- All E‑Leads API endpoints are centralized in `Config/ELeadsApiRoutes.php`.
- Feed is generated on demand via URL (no cron required).
- Sync can be enabled/disabled at any time.
