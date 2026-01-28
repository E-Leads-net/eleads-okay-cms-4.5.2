<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedFormatter;
use Okay\Helpers\ProductsHelper;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Core\Languages;

class ELeadsFeedDataBuilder
{
    public static function buildProductIds(array $products): array
    {
        return array_map(static function ($product) {
            return (int) $product->id;
        }, $products);
    }

    public static function buildProductCategories(CategoriesEntity $categoriesEntity, array $productIds): array
    {
        $productCategories = [];
        if (!empty($productIds)) {
            $productCategoryRows = $categoriesEntity->getProductCategories($productIds);
            foreach ($productCategoryRows as $row) {
                $productCategories[$row->product_id][] = (int) $row->category_id;
            }
        }
        return $productCategories;
    }

    public static function buildVariantsByProduct(VariantsEntity $variantsEntity, array $productIds): array
    {
        $variantsByProduct = [];
        if (!empty($productIds)) {
            $variants = $variantsEntity->find(['product_id' => $productIds]);
            foreach ($variants as $variant) {
                $variantsByProduct[$variant->product_id][] = $variant;
            }
        }
        return $variantsByProduct;
    }

    public static function buildBrandsById(BrandsEntity $brandsEntity): array
    {
        $brands = $brandsEntity->find(['limit' => $brandsEntity->count()]);
        $brandsById = [];
        foreach ($brands as $brand) {
            $brandsById[$brand->id] = $brand->name;
        }
        return $brandsById;
    }

    public static function buildImagesByProduct(ImagesEntity $imagesEntity, array $productIds): array
    {
        $imagesByProduct = [];
        if (!empty($productIds)) {
            $images = $imagesEntity->find(['product_id' => $productIds]);
            foreach ($images as $image) {
                $imagesByProduct[$image->product_id][] = $image;
            }
        }
        return $imagesByProduct;
    }

    public static function buildProducts(ProductsEntity $productsEntity): array
    {
        return $productsEntity->find([
            'visible' => 1,
            'limit' => $productsEntity->count(['visible' => 1]),
        ]);
    }

    public static function buildProductFeatures(ProductsHelper $productsHelper, array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $productsById = [];
        foreach ($products as $product) {
            $productsById[(int) $product->id] = $product;
        }

        $productsById = $productsHelper->attachFeatures($productsById);

        $featureMap = [];
        foreach ($productsById as $productId => $product) {
            if (empty($product->features)) {
                continue;
            }
            foreach ($product->features as $feature) {
                if (empty($feature->name) || empty($feature->values)) {
                    continue;
                }
                foreach ($feature->values as $featureValue) {
                    if (!isset($featureValue->value)) {
                        continue;
                    }
                    $featureMap[$productId][] = [
                        'feature_id' => (int) $feature->id,
                        'feature_name' => $feature->name,
                        'value' => $featureValue->value,
                        'value_id' => (int) $featureValue->id,
                    ];
                }
            }
        }

        return $featureMap;
    }

    public static function buildExportCategories(
        CategoriesEntity $categoriesEntity,
        array $selectedCategoryIds
    ): array {
        $allCategories = $categoriesEntity->find();
        $allCategoryIds = [];
        $categoriesById = [];
        foreach ($allCategories as $category) {
            $allCategoryIds[] = (int) $category->id;
            $categoriesById[$category->id] = $category;
        }
        if (empty($selectedCategoryIds)) {
            return [[], []];
        }
        $selectedCategoryIds = array_values(array_unique(array_map('intval', $selectedCategoryIds)));

        $categoriesToExport = ELeadsFeedFormatter::collectCategoryTree($selectedCategoryIds, $categoriesById);
        $exportCategories = [];
        foreach ($allCategories as $category) {
            if (isset($categoriesToExport[$category->id])) {
                $category->position = isset($category->position) ? (int) $category->position : 0;
                $exportCategories[] = $category;
            }
        }

        return [$exportCategories, array_flip($selectedCategoryIds)];
    }

    public static function buildFeedSettings(
        Settings $settings,
        CurrenciesEntity $currenciesEntity
    ): array {
        $shopName = $settings->get('eleads__yml_feed__shop_name') ?: $settings->get('site_name');
        $email = $settings->get('eleads__yml_feed__email') ?: ($settings->get('order_email') ?: $settings->get('notify_from_email'));
        $shopUrl = $settings->get('eleads__yml_feed__shop_url') ?: Request::getRootUrl();
        $currencyCode = $settings->get('eleads__yml_feed__currency');
        $pictureLimit = $settings->get('eleads__yml_feed__picture_limit');
        $groupedProducts = $settings->get('eleads__yml_feed__grouped');
        if ($pictureLimit === null || $pictureLimit === '') {
            $pictureLimit = 5;
        }

        $mainCurrency = $currenciesEntity->getMainCurrency();
        if (empty($currencyCode) && !empty($mainCurrency)) {
            $currencyCode = $mainCurrency->code;
        }

        return [
            'shop_name' => $shopName,
            'email' => $email,
            'shop_url' => $shopUrl,
            'currency_code' => $currencyCode,
            'picture_limit' => (int) $pictureLimit,
            'grouped_products' => $groupedProducts === null || $groupedProducts === '' ? true : (bool) $groupedProducts,
        ];
    }

    public static function resolveLanguage(
        LanguagesEntity $languagesEntity,
        Languages $languages,
        string $lang
    ): void {
        $lookupLabel = $lang === 'uk' ? 'ua' : $lang;
        $currentLanguage = $languagesEntity->findOne(['label' => $lookupLabel]);
        if (!empty($currentLanguage->id)) {
            $languages->setLangId((int) $currentLanguage->id);
        }
    }
}
