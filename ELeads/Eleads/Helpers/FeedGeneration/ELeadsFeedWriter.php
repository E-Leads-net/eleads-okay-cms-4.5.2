<?php

namespace Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration;

use Okay\Core\Router;

class ELeadsFeedWriter
{
    private ELeadsFeedPathResolver $pathResolver;

    public function __construct(ELeadsFeedPathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function start(string $lang, array $feedMeta, array $categories, int $langId): void
    {
        $tempPath = $this->pathResolver->getTempFeedPath($lang);
        $content = [];
        $content[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $content[] = '<yml_catalog date="' . $this->escape((string) $feedMeta['feed_date']) . '">';
        $content[] = '<shop>';
        $content[] = '<shopName>' . $this->escape((string) $feedMeta['shop_name']) . '</shopName>';
        $content[] = '<email>' . $this->escape((string) $feedMeta['email']) . '</email>';
        $content[] = '<url>' . $this->escape((string) $feedMeta['shop_url']) . '</url>';
        $content[] = '<language>' . $this->escape((string) $feedMeta['language']) . '</language>';
        $content[] = '<categories>';

        foreach ($categories as $category) {
            $attributes = ['id="' . (int) $category->id . '"'];
            if (!empty($category->parent_id)) {
                $attributes[] = 'parentId="' . (int) $category->parent_id . '"';
            }
            $attributes[] = 'position="' . (int) ($category->position ?? 0) . '"';
            $attributes[] = 'url="' . $this->escape(Router::generateUrl('category', ['url' => $category->url], true, $langId)) . '"';

            $content[] = '<category ' . implode(' ', $attributes) . '>' . $this->escape((string) $category->name) . '</category>';
        }

        $content[] = '</categories>';
        $content[] = '<offers>';

        file_put_contents($tempPath, implode('', $content), LOCK_EX);
    }

    public function appendOffers(string $lang, array $offers, int $langId): void
    {
        if (empty($offers)) {
            return;
        }

        $tempPath = $this->pathResolver->getTempFeedPath($lang);
        $chunks = [];

        foreach ($offers as $offer) {
            $attributes = ['id="' . (int) $offer['id'] . '"'];
            if (!empty($offer['group_id'])) {
                $attributes[] = 'group_id="' . (int) $offer['group_id'] . '"';
            }
            $attributes[] = 'available="' . (!empty($offer['available']) ? 'true' : 'false') . '"';

            $chunks[] = '<offer ' . implode(' ', $attributes) . '>';
            $chunks[] = '<url>' . $this->escape(Router::generateUrl('product', ['url' => $offer['url']], true, $langId)) . '</url>';
            $chunks[] = '<name>' . $this->escape((string) $offer['name']) . '</name>';
            $chunks[] = '<price>' . (string) $offer['price'] . '</price>';
            $chunks[] = '<old_price>' . ($offer['old_price'] !== null ? (string) $offer['old_price'] : '') . '</old_price>';
            $chunks[] = '<currency>' . $this->escape((string) $offer['currency']) . '</currency>';
            $chunks[] = '<categoryId>' . (int) $offer['category_id'] . '</categoryId>';
            $chunks[] = '<quantity>' . (int) $offer['quantity'] . '</quantity>';
            $chunks[] = '<stock_status>' . $this->escape((string) $offer['stock_status']) . '</stock_status>';

            foreach ($offer['pictures'] as $picture) {
                $chunks[] = '<picture>' . $this->escape((string) $picture) . '</picture>';
            }

            $chunks[] = '<vendor>' . $this->escape((string) $offer['vendor']) . '</vendor>';
            $chunks[] = '<sku>' . $this->escape((string) $offer['sku']) . '</sku>';
            $chunks[] = '<label/>';
            $chunks[] = '<order>' . (int) $offer['order'] . '</order>';
            $chunks[] = '<description>' . $this->escape((string) $offer['description']) . '</description>';
            $chunks[] = '<short_description>' . $this->escape((string) $offer['short_description']) . '</short_description>';

            foreach ($offer['params'] as $param) {
                $chunks[] = '<param' . (!empty($param['filter']) ? ' filter="true"' : '') . ' name="' . $this->escape((string) $param['name']) . '">' . $this->escape((string) $param['value']) . '</param>';
            }

            $chunks[] = '</offer>';
        }

        file_put_contents($tempPath, implode('', $chunks), FILE_APPEND | LOCK_EX);
    }

    public function finalizeAndPublish(string $lang): int
    {
        $tempPath = $this->pathResolver->getTempFeedPath($lang);
        $finalPath = $this->pathResolver->getFinalFeedPath($lang);

        file_put_contents($tempPath, '</offers></shop></yml_catalog>', FILE_APPEND | LOCK_EX);
        rename($tempPath, $finalPath);

        return (int) filesize($finalPath);
    }

    public function resetTemp(string $lang): void
    {
        $tempPath = $this->pathResolver->getTempFeedPath($lang);
        if (is_file($tempPath)) {
            unlink($tempPath);
        }
    }

    public function hasFinalFeed(string $lang): bool
    {
        return is_file($this->pathResolver->getFinalFeedPath($lang));
    }

    public function getFinalFeedPath(string $lang): string
    {
        return $this->pathResolver->getFinalFeedPath($lang);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
