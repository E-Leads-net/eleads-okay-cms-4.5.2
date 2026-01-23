# E-Leads YML Feed — модуль выгрузки

Модуль добавляет XML‑фид товаров в формате, соответствующем примеру `examle.xml`.

## Установка

1. Скопируйте папку `ELeads` в каталог:

   `app/Okay/Modules/`

   Итоговый путь должен быть:

   `app/Okay/Modules/ELeads/YmlFeed`

2. В админке: **Модули → Установить / Активировать**.

## URL фида

Фид доступен по адресу:

```
/eleads-yml/{lang}.xml
```

Примеры:
- `/eleads-yml/ua.xml`
- `/eleads-yml/ru.xml`
- `/eleads-yml/en.xml`

Если задан ключ доступа, используйте:

```
/eleads-yml/ua.xml?key=ВАШ_КЛЮЧ
```

## Настройки в админке

- **Категории и подкатегории** — выбираете, какие категории попадут в фид. Если ничего не выбрано — выгружаются все.
- **Атрибуты для фильтрации** — выбранные атрибуты получают `filter="true"` в `<param>`.
- **Опции для фильтрации** — выбранные значения атрибутов тоже получают `filter="true"` в `<param>`.
- **Ключ доступа** — защищает фид, требуется параметр `key` в URL.
- **Название магазина / email / URL магазина / Валюта** — подставляются в `<shop>`.
- **Лимит изображений (picture)** — ограничивает количество `<picture>` в каждом `<offer>`.
- **Источник short_description** — откуда брать `<short_description>`.

## Структура фида

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

## Примечания

- Модуль включается/выключается через список модулей.
- Фид формируется из видимых товаров и всех их вариантов.
- Для категорий и товаров используются абсолютные URL.

## Файлы модуля

- Контроллер фида: `Controllers/ELeadsYmlFeedController.php`
- Шаблон XML: `design/html/eleads_yml_feed.xml.tpl`
- Админка: `Backend/Controllers/ELeadsYmlFeedAdmin.php`
- Шаблон админки: `Backend/design/html/e_leads_yml_feed.tpl`

