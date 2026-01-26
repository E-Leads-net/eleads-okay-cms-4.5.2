<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Config;
use Okay\Core\Money;

class ELeadsOfferBuilder
{
    public static function buildOffers(
        array $products,
        array $productCategories,
        array $variantsByProduct,
        array $imagesByProduct,
        array $brandsById,
        array $featureMap,
        array $selectedCategorySet,
        array $selectedFeatureSet,
        array $selectedFeatureValueSet,
        string $currencyCode,
        int $pictureLimit,
        string $lang,
        string $shortDescriptionSource,
        Money $money,
        Config $config
    ): array {
        $offers = [];

        foreach ($products as $product) {
            $productCategoryIds = $productCategories[$product->id] ?? [];
            if (empty($productCategoryIds) && !empty($product->main_category_id)) {
                $productCategoryIds = [(int) $product->main_category_id];
            }

            $hasSelectedCategory = false;
            foreach ($productCategoryIds as $categoryId) {
                if (isset($selectedCategorySet[(int) $categoryId])) {
                    $hasSelectedCategory = true;
                    break;
                }
            }
            if (!$hasSelectedCategory) {
                continue;
            }

            $categoryId = $product->main_category_id;
            if (empty($categoryId) || !isset($selectedCategorySet[(int) $categoryId])) {
                $categoryId = reset($productCategoryIds);
            }

            $productVariants = $variantsByProduct[$product->id] ?? [];
            if (!empty($productVariants)) {
                $productId = (int) $product->id;
                $productVariants = array_values(array_filter($productVariants, static function ($variant) use ($productId) {
                    return isset($variant->product_id) && (int) $variant->product_id === $productId;
                }));
            }
            if (empty($productVariants)) {
                continue;
            }

            $productImages = $imagesByProduct[$product->id] ?? [];
            $pictures = ELeadsFeedFormatter::buildImageUrls($config, $productImages, $pictureLimit);

            $productFeatures = $featureMap[$product->id] ?? [];
            $firstVariant = reset($productVariants);
            if ($firstVariant === false) {
                continue;
            }

            $quantity = 0;
            $hasUnlimited = false;
            foreach ($productVariants as $variant) {
                if ($variant->stock === null) {
                    $hasUnlimited = true;
                    break;
                }
                if ($variant->stock > 0) {
                    $quantity += (int) $variant->stock;
                }
            }

            $available = $hasUnlimited || $quantity > 0;
            if ($hasUnlimited) {
                $quantity = 1;
            }

            $price = $money->convert($firstVariant->price, $firstVariant->currency_id, false);
            $oldPrice = null;
            if (!empty($firstVariant->compare_price) && $firstVariant->compare_price > 0) {
                $oldPrice = $money->convert($firstVariant->compare_price, $firstVariant->currency_id, false);
            }

            $offerName = $product->name;
            $optionValues = [];
            foreach ($productVariants as $variant) {
                if (!empty($variant->name)) {
                    $optionValues[] = (string) $variant->name;
                }
            }
            $optionValues = array_values(array_unique($optionValues));

            $params = ELeadsFeedFormatter::prepareParams(
                $productFeatures,
                $selectedFeatureSet,
                $selectedFeatureValueSet
            );
            if (!empty($optionValues)) {
                $params[] = [
                    'name' => 'Опции',
                    'value' => implode(' | ', $optionValues),
                    'filter' => false,
                ];
            }

            $offers[] = [
                'id' => $product->id,
                'group_id' => null,
                'available' => $available,
                'url' => $product->url,
                'name' => $offerName,
                'price' => $price,
                'old_price' => $oldPrice,
                'currency' => $currencyCode,
                'category_id' => $categoryId,
                'quantity' => $quantity,
                'stock_status' => ELeadsFeedFormatter::formatStockStatus($available, $lang),
                'pictures' => $pictures,
                'vendor' => !empty($brandsById[$product->brand_id]) ? $brandsById[$product->brand_id] : '',
                'sku' => $firstVariant->sku,
                'label' => '',
                'order' => $product->position ?? 0,
                'description' => $product->description,
                'short_description' => ELeadsFeedFormatter::resolveShortDescription($product, $shortDescriptionSource),
                'params' => $params,
            ];
        }

        return $offers;
    }
}
