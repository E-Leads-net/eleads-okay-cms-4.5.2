<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Config;
use Okay\Core\QueryFactory;
use Okay\Core\Request;

class ELeadsFeedFormatter
{
    public static function collectCategoryTree(array $selectedCategoryIds, array $categoriesById): array
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

    public static function loadProductFeatures(QueryFactory $queryFactory, array $productIds, int $langId): array
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

    public static function prepareParams(array $features, array $selectedFeatureSet, array $selectedFeatureValueSet): array
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

    public static function buildImageUrls(Config $config, array $images, int $limit): array
    {
        if (empty($images)) {
            return [];
        }
        $rootUrl = Request::getRootUrl();
        $originalDir = $config->get('original_images_dir');
        $urls = [];
        foreach ($images as $image) {
            if ($limit > 0 && count($urls) >= $limit) {
                break;
            }
            $urls[] = rtrim($rootUrl, '/') . '/' . $originalDir . $image->filename;
        }
        return $urls;
    }

    public static function resolveShortDescription($product, string $source): string
    {
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

    public static function formatStockStatus(bool $available, string $lang): string
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
