<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\Money;
use Okay\Core\Request;
use Okay\Core\Router;
use Okay\Core\Settings;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\ProductsHelper;

class SyncPayloadBuilder
{
    private Settings $settings;
    private EntityFactory $entityFactory;
    private ProductsHelper $productsHelper;
    private Money $money;
    private Config $config;
    private Router $router;
    private SyncLanguageResolver $languageResolver;

    public function __construct(
        Settings $settings,
        EntityFactory $entityFactory,
        ProductsHelper $productsHelper,
        Money $money,
        Config $config,
        Router $router,
        SyncLanguageResolver $languageResolver
    ) {
        $this->settings = $settings;
        $this->entityFactory = $entityFactory;
        $this->productsHelper = $productsHelper;
        $this->money = $money;
        $this->config = $config;
        $this->router = $router;
        $this->languageResolver = $languageResolver;
    }

    public function buildPayload(int $productId): ?array
    {
        /** @var ProductsEntity $productsEntity */
        $productsEntity = $this->entityFactory->get(ProductsEntity::class);
        /** @var VariantsEntity $variantsEntity */
        $variantsEntity = $this->entityFactory->get(VariantsEntity::class);
        /** @var ImagesEntity $imagesEntity */
        $imagesEntity = $this->entityFactory->get(ImagesEntity::class);
        /** @var CategoriesEntity $categoriesEntity */
        $categoriesEntity = $this->entityFactory->get(CategoriesEntity::class);
        /** @var BrandsEntity $brandsEntity */
        $brandsEntity = $this->entityFactory->get(BrandsEntity::class);

        [$langId, $languageLabel, $payloadLanguage] = $this->languageResolver->resolve();

        $product = $productsEntity->get($productId);
        if (empty($product)) {
            return null;
        }

        $variants = $variantsEntity->find(['product_id' => $productId]);
        if (empty($variants)) {
            return null;
        }

        $images = $imagesEntity->find(['product_id' => $productId]);

        $productsMap = [$productId => $product];
        $productsMap = $this->productsHelper->attachFeatures($productsMap);
        $featureFilters = $this->getSelectedFeatureFilters();

        $attributes = [];
        $attributeFilters = [];
        if (!empty($productsMap[$productId]->features)) {
            foreach ($productsMap[$productId]->features as $feature) {
                if (empty($feature->name) || empty($feature->values)) {
                    continue;
                }
                $values = [];
                $isFilter = false;
                foreach ($feature->values as $featureValue) {
                    if (!isset($featureValue->value)) {
                        continue;
                    }
                    $values[] = $featureValue->value;
                    if (isset($featureFilters['features'][(int) $feature->id]) || isset($featureFilters['values'][(int) $featureValue->id])) {
                        $isFilter = true;
                    }
                }
                if (!empty($values)) {
                    $attributes[$feature->name] = implode('; ', $values);
                    if ($isFilter) {
                        $attributeFilters[] = $feature->name;
                    }
                }
            }
        }

        $categoriesById = [];
        foreach ($categoriesEntity->find() as $category) {
            $categoriesById[$category->id] = $category;
        }

        $category = null;
        if (!empty($product->main_category_id) && isset($categoriesById[$product->main_category_id])) {
            $category = $categoriesById[$product->main_category_id];
        } else {
            $productCategories = $categoriesEntity->getProductCategories([$productId]);
            foreach ($productCategories as $productCategory) {
                if (isset($categoriesById[$productCategory->category_id])) {
                    $category = $categoriesById[$productCategory->category_id];
                    break;
                }
            }
        }

        $categoryPayload = [
            'external_id' => $category ? (string) $category->id : '',
            'external_url' => $category ? $this->router->generateUrl('category', ['url' => $category->url], true, $langId) : '',
            'external_parent_id' => $category && !empty($category->parent_id) ? (string) $category->parent_id : '',
            'position' => $category ? (int) ($category->position ?? 0) : 0,
            'full_path' => '',
            'path' => [],
        ];

        if ($category) {
            $pathNames = [];
            $current = $category;
            while ($current) {
                $pathNames[] = $current->name;
                $parentId = (int) $current->parent_id;
                $current = $parentId && isset($categoriesById[$parentId]) ? $categoriesById[$parentId] : null;
            }
            $pathNames = array_reverse($pathNames);
            $categoryPayload['path'] = $pathNames;
            $categoryPayload['full_path'] = implode(' / ', $pathNames);
        }

        $firstVariant = reset($variants);
        $quantity = 0;
        $hasUnlimited = false;
        foreach ($variants as $variant) {
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

        $currencyCode = (string) ($this->settings->get('eleads__yml_feed__currency') ?: '');
        $price = $this->money->convert($firstVariant->price, $firstVariant->currency_id, false);
        $oldPrice = 0;
        if (!empty($firstVariant->compare_price) && $firstVariant->compare_price > 0) {
            $oldPrice = $this->money->convert($firstVariant->compare_price, $firstVariant->currency_id, false);
        }

        $brandName = '';
        if (!empty($product->brand_id)) {
            $brand = $brandsEntity->get((int) $product->brand_id);
            if (!empty($brand->name)) {
                $brandName = $brand->name;
            }
        }

        $imagesList = [];
        $rootUrl = Request::getRootUrl();
        $originalDir = $this->config->get('original_images_dir');
        foreach ($images as $image) {
            $imagesList[] = rtrim($rootUrl, '/') . '/' . $originalDir . $image->filename;
        }

        $shortDescriptionSource = (string) $this->settings->get('eleads__yml_feed__short_description_source');
        $shortDescription = ELeadsFeedFormatter::resolveShortDescription($product, $shortDescriptionSource);
        $productUrl = $this->router->generateUrl('product', ['url' => $product->url], true, $langId);

        return [
            'language' => $payloadLanguage,
            'external_id' => (string) $productId,
            'payload' => [
                'source' => [
                    'offer_id' => (string) $productId,
                    'language' => $payloadLanguage,
                    'url' => $productUrl,
                    'group_id' => (string) $productId,
                ],
                'product' => [
                    'title' => (string) $product->name,
                    'description' => (string) ($product->description ?? ''),
                    'short_description' => (string) $shortDescription,
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'currency' => $currencyCode,
                    'quantity' => $quantity,
                    'stock_status' => ELeadsFeedFormatter::formatStockStatus($available, $languageLabel),
                    'vendor' => $brandName,
                    'sku' => (string) ($firstVariant->sku ?? ''),
                    'label' => '',
                    'sort_order' => (int) ($product->position ?? 0),
                    'attributes' => (object) $attributes,
                    'attribute_filters' => $attributeFilters,
                    'images' => $imagesList,
                ],
                'category' => $categoryPayload,
            ],
        ];
    }

    private function getSelectedFeatureFilters(): array
    {
        $selectedFeatureIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_features'))));
        $selectedFeatureValueIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_options'))));

        return [
            'features' => array_flip($selectedFeatureIds),
            'values' => array_flip($selectedFeatureValueIds),
        ];
    }
}
