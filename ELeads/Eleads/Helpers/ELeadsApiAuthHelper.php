<?php

namespace Okay\Modules\ELeads\Eleads\Helpers;

use Okay\Core\Settings;

class ELeadsApiAuthHelper
{
    public static function validate(Settings $settings): ?string
    {
        $apiKey = trim((string) $settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return 'api_key_missing';
        }

        $authHeader = self::getAuthorizationHeader();
        if ($authHeader === null || stripos($authHeader, 'Bearer ') !== 0) {
            return 'unauthorized';
        }

        $token = trim(substr($authHeader, 7));
        if (!hash_equals($apiKey, $token)) {
            return 'unauthorized';
        }

        return null;
    }

    private static function getAuthorizationHeader(): ?string
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
}
