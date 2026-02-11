<?php


namespace Okay\Modules\ELeads\Eleads\Controllers;


use Okay\Controllers\AbstractController;
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
        $sitemapHelper = new SeoSitemapHelper($this->settings, Request::getRootUrl());
        if (!$sitemapHelper->hasSlug($slug)) {
            return false;
        }

        $apiHelper = new SeoPagesApiHelper($this->settings);
        $page = $apiHelper->fetchPage($slug);
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
        $this->design->assign('canonical', Router::generateUrl('ELeads_Seo_Page', ['slug' => $slug], true));

        $this->response->setContent('products.tpl');
    }
}
