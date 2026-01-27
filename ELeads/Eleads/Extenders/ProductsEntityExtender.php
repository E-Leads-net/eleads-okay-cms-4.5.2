<?php


namespace Okay\Modules\ELeads\Eleads\Extenders;


use Okay\Core\ServiceLocator;
use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\Languages;
use Okay\Core\Money;
use Okay\Core\Router;
use Okay\Core\Settings;
use Okay\Helpers\ProductsHelper;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsSyncService;
use Okay\Modules\ELeads\Eleads\Helpers\SyncApiClient;
use Okay\Modules\ELeads\Eleads\Helpers\SyncLanguageResolver;
use Okay\Modules\ELeads\Eleads\Helpers\SyncPayloadBuilder;
use Okay\Core\Modules\Extender\ExtensionInterface;

class ProductsEntityExtender implements ExtensionInterface
{
    public function afterAdd($result, $object): void
    {
        $serviceLocator = ServiceLocator::getInstance();

        $productId = (int) $result;
        if ($productId <= 0) {
            return;
        }

        $syncService = $this->buildSyncService($serviceLocator);
        if ($syncService === null) {
            return;
        }

        $syncService->syncProductCreated($productId);
    }

    public function afterUpdate($result, $ids, $object): void
    {
        $ids = (array) $ids;
        $serviceLocator = ServiceLocator::getInstance();

        if (!$result) {
            return;
        }

        if (empty($ids)) {
            return;
        }

        $syncService = $this->buildSyncService($serviceLocator);
        if ($syncService === null) {
            return;
        }

        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }
            $syncService->syncProductUpdated($productId);
        }
    }

    public function afterDelete($result, $ids): void
    {
        $ids = (array) $ids;
        $serviceLocator = ServiceLocator::getInstance();

        if (!$result || empty($ids)) {
            return;
        }

        $syncService = $this->buildSyncService($serviceLocator);
        if ($syncService === null) {
            return;
        }

        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }
            $syncService->syncProductDeleted($productId);
        }
    }

    private function buildSyncService(ServiceLocator $serviceLocator): ?ELeadsSyncService
    {
        try {
            $settings = $serviceLocator->getService(Settings::class);
            $syncEnabled = $settings->get('eleads__sync_enabled');
            if (empty($syncEnabled)) {
                return null;
            }

            $apiKey = trim((string) $settings->get('eleads__api_key'));
            if ($apiKey === '') {
                return null;
            }

            $languageResolver = new SyncLanguageResolver(
                $serviceLocator->getService(Languages::class)
            );

            $payloadBuilder = new SyncPayloadBuilder(
                $settings,
                $serviceLocator->getService(EntityFactory::class),
                $serviceLocator->getService(ProductsHelper::class),
                $serviceLocator->getService(Money::class),
                $serviceLocator->getService(Config::class),
                $serviceLocator->getService(Router::class),
                $languageResolver
            );

            $apiClient = new SyncApiClient();

            return new ELeadsSyncService(
                $settings,
                $payloadBuilder,
                $apiClient,
                $languageResolver
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
