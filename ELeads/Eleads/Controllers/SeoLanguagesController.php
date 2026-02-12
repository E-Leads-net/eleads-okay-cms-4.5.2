<?php

namespace Okay\Modules\ELeads\Eleads\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\EntityFactory;
use Okay\Core\Request;
use Okay\Entities\LanguagesEntity;

class SeoLanguagesController extends AbstractController
{
    public function render(Request $request, EntityFactory $entityFactory)
    {
        if (!$request->method('GET')) {
            $this->respond(['error' => 'method_not_allowed'], 405);
            return;
        }

        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            $this->respond(['error' => 'api_key_missing'], 401);
            return;
        }

        $authHeader = $this->getAuthorizationHeader();
        if ($authHeader === null || stripos($authHeader, 'Bearer ') !== 0) {
            $this->respond(['error' => 'unauthorized'], 401);
            return;
        }

        $token = trim(substr($authHeader, 7));
        if (!hash_equals($apiKey, $token)) {
            $this->respond(['error' => 'unauthorized'], 401);
            return;
        }

        /** @var LanguagesEntity $languagesEntity */
        $languagesEntity = $entityFactory->get(LanguagesEntity::class);
        $languages = $languagesEntity->find();

        $items = [];
        foreach ($languages as $language) {
            $items[] = [
                'id' => (int) ($language->id ?? 0),
                'label' => (string) ($language->label ?? ''),
                'code' => (string) ($language->label ?? ''),
                'href_lang' => (string) ($language->href_lang ?? ''),
                'enabled' => (bool) (!empty($language->enabled)),
            ];
        }

        $this->respond([
            'status' => 'ok',
            'count' => count($items),
            'items' => $items,
        ]);
    }

    private function getAuthorizationHeader(): ?string
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }
        return null;
    }

    private function respond(array $payload, int $status = 200): void
    {
        $this->response->setStatusCode($status);
        $this->response->setContentType(RESPONSE_JSON);
        $this->response->setContent(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}

