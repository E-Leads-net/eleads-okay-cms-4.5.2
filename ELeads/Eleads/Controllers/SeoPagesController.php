<?php


namespace Okay\Modules\ELeads\Eleads\Controllers;


use Okay\Controllers\AbstractController;
use Okay\Core\Languages;
use Okay\Core\Request;
use Okay\Core\Router;
use Okay\Entities\CategoriesEntity;
use Okay\Helpers\CatalogHelper;
use Okay\Helpers\FilterHelper;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\SeoPagesApiHelper;
use Okay\Modules\ELeads\Eleads\Helpers\SeoSitemapHelper;

class SeoPagesController extends AbstractController
{
    public function render(
        Request $request,
        Languages $languages,
        ProductsHelper $productsHelper,
        FilterHelper $filterHelper,
        CatalogHelper $catalogHelper,
        CategoriesEntity $categoriesEntity,
        $slug
    )
    {
        $enabled = (int) $this->settings->get('eleads__seo_pages_enabled');
        if ($enabled !== 1) {
            return false;
        }

        $slug = (string) $slug;
        $langLabel = (string) $languages->getLangLabel();
        $sitemapHelper = new SeoSitemapHelper($this->settings, Request::getRootUrl(), $languages);
        if (!$sitemapHelper->hasSlug($slug, $langLabel)) {
            return false;
        }

        $apiHelper = new SeoPagesApiHelper($this->settings);
        $page = $apiHelper->fetchPage($slug, $langLabel);
        if ($page === null) {
            return false;
        }

        $productIds = [];
        if (!empty($page['product_ids']) && is_array($page['product_ids'])) {
            $productIds = array_values(array_unique(array_map('intval', $page['product_ids'])));
            $productIds = array_values(array_filter($productIds, static fn ($id) => $id > 0));
        }

        $keyword = (string) ($page['query'] ?? '');
        $productsFilter = [
            'visible' => 1,
        ];
        if ($keyword !== '') {
            $productsFilter['keyword'] = $keyword;
        }

        $isFilterPage = false;
        $catalogFeatures = [];

        // New core branch (4.5.x+): uses ProductsHelper/CatalogHelper filter pipeline.
        if (method_exists($productsHelper, 'getCatalogFeatures')
            && method_exists($productsHelper, 'assignFilterProcedure')
        ) {
            $catalogFeatures = (array) $productsHelper->getCatalogFeatures();

            if (!empty($catalogFeatures) && method_exists($filterHelper, 'setFeatures')) {
                $filterHelper->setFeatures($catalogFeatures);
            }

            $productsHelper->assignFilterProcedure($productsFilter, $catalogFeatures, $keyword);

            if (method_exists($productsHelper, 'isFilterPage')) {
                $isFilterPage = (bool) $productsHelper->isFilterPage($productsFilter);
            } elseif (method_exists($filterHelper, 'isFilterPage')) {
                $isFilterPage = (bool) $filterHelper->isFilterPage($productsFilter);
            }
        } else {
            // Old core branch (4.0.x): fallback to legacy search filter flow.
            if (method_exists($filterHelper, 'setFiltersUrl')) {
                $filterHelper->setFiltersUrl('');
            }

            if (method_exists($catalogHelper, 'getPriceFilter')) {
                $productsFilter['price'] = $catalogHelper->getPriceFilter('search');
            }

            if (method_exists($catalogHelper, 'getOtherFilters')) {
                $this->design->assign('other_filters', $catalogHelper->getOtherFilters($productsFilter));
            }

            if (method_exists($catalogHelper, 'getPrices')) {
                $prices = $catalogHelper->getPrices($productsFilter, 'search');
                $this->design->assign('prices', $prices);
            }

            if (method_exists($filterHelper, 'getSearchProductsFilter')) {
                $legacyFilter = $filterHelper->getSearchProductsFilter($productsFilter, $keyword);
                if ($legacyFilter === false) {
                    return false;
                }
                $productsFilter = $legacyFilter;
            }

            $hasPriceFilter = !empty($productsFilter['price'])
                && isset($productsFilter['price']['min'], $productsFilter['price']['max'])
                && $productsFilter['price']['min'] !== ''
                && $productsFilter['price']['max'] !== ''
                && $productsFilter['price']['min'] !== null;
            $isFilterPage = $hasPriceFilter || !empty($productsFilter['other_filter']);
        }

        $sort = (string) $request->get('sort', 'string');
        if ($sort === '') {
            $sort = 'position';
        }

        $products = [];
        if (!empty($productIds)) {
            $products = $productsHelper->getList([
                'id' => $productIds,
                'visible' => 1,
            ], $sort);
        }

        $metaTitle = $page['meta_title'] ?? $page['h1'] ?? $page['query'] ?? '';
        $metaDescription = $page['meta_description'] ?? '';
        $metaKeywords = $page['meta_keywords'] ?? '';

        $this->design->assign('seo_page', $page);
        $this->design->assign('h1', $page['h1'] ?? $page['query'] ?? '');
        $this->design->assign('annotation', $page['short_description'] ?? '');
        $this->design->assign('description', $page['description'] ?? '');
        if (!method_exists($productsHelper, 'getCatalogFeatures')) {
            $legacyCategory = $this->design->getVar('category');
            if (!is_object($legacyCategory)) {
                $legacyCategory = new \stdClass();
            }
            if (!isset($legacyCategory->id)) {
                $legacyCategory->id = 0;
            }
            $legacyCategory->annotation = (string) ($page['short_description'] ?? '');
            $this->design->assign('category', $legacyCategory);
        }
        $this->design->assign('products', $products);
        $this->design->assign('sort', $sort);
        $this->design->assign('is_filter_page', $isFilterPage);
        $this->design->assign('total_pages_num', 1);
        $this->design->assign('current_page_num', 1);
        $this->design->assign('route_name', 'products');
        $this->design->assign('furlRoute', 'products');
        $this->design->assign('keyword', $keyword);
        $this->design->assign('meta_title', $metaTitle);
        $this->design->assign('meta_description', $metaDescription);
        $this->design->assign('meta_keywords', $metaKeywords);
        $canonicalUrl = trim((string) ($page['url'] ?? ''));
        if ($canonicalUrl === '') {
            $canonicalUrl = Router::generateUrl('ELeads_Seo_Page', ['slug' => $slug], true);
        }

        $this->applyAlternateLinks($page['alternate'] ?? [], $langLabel, $canonicalUrl);
        $this->design->assign('canonical', $canonicalUrl);

        $this->response->setContent('products.tpl');
    }

