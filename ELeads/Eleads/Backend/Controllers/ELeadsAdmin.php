<?php


namespace Okay\Modules\ELeads\Eleads\Backend\Controllers;


use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\Request;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\FeaturesEntity;
use Okay\Entities\FeaturesValuesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsUpdateHelper;

class ELeadsAdmin extends IndexAdmin
{
    public function fetch(
        CategoriesEntity $categoriesEntity,
        CurrenciesEntity $currenciesEntity,
        FeaturesEntity $featuresEntity,
        FeaturesValuesEntity $featuresValuesEntity,
        LanguagesEntity $languagesEntity
    ) {
        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        $apiKeyValid = false;
        $apiKeyError = null;
        $apiKeySubmitted = null;
        $isApiKeyForm = false;

        if ($this->request->method('POST') && $this->request->post('eleads__api_key_submit') !== null) {
            $isApiKeyForm = true;
            $apiKeySubmitted = trim((string) $this->request->post('eleads__api_key', 'string'));
            if ($apiKeySubmitted !== '' && $this->checkApiKeyStatus($apiKeySubmitted)) {
                $this->settings->set('eleads__api_key', $apiKeySubmitted);
                $apiKey = $apiKeySubmitted;
                $apiKeyValid = true;
                $this->design->assign('message_success', 'saved');
            } else {
                $apiKeyError = 'invalid';
            }
        }

        if (!$isApiKeyForm) {
            if ($apiKey !== '') {
                $apiKeyValid = $this->checkApiKeyStatus($apiKey);
                if (!$apiKeyValid) {
                    $apiKeyError = 'invalid';
                }
            }
        }

        if ($this->request->method('POST') && !$isApiKeyForm && $apiKeyValid) {
            $this->settings->set('eleads__yml_feed__categories', (array) $this->request->post('eleads__yml_feed__categories'));
            $this->settings->set('eleads__yml_feed__filter_features', (array) $this->request->post('eleads__yml_feed__filter_features'));
            $this->settings->set('eleads__yml_feed__filter_options', (array) $this->request->post('eleads__yml_feed__filter_options'));
            $this->settings->set('eleads__yml_feed__grouped', $this->request->post('eleads__yml_feed__grouped') ? 1 : 0);
            $this->settings->set('eleads__sync_enabled', $this->request->post('eleads__sync_enabled') ? 1 : 0);
            $this->settings->set('eleads__yml_feed__access_key', $this->request->post('eleads__yml_feed__access_key', 'string'));
            $this->settings->set('eleads__yml_feed__shop_name', $this->request->post('eleads__yml_feed__shop_name'));
            $this->settings->set('eleads__yml_feed__email', $this->request->post('eleads__yml_feed__email'));
            $this->settings->set('eleads__yml_feed__shop_url', $this->request->post('eleads__yml_feed__shop_url'));
            $this->settings->set('eleads__yml_feed__currency', $this->request->post('eleads__yml_feed__currency'));
            $this->settings->set('eleads__yml_feed__picture_limit', $this->request->post('eleads__yml_feed__picture_limit', 'integer'));
            $this->settings->set('eleads__yml_feed__short_description_source', $this->request->post('eleads__yml_feed__short_description_source'));

            $this->design->assign('message_success', 'saved');
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
        $features = $featuresEntity->find();
        $featureValues = $featuresValuesEntity->find();
        $languages = $languagesEntity->mappedBy('id')->find();

        $rootUrl = Request::getRootUrl();
        $feedUrls = [];
        foreach ($languages as $language) {
            $label = $language->label === 'ua' ? 'uk' : $language->label;
            $feedUrls[$language->id] = rtrim($rootUrl, '/') . '/eleads-yml/' . $label . '.xml';
        }
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
        $this->design->assign('default_shop_name', $defaultShopName);
        $this->design->assign('default_email', $defaultEmail);
        $this->design->assign('default_currency', $defaultCurrency);
        $this->design->assign('default_picture_limit', $defaultPictureLimit);
        $this->design->assign('api_key_required', !$apiKeyValid);
        $this->design->assign('api_key_value', $apiKeySubmitted !== null ? $apiKeySubmitted : $apiKey);
        $this->design->assign('api_key_error', $apiKeyError);
        $this->design->assign('update_info', $updateInfo);
        $this->design->assign('update_action_url', $updateActionUrl);

        $this->response->setContent($this->design->fetch('e_leads.tpl'));
    }

    private function checkApiKeyStatus(string $apiKey): bool
    {
        if ($apiKey === '') {
            return false;
        }

        $ch = curl_init();
        if ($ch === false) {
            return false;
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
            return false;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }

        $data = json_decode($response, true);
        return is_array($data) && !empty($data['ok']);
    }
}
