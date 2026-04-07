<?php

namespace Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration;

use Okay\Core\Config;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\Settings;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedDataBuilder;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedLanguageHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsOfferBuilder;

class ELeadsFeedBatchProcessor
{
    private const DEFAULT_BATCH_SIZE = 300;

    private Settings $settings;
    private Config $config;
    private Languages $languages;
    private LanguagesEntity $languagesEntity;
    private CategoriesEntity $categoriesEntity;
    private CurrenciesEntity $currenciesEntity;
    private ProductsEntity $productsEntity;
    private VariantsEntity $variantsEntity;
    private ImagesEntity $imagesEntity;
    private BrandsEntity $brandsEntity;
    private ProductsHelper $productsHelper;
    private Money $money;
    private ELeadsFeedJobStorage $jobStorage;
    private ELeadsFeedWriter $writer;
    private ELeadsFeedBatchDataBuilder $batchDataBuilder;
    private ELeadsFeedPathResolver $pathResolver;

    public function __construct(
        Settings $settings,
        Config $config,
        Languages $languages,
        LanguagesEntity $languagesEntity,
        CategoriesEntity $categoriesEntity,
        CurrenciesEntity $currenciesEntity,
        ProductsEntity $productsEntity,
        VariantsEntity $variantsEntity,
        ImagesEntity $imagesEntity,
        BrandsEntity $brandsEntity,
        ProductsHelper $productsHelper,
        Money $money,
        ELeadsFeedJobStorage $jobStorage,
        ELeadsFeedWriter $writer,
        ELeadsFeedBatchDataBuilder $batchDataBuilder,
        ELeadsFeedPathResolver $pathResolver
    ) {
        $this->settings = $settings;
        $this->config = $config;
        $this->languages = $languages;
        $this->languagesEntity = $languagesEntity;
        $this->categoriesEntity = $categoriesEntity;
        $this->currenciesEntity = $currenciesEntity;
        $this->productsEntity = $productsEntity;
        $this->variantsEntity = $variantsEntity;
        $this->imagesEntity = $imagesEntity;
        $this->brandsEntity = $brandsEntity;
        $this->productsHelper = $productsHelper;
        $this->money = $money;
        $this->jobStorage = $jobStorage;
        $this->writer = $writer;
        $this->batchDataBuilder = $batchDataBuilder;
        $this->pathResolver = $pathResolver;
    }

    public function start(string $lang): array
    {
        $batchSize = self::DEFAULT_BATCH_SIZE;
        $this->writer->resetTemp($lang);
        $lockHandle = $this->acquireLockHandle($lang, true);
        if ($lockHandle === false) {
            throw new \RuntimeException('generation_lock_failed');
        }

        try {
            ELeadsFeedDataBuilder::resolveLanguage($this->languagesEntity, $this->languages, $lang);
            $langId = (int) $this->languages->getLangId();
            $feedLanguage = ELeadsFeedLanguageHelper::normalizeExternalLanguage($lang, $this->languages);

            $feedSettings = ELeadsFeedDataBuilder::buildFeedSettings($this->settings, $this->currenciesEntity);
            [$exportCategories] = ELeadsFeedDataBuilder::buildExportCategories(
                $this->categoriesEntity,
                (array) $this->settings->get('eleads__yml_feed__categories')
            );

            $this->writer->start($lang, [
                'feed_date' => date('Y-m-d H:i'),
                'shop_name' => $feedSettings['shop_name'],
                'email' => $feedSettings['email'],
                'shop_url' => $feedSettings['shop_url'],
                'language' => $feedLanguage,
            ], $exportCategories, $langId);

            $job = $this->jobStorage->create($lang, $batchSize);
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);

            return $job;
        } catch (\Throwable $e) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            throw $e;
        }
    }

    public function processNextBatch(string $lang): array
    {
        $job = $this->jobStorage->get($lang);
        if ($job['status'] !== 'running') {
            return $job;
        }

        $lockHandle = $this->acquireLockHandle($lang, false);
        if ($lockHandle === false) {
            return $job;
        }

        try {
            ELeadsFeedDataBuilder::resolveLanguage($this->languagesEntity, $this->languages, $lang);
            $langId = (int) $this->languages->getLangId();
            $selectedCategoryIds = (array) $this->settings->get('eleads__yml_feed__categories');
            if (empty($selectedCategoryIds)) {
                $size = $this->writer->finalizeAndPublish($lang);
                $job = $this->jobStorage->markReady($lang, 0, 0, $size);
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                return $job;
            }

            $products = $this->batchDataBuilder->getVisibleProductBatch($job['last_product_id'], $job['batch_size']);
            if (empty($products)) {
                $size = $this->writer->finalizeAndPublish($lang);
                $job = $this->jobStorage->markReady($lang, $job['processed'], $job['last_product_id'], $size);
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                return $job;
            }

            $batchData = $this->batchDataBuilder->buildBatchData($products);
            $feedSettings = ELeadsFeedDataBuilder::buildFeedSettings($this->settings, $this->currenciesEntity);
            $selectedFeatureIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_features'))));
            $selectedFeatureValueIds = array_values(array_unique(array_map('intval', (array) $this->settings->get('eleads__yml_feed__filter_options'))));
            [, $selectedCategorySet] = ELeadsFeedDataBuilder::buildExportCategories($this->categoriesEntity, $selectedCategoryIds);

            $offers = ELeadsOfferBuilder::buildOffers(
                $products,
                $batchData['product_categories'],
                $batchData['variants_by_product'],
                $batchData['images_by_product'],
                $batchData['brands_by_id'],
                $batchData['feature_map'],
                $selectedCategorySet,
                array_flip($selectedFeatureIds),
                array_flip($selectedFeatureValueIds),
                $feedSettings['currency_code'],
                (int) $feedSettings['picture_limit'],
                (string) $feedSettings['image_size'],
                $lang,
                (string) $this->settings->get('eleads__yml_feed__short_description_source'),
                $this->money,
                $this->config,
                (bool) $feedSettings['grouped_products']
            );

            $this->writer->appendOffers($lang, $offers, $langId);

            $processed = $job['processed'] + count($products);
            $lastProduct = end($products);
            $lastProductId = (int) ($lastProduct->id ?? $job['last_product_id']);
            $job = $this->jobStorage->updateProgress($lang, $processed, $lastProductId);

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            return $job;
        } catch (\Throwable $e) {
            if (is_resource($lockHandle)) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }

            return $this->jobStorage->markFailed($lang, $e->getMessage(), $job['processed'], $job['last_product_id']);
        }
    }

    private function acquireLockHandle(string $lang, bool $blocking)
    {
        $lockPath = $this->pathResolver->getLockPath($lang);
        $handle = fopen($lockPath, 'c');
        if ($handle === false) {
            return false;
        }

        $operation = LOCK_EX;
        if (!$blocking) {
            $operation |= LOCK_NB;
        }

        if (!flock($handle, $operation)) {
            fclose($handle);
            return false;
        }

        return $handle;
    }
}
