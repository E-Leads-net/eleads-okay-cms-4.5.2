<?php


namespace Okay\Modules\ELeads\YmlFeed\Controllers;


use Okay\Controllers\AbstractController;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Entities\LanguagesEntity;

class ELeadsYmlFeedController extends AbstractController
{
    public function render(
        CategoriesEntity $categoriesEntity,
        BrandsEntity $brandsEntity,
        ProductsEntity $productsEntity,
        VariantsEntity $variantsEntity,
        ImagesEntity $imagesEntity,
        CurrenciesEntity $currenciesEntity,
        LanguagesEntity $languagesEntity,
        Languages $languages,
        Money $money,
        QueryFactory $queryFactory,
        Request $request,
        $lang
    ) {
        $accessKey = (string) $this->settings->get('eleads__yml_feed__access_key');
        if (!empty($accessKey)) {
            $requestKey = (string) $request->get('key');
            if ($requestKey !== $accessKey) {
                return false;
            }
        }

        $currentLanguage = $languagesEntity->findOne(['label' => $lang]);
        if (!empty($currentLanguage->id)) {
            $languages->setLangId((int) $currentLanguage->id);
        }

        $shopName = $this->settings->get('eleads__yml_feed__shop_name') ?: $this->settings->get('site_name');
        $email = $this->settings->get('eleads__yml_feed__email') ?: ($this->settings->get('order_email') ?: $this->settings->get('notify_from_email'));
        $shopUrl = $this->settings->get('eleads__yml_feed__shop_url') ?: Request::getRootUrl();
        $currencyCode = $this->settings->get('eleads__yml_feed__currency');
        $pictureLimit = $this->settings->get('eleads__yml_feed__picture_limit');
        if ($pictureLimit === null || $pictureLimit === '') {
            $pictureLimit = 5;
        }

        $mainCurrency = $currenciesEntity->getMainCurrency();
        if (empty($currencyCode) && !empty($mainCurrency)) {
            $currencyCode = $mainCurrency->code;
        }

        $selectedCategoryIds = (array) $this->settings->get('eleads__yml_feed__categories');
        $allCategories = $categoriesEntity->find();
        $allCategoryIds = [];
        $categoriesById = [];
        foreach ($allCategories as $category) {
            $allCategoryIds[] = (int) $category->id;
            $categoriesById[$category->id] = $category;
        }
        if (empty($selectedCategoryIds)) {
            $selectedCategoryIds = $allCategoryIds;
        }
        $selectedCategoryIds = array_values(array_unique(array_map('intval', $selectedCategoryIds)));
        $selectedCategorySet = array_flip($selectedCategoryIds);

        $categoriesToExport = $this->collectCategoryTree($selectedCategoryIds, $categoriesById);
        $exportCategories = [];
        foreach ($allCategories as $category) {
            if (isset($categoriesToExport[$category->id])) {
                $exportCategories[] = $category;
            }
        }

        $products = $productsEntity->find([
            'visible' => 1,
            'limit' => $productsEntity->count(['visible' => 1]),
        ]);
        $productIds = array_map(static function ($product) {
            return (int) $product->id;
        }, $products);

        $productCategories = [];
        if (!empty($productIds)) {
            $productCategoryRows = $categoriesEntity->getProductCategories($productIds);
            foreach ($productCategoryRows as $row) {
                $productCategories[$row->product_id][] = (int) $row->category_id;
            }
        }

        $variantsByProduct = [];
        if (!empty($productIds)) {
            $variants = $variantsEntity->find(['product_id' => $productIds]);
            foreach ($variants as $variant) {
                $variantsByProduct[$variant->product_id][] = $variant;
            }
        }

        $brands = $brandsEntity->find(['limit' => $brandsEntity->count()]);
        $brandsById = [];
        foreach ($brands as $brand) {
            $brandsById[$brand->id] = $brand->name;
        }

        $imagesByProduct = [];
        if (!empty($productIds)) {
            $images = $imagesEntity->find(['product_id' => $productIds]);
            foreach ($images as $image) {
                $imagesByProduct[$image->product_id][] = $image;
            }
        }

        $featureMap = $this->loadProductFeatures($queryFactory, $productIds, $languages->getLangId());

        $selectedFeatureIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_features'))));
        $selectedFeatureValueIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_options'))));
        $selectedFeatureSet = array_flip($selectedFeatureIds);
        $selectedFeatureValueSet = array_flip($selectedFeatureValueIds);

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
            $pictures = $this->buildImageUrls($productImages, (int) $pictureLimit);

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
                    'stock_status' => $this->formatStockStatus($available, $lang),
                    'pictures' => $pictures,
                    'vendor' => !empty($brandsById[$product->brand_id]) ? $brandsById[$product->brand_id] : '',
                    'sku' => $variant->sku,
                    'label' => '',
                    'order' => $product->position ?? 0,
                    'description' => $product->description,
                    'short_description' => $this->resolveShortDescription($product),
                    'params' => $this->prepareParams($productFeatures, $selectedFeatureSet, $selectedFeatureValueSet),
                ];
            }
        }

        $this->design->assign('feed_date', date('Y-m-d H:i'));
        $this->design->assign('shop_name', $shopName);
        $this->design->assign('email', $email);
        $this->design->assign('shop_url', $shopUrl);
        $this->design->assign('language', $lang);
        $this->design->assign('categories', $exportCategories);
        $this->design->assign('offers', $offers);

        $this->response->setContentType(RESPONSE_XML);
        $this->response->setContent($this->design->fetch('eleads_yml_feed.xml.tpl'));
    }

    private function collectCategoryTree(array $selectedCategoryIds, array $categoriesById): array
    {
        $result = [];
        foreach ($selectedCategoryIds as $categoryId) {
            $categoryId = (int) $categoryId;
            if (!isset($categoriesById[$categoryId])) {
                continue;
            }
            $currentId = $categoryId;
            while (!empty($currentId) && isset($categoriesById[$currentId])) {
                $result[$currentId] = true;
                $currentId = (int) $categoriesById[$currentId]->parent_id;
            }
        }
        return $result;
    }

    private function loadProductFeatures(QueryFactory $queryFactory, array $productIds, int $langId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $select = $queryFactory->newSelect();
        $select->from('__products_features_values AS pf')
            ->cols([
                'pf.product_id',
                'f.id AS feature_id',
                'lf.name AS feature_name',
                'lfv.value AS value',
                'fv.id AS value_id',
            ])
            ->join('LEFT', '__features_values AS fv', 'fv.id=pf.value_id')
            ->join('LEFT', '__features AS f', 'f.id=fv.feature_id')
            ->join('LEFT', '__lang_features AS lf', 'lf.feature_id=f.id AND lf.lang_id=:lang_id')
            ->join('LEFT', '__lang_features_values AS lfv', 'lfv.feature_value_id=fv.id AND lfv.lang_id=:lang_id')
            ->where('pf.product_id IN (:product_ids)')
            ->bindValue('product_ids', $productIds)
            ->bindValue('lang_id', $langId)
            ->orderBy(['pf.product_id']);

        $rows = $select->results();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->product_id][] = [
                'feature_id' => (int) $row->feature_id,
                'feature_name' => $row->feature_name,
                'value' => $row->value,
                'value_id' => (int) $row->value_id,
            ];
        }
        return $result;
    }

    private function prepareParams(array $features, array $selectedFeatureSet, array $selectedFeatureValueSet): array
    {
        $params = [];
        foreach ($features as $feature) {
            if (empty($feature['feature_name']) || $feature['value'] === null) {
                continue;
            }
            $isFilter = isset($selectedFeatureSet[(int) $feature['feature_id']])
                || isset($selectedFeatureValueSet[(int) $feature['value_id']]);
            $params[] = [
                'name' => $feature['feature_name'],
                'value' => $feature['value'],
                'filter' => $isFilter,
            ];
        }
        return $params;
    }

    private function buildImageUrls(array $images, int $limit): array
    {
        if (empty($images)) {
            return [];
        }
        $rootUrl = Request::getRootUrl();
        $originalDir = $this->config->get('original_images_dir');
        $urls = [];
        foreach ($images as $image) {
            if ($limit > 0 && count($urls) >= $limit) {
                break;
            }
            $urls[] = rtrim($rootUrl, '/') . '/' . $originalDir . $image->filename;
        }
        return $urls;
    }

    private function resolveShortDescription($product): string
    {
        $source = $this->settings->get('eleads__yml_feed__short_description_source');
        if ($source === 'meta_description' && !empty($product->meta_description)) {
            return (string) $product->meta_description;
        }
        if ($source === 'description' && !empty($product->description)) {
            return (string) $product->description;
        }
        if (!empty($product->annotation)) {
            return (string) $product->annotation;
        }
        return (string) ($product->meta_description ?? '');
    }

    private function formatStockStatus(bool $available, string $lang): string
    {
        if ($lang === 'uk' || $lang === 'ua') {
            return $available ? 'В наявності' : 'Немає в наявності';
        }
        if ($lang === 'en') {
            return $available ? 'In stock' : 'Out of stock';
        }
        return $available ? 'На складе' : 'Нет в наличии';
    }
}
