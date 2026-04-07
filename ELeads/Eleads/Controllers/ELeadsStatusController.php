<?php

namespace Okay\Modules\ELeads\Eleads\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\Config;
use Okay\Core\Database;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\QueryFactory;
use Okay\Core\Request;
use Okay\Entities\BrandsEntity;
use Okay\Entities\CategoriesEntity;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\ImagesEntity;
use Okay\Entities\LanguagesEntity;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsApiAuthHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedLanguageHelper;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedBatchDataBuilder;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedBatchProcessor;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedJobStorage;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedPathResolver;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedWriter;

class ELeadsStatusController extends AbstractController
{
    public function render(
        Request $request,
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
        QueryFactory $queryFactory,
        Database $db,
        Config $config
    ) {
        if (!$request->method('GET')) {
            $this->respond(['error' => 'method_not_allowed'], 405);
            return;
        }

        $authError = ELeadsApiAuthHelper::validate($this->settings);
        if ($authError !== null) {
            $this->respond(['error' => $authError], 401);
            return;
        }

        $externalLang = ELeadsFeedLanguageHelper::normalizeExternalLanguage(
            $request->get('lang', 'string'),
            $languages
        );

        try {
            $pathResolver = new ELeadsFeedPathResolver($config);
            $jobStorage = new ELeadsFeedJobStorage($pathResolver);
            $writer = new ELeadsFeedWriter($pathResolver);
            $batchDataBuilder = new ELeadsFeedBatchDataBuilder(
                $queryFactory,
                $db,
                $productsEntity,
                $categoriesEntity,
                $variantsEntity,
                $imagesEntity,
                $brandsEntity,
                $productsHelper
            );
            $processor = new ELeadsFeedBatchProcessor(
                $this->settings,
                $config,
                $languages,
                $languagesEntity,
                $categoriesEntity,
                $currenciesEntity,
                $productsEntity,
                $variantsEntity,
                $imagesEntity,
                $brandsEntity,
                $productsHelper,
                $money,
                $jobStorage,
                $writer,
                $batchDataBuilder,
                $pathResolver
            );

            $state = $processor->processNextBatch($externalLang);
            $this->respond($state);
        } catch (\Throwable $e) {
            $this->respond(['error' => 'generation_failed'], 500);
        }
    }

    private function respond(array $payload, int $status = 200): void
    {
        $this->response->setStatusCode($status);
        $this->response->setContentType(RESPONSE_JSON);
        $this->response->setContent(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
