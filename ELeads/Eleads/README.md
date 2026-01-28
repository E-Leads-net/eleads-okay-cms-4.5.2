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

## Feed Structure (Excerpt)
```
<yml_catalog date="YYYY-MM-DD HH:MM">
  <shop>
    <shopName>...</shopName>
    <email>...</email>
    <url>...</url>
    <language>...</language>
    <categories>
      <category id="..." parentId="..." url="...">...</category>
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

## Synchronization (E‑Leads API)
When synchronization is enabled:
- **Create** product → `POST https://stage-dashboard.e-leads.net/api/ecommerce/items`
- **Update** product → `PUT https://stage-dashboard.e-leads.net/api/ecommerce/items/{external_id}`
- **Delete** product → `DELETE https://stage-dashboard.e-leads.net/api/ecommerce/items/{external_id}`

Payload basics:
- `language` is taken from the current admin language.
- If the admin language label is `ua`, the payload language is sent as `uk`.

Requests use the **API Key** as Bearer token.

## Widget Loader Tag Injection
On module enable:
- The module requests the loader tag from:
  ```
  https://stage-api.e-leads.net/v1/widgets-loader-tag
  ```
- The tag is injected into the current theme `index.tpl` (before `</body>`, or appended if not found).

On module disable:
- The injected block is removed.

If the tag request fails, nothing is inserted.

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
- Sync helpers: `Helpers/Sync*`

## Notes for Marketplace Review
- The module does not modify core files.
- All E‑Leads API endpoints are centralized in `Config/ELeadsApiRoutes.php`.
- Feed is generated on demand via URL (no cron required).
- Sync can be enabled/disabled at any time.
