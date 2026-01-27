<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Settings;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class ELeadsSyncService
{
    private Settings $settings;
    private SyncPayloadBuilder $payloadBuilder;
    private SyncApiClient $apiClient;
    private SyncLanguageResolver $languageResolver;

    public function __construct(
        Settings $settings,
        SyncPayloadBuilder $payloadBuilder,
        SyncApiClient $apiClient,
        SyncLanguageResolver $languageResolver
    ) {
        $this->settings = $settings;
        $this->payloadBuilder = $payloadBuilder;
        $this->apiClient = $apiClient;
        $this->languageResolver = $languageResolver;
    }

    public function syncProductUpdated(int $productId): void
    {
        if (!$this->isSyncEnabled()) {
            return;
        }

        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return;
        }

        try {
            $payload = $this->payloadBuilder->buildPayload($productId);
        } catch (\Throwable $e) {
            return;
        }
        if ($payload === null) {
            return;
        }

        $this->apiClient->send(
            ELeadsApiRoutes::ecommerceItemsUpdateUrl((string) $productId),
            'PUT',
            $payload,
            $apiKey
        );
    }

    public function syncProductCreated(int $productId): void
    {
        if (!$this->isSyncEnabled()) {
            return;
        }

        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return;
        }

        try {
            $payload = $this->payloadBuilder->buildPayload($productId);
        } catch (\Throwable $e) {
            return;
        }
        if ($payload === null) {
            return;
        }

        $this->apiClient->send(
            rtrim(ELeadsApiRoutes::ECOMMERCE_ITEMS, '/'),
            'POST',
            $payload,
            $apiKey
        );
    }

    public function syncProductDeleted(int $productId): void
    {
        if (!$this->isSyncEnabled()) {
            return;
        }

        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return;
        }

        [, , $payloadLanguage] = $this->languageResolver->resolve();

        $this->apiClient->send(
            ELeadsApiRoutes::ecommerceItemsUpdateUrl((string) $productId),
            'DELETE',
            ['language' => $payloadLanguage],
            $apiKey
        );
    }

    private function isSyncEnabled(): bool
    {
        $value = $this->settings->get('eleads__sync_enabled');
        return !empty($value);
    }
}
