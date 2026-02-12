<?php


namespace Okay\Modules\ELeads\Eleads\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Languages;
use Okay\Core\Request;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\FeaturesEntity;
use Okay\Entities\FeaturesValuesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsUpdateHelper;
use Okay\Modules\ELeads\Eleads\Helpers\SeoSitemapHelper;

class ELeadsAdmin extends IndexAdmin
{
    public function fetch(
        CategoriesEntity $categoriesEntity,
        CurrenciesEntity $currenciesEntity,
        FeaturesEntity $featuresEntity,
        FeaturesValuesEntity $featuresValuesEntity,
        LanguagesEntity $languagesEntity,
        Languages $languages
    ) {
        $seoSitemapHelper = new SeoSitemapHelper($this->settings, Request::getRootUrl(), $languages);
        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        $apiKeyValid = false;
        $seoAllowed = false;
        $apiKeyError = null;
        $apiKeySubmitted = null;
        $isApiKeyForm = false;

        if ($this->request->method('POST') && $this->request->post('eleads__api_key_submit') !== null) {
            $isApiKeyForm = true;
            $apiKeySubmitted = trim((string) $this->request->post('eleads__api_key', 'string'));
            if ($apiKeySubmitted !== '') {
                $status = $this->checkApiKeyStatus($apiKeySubmitted);
                $apiKeyValid = $status['ok'];
                $seoAllowed = $status['seo_status'];
            }
            if ($apiKeySubmitted !== '' && $apiKeyValid) {
                $this->settings->set('eleads__api_key', $apiKeySubmitted);
                $apiKey = $apiKeySubmitted;
                $this->design->assign('message_success', 'saved');
            } else {
                $apiKeyError = 'invalid';
            }
        }

        if (!$isApiKeyForm) {
            if ($apiKey !== '') {
                $status = $this->checkApiKeyStatus($apiKey);
                $apiKeyValid = $status['ok'];
                $seoAllowed = $status['seo_status'];
                if (!$apiKeyValid) {
                    $apiKeyError = 'invalid';
                }
            }
        }

        if ($this->request->method('POST') && !$isApiKeyForm && $apiKeyValid) {
            $seoPagesEnabled = $seoAllowed && $this->request->post('eleads__seo_pages_enabled') ? 1 : 0;
            $this->settings->set('eleads__yml_feed__categories', (array) $this->request->post('eleads__yml_feed__categories'));
            $this->settings->set('eleads__yml_feed__filter_features', (array) $this->request->post('eleads__yml_feed__filter_features'));
            $this->settings->set('eleads__yml_feed__filter_options', (array) $this->request->post('eleads__yml_feed__filter_options'));
            $this->settings->set('eleads__yml_feed__grouped', $this->request->post('eleads__yml_feed__grouped') ? 1 : 0);
            $this->settings->set('eleads__sync_enabled', $this->request->post('eleads__sync_enabled') ? 1 : 0);
            $this->settings->set('eleads__seo_pages_enabled', $seoPagesEnabled);
            $this->settings->set('eleads__yml_feed__access_key', $this->request->post('eleads__yml_feed__access_key', 'string'));
            $this->settings->set('eleads__yml_feed__shop_name', $this->request->post('eleads__yml_feed__shop_name'));
            $this->settings->set('eleads__yml_feed__email', $this->request->post('eleads__yml_feed__email'));
            $this->settings->set('eleads__yml_feed__shop_url', $this->request->post('eleads__yml_feed__shop_url'));
            $this->settings->set('eleads__yml_feed__currency', $this->request->post('eleads__yml_feed__currency'));
            $this->settings->set('eleads__yml_feed__picture_limit', $this->request->post('eleads__yml_feed__picture_limit', 'integer'));
            $this->settings->set('eleads__yml_feed__image_size', $this->request->post('eleads__yml_feed__image_size', 'string'));
            $this->settings->set('eleads__yml_feed__short_description_source', $this->request->post('eleads__yml_feed__short_description_source'));

            if ($seoPagesEnabled) {
                $seoSitemapHelper->createSitemap();
            } else {
                $seoSitemapHelper->removeSitemap();
            }

            $this->design->assign('message_success', 'saved');
        }

        if (!$seoAllowed) {
            if ((int) $this->settings->get('eleads__seo_pages_enabled') === 1) {
                $this->settings->set('eleads__seo_pages_enabled', 0);
                $seoSitemapHelper->removeSitemap();
            }
        }
        if ($this->request->get('update_result')) {
            $this->design->assign('update_result', $this->request->get('update_result'));
            $this->design->assign('update_message', $this->request->get('update_message'));
        }

        $categories = $categoriesEntity->getCategoriesTree();
        $selectedCategories = (array) $this->settings->get('eleads__yml_feed__categories');

        $defaultShopName = $this->settings->get('site_name');
        $defaultEmail = $this->settings->get('order_email') ?: $this->settings->get('notify_from_email');
        $mainCurrency = $currenciesEntity->getMainCurrency();
        $defaultCurrency = $mainCurrency ? $mainCurrency->code : '';
        $defaultPictureLimit = 5;
        $imageSizesRaw = (string) $this->settings->get('products_image_sizes');
        $imageSizes = array_values(array_filter(array_map('trim', explode('|', $imageSizesRaw))));
        $selectedImageSize = (string) $this->settings->get('eleads__yml_feed__image_size');
        if ($selectedImageSize === '') {
            $selectedImageSize = 'original';
        }
        if ($selectedImageSize !== 'original' && !in_array($selectedImageSize, $imageSizes, true)) {
            $selectedImageSize = 'original';
        }
        $features = $featuresEntity->find();
        $featureValues = $featuresValuesEntity->find();
        $languages = $languagesEntity->mappedBy('id')->find();

        $rootUrl = Request::getRootUrl();
        $accessKey = (string) $this->settings->get('eleads__yml_feed__access_key');
        $accessKey = trim($accessKey);
        $feedUrls = [];
        foreach ($languages as $language) {
            $label = $language->label === 'ua' ? 'uk' : $language->label;
            $feedUrl = rtrim($rootUrl, '/') . '/eleads-yml/' . $label . '.xml';
            if ($accessKey !== '') {
                $feedUrl .= '?key=' . rawurlencode($accessKey);
            }
            $feedUrls[$language->id] = $feedUrl;
        }
        $sitemapUrl = rtrim($rootUrl, '/') . '/e-search/sitemap.xml';
        $updateInfo = ELeadsUpdateHelper::getUpdateInfo();
        $updateActionUrl = Request::getRootUrl() . '/backend/index.php?controller=ELeads.Eleads.ELeadsUpdateAdmin';

        $this->design->assign('categories', $categories);
        $this->design->assign('features', $features);
        $this->design->assign('feature_values', $featureValues);
        $this->design->assign('languages', $languages);
        $this->design->assign('feed_urls', $feedUrls);
        $this->design->assign('selected_categories', $selectedCategories);
        $this->design->assign('selected_features', (array) $this->settings->get('eleads__yml_feed__filter_features'));
        $this->design->assign('selected_feature_values', (array) $this->settings->get('eleads__yml_feed__filter_options'));
        $groupedProducts = $this->settings->get('eleads__yml_feed__grouped');
        if ($groupedProducts === null || $groupedProducts === '') {
            $groupedProducts = 1;
        }
        $this->design->assign('grouped_products', (int) $groupedProducts);
        $syncEnabled = $this->settings->get('eleads__sync_enabled');
        if ($syncEnabled === null || $syncEnabled === '') {
            $syncEnabled = 0;
        }
        $this->design->assign('sync_enabled', (int) $syncEnabled);
        $seoPagesEnabled = $this->settings->get('eleads__seo_pages_enabled');
        if ($seoPagesEnabled === null || $seoPagesEnabled === '') {
            $seoPagesEnabled = 0;
        }
        $this->design->assign('seo_pages_enabled', (int) $seoPagesEnabled);
        $this->design->assign('seo_pages_allowed', $seoAllowed);
        $this->design->assign('default_shop_name', $defaultShopName);
        $this->design->assign('default_email', $defaultEmail);
        $this->design->assign('default_currency', $defaultCurrency);
        $this->design->assign('default_picture_limit', $defaultPictureLimit);
        $this->design->assign('image_sizes', $imageSizes);
        $this->design->assign('selected_image_size', $selectedImageSize);
        $this->design->assign('api_key_required', !$apiKeyValid);
        $this->design->assign('api_key_value', $apiKeySubmitted !== null ? $apiKeySubmitted : $apiKey);
        $this->design->assign('api_key_error', $apiKeyError);
        $this->design->assign('update_info', $updateInfo);
        $this->design->assign('update_action_url', $updateActionUrl);
        $this->design->assign('seo_sitemap_url', $sitemapUrl);

        $this->response->setContent($this->design->fetch('e_leads.tpl'));
    }

    private function checkApiKeyStatus(string $apiKey): array
    {
        if ($apiKey === '') {
            return ['ok' => false, 'seo_status' => false];
        }

        $ch = curl_init();
        if ($ch === false) {
            return ['ok' => false, 'seo_status' => false];
        }

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, ELeadsApiRoutes::TOKEN_STATUS);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return ['ok' => false, 'seo_status' => false];
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['ok' => false, 'seo_status' => false];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'seo_status' => false];
        }

        return [
            'ok' => !empty($data['ok']),
            'seo_status' => !empty($data['seo_status']),
        ];
    }
}
