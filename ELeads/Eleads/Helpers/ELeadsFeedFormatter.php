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
        $productIdSet = array_fill_keys(array_map('intval', $productIds), true);

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
            ->where('pf.product_id IN (?)', $productIds)
            ->bindValue('lang_id', $langId)
            ->orderBy(['pf.product_id']);

        $rows = $select->results();
        $result = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            if (!isset($productIdSet[$productId])) {
                continue;
            }
            $result[$productId][] = [
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
        $grouped = [];
        foreach ($features as $feature) {
            if (empty($feature['feature_name']) || $feature['value'] === null) {
                continue;
            }
            $isFilter = isset($selectedFeatureSet[(int) $feature['feature_id']])
                || isset($selectedFeatureValueSet[(int) $feature['value_id']]);
            $name = (string) $feature['feature_name'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'values' => [],
                    'filter' => false,
                ];
            }
            $grouped[$name]['values'][] = (string) $feature['value'];
            if ($isFilter) {
                $grouped[$name]['filter'] = true;
            }
        }
        $params = [];
        foreach ($grouped as $item) {
            $values = array_values(array_unique(array_filter($item['values'], static function ($value) {
                return $value !== '';
            })));
            if (empty($values)) {
                continue;
            }
            $params[] = [
                'name' => $item['name'],
                'value' => implode('; ', $values),
                'filter' => $item['filter'],
            ];
        }
        return $params;
    }

    public static function buildImageUrls(
        Config $config,
        array $images,
        int $limit,
        string $imageSize = 'original'
    ): array
    {
        if (empty($images)) {
            return [];
        }
        $rootUrl = Request::getRootUrl();
        $imageSize = trim($imageSize);
        $useOriginal = $imageSize === '' || $imageSize === 'original';
        $originalDir = $config->get('original_images_dir');
        $resizedDir = $config->get('resized_images_dir');
        $urls = [];
        foreach ($images as $image) {
            if ($limit > 0 && count($urls) >= $limit) {
                break;
            }
            $filename = $image->filename;
            if (!$useOriginal) {
                $resizedFilename = self::buildResizedFilename($filename, $imageSize);
                if ($resizedFilename !== null) {
                    $filename = $resizedFilename;
                } else {
                    $useOriginal = true;
                }
            }
            $dir = $useOriginal ? $originalDir : $resizedDir;
            $urls[] = rtrim($rootUrl, '/') . '/' . $dir . $filename;
        }
        return $urls;
    }

    private static function buildResizedFilename(string $filename, string $size): ?string
    {
        $size = trim($size);
        if ($size === '') {
            return null;
        }
        if (!preg_match('/^(\\d*)x(\\d*)(w?)$/', $size, $matches)) {
            return null;
        }

        $width = $matches[1];
        $height = $matches[2];
        $watermark = $matches[3] ?? '';

        $dirname = pathinfo($filename, PATHINFO_DIRNAME);
        $file = pathinfo($filename, PATHINFO_FILENAME);
        if ($dirname !== '.' && $dirname !== '') {
            $file = $dirname . '/' . $file;
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $resizedFilename = $file . '.' . $width . 'x' . $height . ($watermark !== '' ? 'w' : '') . '.' . $ext;
        return $resizedFilename;
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
