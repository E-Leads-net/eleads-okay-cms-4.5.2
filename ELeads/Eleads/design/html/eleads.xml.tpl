<?xml version="1.0" encoding="UTF-8"?>
<yml_catalog date="{$feed_date|escape}">
<shop>
<shopName>{$shop_name|escape}</shopName>
<email>{$email|escape}</email>
<url>{$shop_url|escape}</url>
<language>{$language|escape}</language>
<categories>
{foreach $categories as $category}
<category id="{$category->id}"{if $category->parent_id} parentId="{$category->parent_id}"{/if} position="{$category->position}" url="{url_generator route='category' url=$category->url absolute=1}">{$category->name|escape}</category>
{/foreach}
</categories>
<offers>
{foreach $offers as $offer}
<offer id="{$offer.id}"{if $offer.group_id} group_id="{$offer.group_id}"{/if} available="{if $offer.available}true{else}false{/if}">
<url>{url_generator route='product' url=$offer.url absolute=1}</url>
<name>{$offer.name|escape}</name>
<price>{$offer.price}</price>
<old_price>{if $offer.old_price !== null}{$offer.old_price}{/if}</old_price>
<currency>{$offer.currency|escape}</currency>
<categoryId>{$offer.category_id}</categoryId>
<quantity>{$offer.quantity}</quantity>
<stock_status>{$offer.stock_status|escape}</stock_status>
{foreach $offer.pictures as $picture}
<picture>{$picture|escape}</picture>
{/foreach}
<vendor>{$offer.vendor|escape}</vendor>
<sku>{$offer.sku|escape}</sku>
<label/>
<order>{$offer.order}</order>
<description>{$offer.description|escape}</description>
<short_description>{$offer.short_description|escape}</short_description>
{foreach $offer.params as $param}
<param{if $param.filter} filter="true"{/if} name="{$param.name|escape}">{$param.value|escape}</param>
{/foreach}
</offer>
{/foreach}
</offers>
</shop>
</yml_catalog>
