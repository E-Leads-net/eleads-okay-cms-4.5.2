<?php


namespace Okay\Modules\ELeads\Eleads\Config;


class ELeadsApiRoutes
{
    public const TOKEN_STATUS = 'https://dashboard.e-leads.net/api/ecommerce/token/status';
    public const ECOMMERCE_ITEMS = 'https://dashboard.e-leads.net/api/ecommerce/items';
    public const SEO_SLUGS = 'https://dashboard.e-leads.net/api/seo/slugs';
    public const SEO_PAGES = 'https://dashboard.e-leads.net/api/seo/pages';
    public const WIDGETS_LOADER_TAG = 'https://api.e-leads.net/v1/widgets-loader-tag';
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

    public static function seoPageUrl(string $slug, ?string $lang = null): string
    {
        $url = rtrim(self::SEO_PAGES, '/') . '/' . rawurlencode($slug);
        if ($lang !== null && trim($lang) !== '') {
            $url .= '?lang=' . rawurlencode(trim($lang));
        }
        return $url;
    }
}
