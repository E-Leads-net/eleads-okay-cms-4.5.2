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
            if (empty($productVariants)) {
                continue;
            }

            $productImages = $imagesByProduct[$product->id] ?? [];
            $pictures = ELeadsFeedFormatter::buildImageUrls($config, $productImages, $pictureLimit);

            $productFeatures = $featureMap[$product->id] ?? [];

            $variantCount = count($productVariants);
            foreach ($productVariants as $variant) {
                $available = ($variant->stock > 0);
                $quantity = $variant->stock > 0 ? (int) $variant->stock : 0;
                if ($variant->stock === null) {
                    $available = true;
                    $quantity = 1;
                }

                $price = $money->convert($variant->price, $variant->currency_id, false);
                $oldPrice = null;
                if (!empty($variant->compare_price) && $variant->compare_price > 0) {
                    $oldPrice = $money->convert($variant->compare_price, $variant->currency_id, false);
                }

                $offerName = $product->name;
                if (!empty($variant->name)) {
                    $offerName .= ' (' . $variant->name . ')';
                }

                $offers[] = [
                    'id' => $variant->id,
                    'group_id' => $variantCount > 1 ? $product->id : null,
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
                    'sku' => $variant->sku,
                    'label' => '',
                    'order' => $product->position ?? 0,
                    'description' => $product->description,
                    'short_description' => ELeadsFeedFormatter::resolveShortDescription($product, $shortDescriptionSource),
                    'params' => ELeadsFeedFormatter::prepareParams(
                        $productFeatures,
                        $selectedFeatureSet,
                        $selectedFeatureValueSet
                    ),
                ];
            }
        }

        return $offers;
    }
}
