<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Request;
use Okay\Core\Settings;

class ELeadsAccessGuard
{
    public static function allowFeed(Settings $settings, Request $request): bool
    {
        $accessKey = (string) $settings->get('eleads__yml_feed__access_key');
        if ($accessKey === '') {
            return true;
        }

        $requestKey = (string) $request->get('key');
        return $requestKey === $accessKey;
    }
}
