<?php


namespace Okay\Modules\ELeads\Eleads\Config;


class ELeadsApiRoutes
{
    public const TOKEN_STATUS = 'https://stage-dashboard.e-leads.net/api/ecommerce/token/status';
    public const ECOMMERCE_ITEMS = 'https://stage-dashboard.e-leads.net/api/ecommerce/items';
    public const GITHUB_REPO = 'E-Leads-net/eleads-okay-cms-4.5.2';
    public const GITHUB_REPO_URL = 'https://github.com/' . self::GITHUB_REPO;
    public const GITHUB_API_BASE = 'https://api.github.com/repos/' . self::GITHUB_REPO;
    public const GITHUB_LATEST_RELEASE = self::GITHUB_API_BASE . '/releases/latest';
    public const GITHUB_TAGS = self::GITHUB_API_BASE . '/tags';
    
    public static function githubZipballUrl(string $ref): string
    {
        return self::GITHUB_API_BASE . '/zipball/' . $ref;
    }

    public static function ecommerceItemsUpdateUrl(string $externalId): string
    {
        return rtrim(self::ECOMMERCE_ITEMS, '/') . '/' . rawurlencode($externalId);
    }
}
