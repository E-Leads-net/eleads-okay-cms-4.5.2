<?php

namespace Okay\Modules\ELeads\Eleads\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\Request;
use Okay\Modules\ELeads\Eleads\Helpers\SeoSitemapHelper;

class SeoSitemapSyncController extends AbstractController
{
    public function render(Request $request)
    {
        if (!$request->method('POST')) {
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

        $payload = $this->getRequestPayload($request);
        $action = strtolower((string) ($payload['action'] ?? ''));
        $slug = trim((string) ($payload['slug'] ?? ''));
        $newSlug = trim((string) ($payload['new_slug'] ?? ''));

        if ($action === '' || $slug === '') {
            $this->respond(['error' => 'invalid_payload'], 422);
            return;
        }

        $sitemapHelper = new SeoSitemapHelper($this->settings, Request::getRootUrl());
        $result = false;

        if ($action === 'create') {
            $result = $sitemapHelper->addSlug($slug);
        } elseif ($action === 'delete') {
            $result = $sitemapHelper->removeSlug($slug);
        } elseif ($action === 'update') {
            if ($newSlug === '') {
                $this->respond(['error' => 'invalid_payload'], 422);
                return;
            }
            $result = $sitemapHelper->updateSlug($slug, $newSlug);
        } else {
            $this->respond(['error' => 'invalid_action'], 422);
            return;
        }

        if (!$result) {
            $this->respond(['error' => 'sitemap_update_failed'], 500);
            return;
        }

        $this->respond(['status' => 'ok']);
    }

    private function getRequestPayload(Request $request): array
    {
        $payload = $request->post();
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
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
