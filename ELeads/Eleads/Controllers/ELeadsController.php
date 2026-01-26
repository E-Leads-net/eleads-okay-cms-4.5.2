<?php


namespace Okay\Modules\ELeads\Eleads\Controllers;


use Okay\Controllers\AbstractController;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\Request;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsAccessGuard;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedDataBuilder;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsOfferBuilder;

class ELeadsController extends AbstractController
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
        ProductsHelper $productsHelper,
        Request $request,
        $lang
    ) {
        if (!ELeadsAccessGuard::allowFeed($this->settings, $request)) {
            return false;
        }

        $feedLanguage = $lang === 'ua' ? 'uk' : $lang;
        ELeadsFeedDataBuilder::resolveLanguage($languagesEntity, $languages, $lang);

        $feedSettings = ELeadsFeedDataBuilder::buildFeedSettings($this->settings, $currenciesEntity);
        $shopName = $feedSettings['shop_name'];
        $email = $feedSettings['email'];
        $shopUrl = $feedSettings['shop_url'];
        $currencyCode = $feedSettings['currency_code'];
        $pictureLimit = $feedSettings['picture_limit'];
        $groupedProducts = (bool) $feedSettings['grouped_products'];

        $selectedCategoryIds = (array) $this->settings->get('eleads__yml_feed__categories');
        [$exportCategories, $selectedCategorySet] = ELeadsFeedDataBuilder::buildExportCategories(
            $categoriesEntity,
            $selectedCategoryIds
        );

        $products = ELeadsFeedDataBuilder::buildProducts($productsEntity);
        $productIds = ELeadsFeedDataBuilder::buildProductIds($products);
        $productCategories = ELeadsFeedDataBuilder::buildProductCategories($categoriesEntity, $productIds);
        $variantsByProduct = ELeadsFeedDataBuilder::buildVariantsByProduct($variantsEntity, $productIds);
        $brandsById = ELeadsFeedDataBuilder::buildBrandsById($brandsEntity);
        $imagesByProduct = ELeadsFeedDataBuilder::buildImagesByProduct($imagesEntity, $productIds);

        $featureMap = ELeadsFeedDataBuilder::buildProductFeatures($productsHelper, $products);
        $shortDescriptionSource = (string) $this->settings->get('eleads__yml_feed__short_description_source');

        $selectedFeatureIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_features'))));
        $selectedFeatureValueIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_options'))));
        $selectedFeatureSet = array_flip($selectedFeatureIds);
        $selectedFeatureValueSet = array_flip($selectedFeatureValueIds);

        $offers = ELeadsOfferBuilder::buildOffers(
            $products,
            $productCategories,
            $variantsByProduct,
            $imagesByProduct,
            $brandsById,
            $featureMap,
            $selectedCategorySet,
            $selectedFeatureSet,
            $selectedFeatureValueSet,
            $currencyCode,
            (int) $pictureLimit,
            $lang,
            $shortDescriptionSource,
            $money,
            $this->config,
            $groupedProducts
        );

        $this->design->assign('feed_date', date('Y-m-d H:i'));
        $this->design->assign('shop_name', $shopName);
        $this->design->assign('email', $email);
        $this->design->assign('shop_url', $shopUrl);
        $this->design->assign('language', $feedLanguage);
        $this->design->assign('categories', $exportCategories);
        $this->design->assign('offers', $offers);

        $this->response->setContentType(RESPONSE_XML);
        $this->response->setContent($this->design->fetch('eleads.xml.tpl'));
    }

}
