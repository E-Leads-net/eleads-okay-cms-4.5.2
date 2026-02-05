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

        $settings = $this->getSettings($serviceLocator);
        if ($settings === null) {
            return;
        }

        if (!$this->isSyncEnabled($settings)) {
            return;
        }

        $apiKey = trim((string) $settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return;
        }

        $pending = $this->getPendingCreates($settings);
        $pending[$productId] = true;
        $this->savePendingCreates($settings, $pending);
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

        $settings = $this->getSettings($serviceLocator);
        if ($settings === null) {
            return;
        }

        if (!$this->isSyncEnabled($settings)) {
            return;
        }

        $syncService = $this->buildSyncService($serviceLocator);
        if ($syncService === null) {
            return;
        }

        $pending = $this->getPendingCreates($settings);
        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }
            if (isset($pending[$productId])) {
                $created = $syncService->syncProductCreated($productId);
                if ($created) {
                    unset($pending[$productId]);
                }
                continue;
            }
            $syncService->syncProductUpdated($productId);
        }
        $this->savePendingCreates($settings, $pending);
    }

    public function afterDelete($result, $ids): void
    {
        $ids = (array) $ids;
        $serviceLocator = ServiceLocator::getInstance();

        if (!$result || empty($ids)) {
            return;
        }

        $settings = $this->getSettings($serviceLocator);
        if ($settings === null) {
            return;
        }

        if (!$this->isSyncEnabled($settings)) {
            return;
        }

        $syncService = $this->buildSyncService($serviceLocator);
        if ($syncService === null) {
            return;
        }

        $pending = $this->getPendingCreates($settings);
        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }
            if (isset($pending[$productId])) {
                unset($pending[$productId]);
                continue;
            }
            $syncService->syncProductDeleted($productId);
        }
        $this->savePendingCreates($settings, $pending);
    }

    private function buildSyncService(ServiceLocator $serviceLocator): ?ELeadsSyncService
    {
        try {
            $settings = $this->getSettings($serviceLocator);
            if ($settings === null) {
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

    private function getSettings(ServiceLocator $serviceLocator): ?Settings
    {
        try {
            return $serviceLocator->getService(Settings::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isSyncEnabled(Settings $settings): bool
    {
        return !empty($settings->get('eleads__sync_enabled'));
    }

    private function getPendingCreates(Settings $settings): array
    {
        $pending = (array) $settings->get('eleads__sync_pending_create');
        $result = [];
        foreach ($pending as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $result[$id] = true;
            }
        }
        return $result;
    }

    private function savePendingCreates(Settings $settings, array $pending): void
    {
        $settings->set('eleads__sync_pending_create', array_values(array_map('intval', array_keys($pending))));
    }
}