    /**
     * @param array<int, mixed> $alternates
     */
    private function applyAlternateLinks(array $alternates, string $currentLangLabel, string $currentUrl): void
    {
        $languages = $this->design->getVar('languages');
        if (!is_array($languages) || empty($languages)) {
            return;
        }

        $currentLangLabel = strtolower(trim($currentLangLabel));
        $currentHreflang = $currentLangLabel === 'ua' ? 'uk' : $currentLangLabel;

        $alternateMap = [];
        foreach ($alternates as $alternate) {
            if (!is_array($alternate)) {
                continue;
            }

            $url = trim((string) ($alternate['url'] ?? ''));
            $apiLang = strtolower(trim((string) ($alternate['lang'] ?? '')));
            if ($url === '' || $apiLang === '') {
                continue;
            }

            $storeLangLabel = $this->resolveStoreLanguageLabel($apiLang, $languages);
            if ($storeLangLabel === '') {
                continue;
            }

            $alternateMap[$storeLangLabel] = [
                'url' => $url,
                'hreflang' => $apiLang,
            ];
        }

        $filteredLanguages = [];
        $currentLanguageObject = null;
        foreach ($languages as $key => $language) {
            if (!is_object($language)) {
                continue;
            }

            $label = strtolower((string) ($language->label ?? ''));
            if ($label === $currentLangLabel) {
                $currentLanguageObject = $language;
            }

            if ($label === '' || !isset($alternateMap[$label])) {
                continue;
            }

            $languages[$key]->enabled = true;
            $languages[$key]->url = $alternateMap[$label]['url'];
            $languages[$key]->href_lang = $alternateMap[$label]['hreflang'];
            $filteredLanguages[$key] = $languages[$key];
        }

        if ($currentLangLabel !== '' && $currentUrl !== '') {
            if (is_object($currentLanguageObject)) {
                $currentLanguageObject->enabled = true;
                $currentLanguageObject->url = $currentUrl;
                $currentLanguageObject->href_lang = $currentHreflang;
                $filteredLanguages[$currentLangLabel] = $currentLanguageObject;
            } else {
                $currentItem = new \stdClass();
                $currentItem->enabled = true;
                $currentItem->url = $currentUrl;
                $currentItem->href_lang = $currentHreflang;
                $filteredLanguages[$currentLangLabel] = $currentItem;
            }
        }

        $this->design->assign('languages', $filteredLanguages);
    }

    /**
     * @param array<int, mixed> $languages
     */
    private function resolveStoreLanguageLabel(string $apiLang, array $languages): string
    {
        $apiLang = strtolower(trim($apiLang));
        if ($apiLang === '') {
            return '';
        }

        $labels = [];
        foreach ($languages as $language) {
            if (!is_object($language)) {
                continue;
            }

            $label = strtolower((string) ($language->label ?? ''));
            if ($label !== '') {
                $labels[$label] = true;
            }
        }

        if (isset($labels[$apiLang])) {
            return $apiLang;
        }
        if ($apiLang === 'uk' && isset($labels['ua'])) {
            return 'ua';
        }
        if ($apiLang === 'ua' && isset($labels['uk'])) {
            return 'uk';
        }

        return '';
    }
}
