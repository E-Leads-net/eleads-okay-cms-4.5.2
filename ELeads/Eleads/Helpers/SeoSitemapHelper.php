<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Languages;
use Okay\Core\Settings;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class SeoSitemapHelper
{
    private const SITEMAP_DIR = 'e-search';
    private const SITEMAP_FILE = 'sitemap.xml';

    private Settings $settings;
    private ?Languages $languages;
    private string $baseUrl;

    public function __construct(Settings $settings, string $baseUrl, ?Languages $languages = null)
    {
        $this->settings = $settings;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->languages = $languages;
    }

    public function createSitemap(): void
    {
        $path = $this->getSitemapPath();
        if ($path === '') {
            return;
        }

        if (is_file($path)) {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        $slugs = $this->fetchSeoSlugs();
        $content = $this->buildSitemap($slugs);
        file_put_contents($path, $content);
    }

    public function removeSitemap(): void
    {
        $path = $this->getSitemapPath();
        if ($path === '' || !is_file($path)) {
            return;
        }

        @unlink($path);
    }

    public function addSlug(string $slug, ?string $language = null): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        $path = $this->getSitemapPath();
        if ($path === '') {
            return false;
        }

        $this->ensureSitemapExists($path);

        if ($this->hasSlug($slug, $language)) {
            return true;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $loc = $this->buildSlugUrl($slug, $language);
        $entry = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>' . PHP_EOL;

        if (strpos($content, '</urlset>') === false) {
            $content = $this->getSkeleton();
        }

        $content = str_replace('</urlset>', $entry . '</urlset>', $content);
        return file_put_contents($path, $content) !== false;
    }

    public function removeSlug(string $slug, ?string $language = null): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        $path = $this->getSitemapPath();
        if ($path === '' || !is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        if ($language !== null && trim($language) !== '') {
            $escapedLoc = preg_quote($this->buildSlugUrl($slug, $language), '~');
            $pattern = '~\\s*<url>\\s*<loc>' . $escapedLoc . '</loc>\\s*</url>\\s*~i';
        } else {
            $pattern = '~\\s*<url>\\s*<loc>[^<]*/'
                . preg_quote(self::SITEMAP_DIR, '~')
                . '/' . preg_quote(rawurlencode($slug), '~')
                . '</loc>\\s*</url>\\s*~i';
        }

        $newContent = preg_replace($pattern, PHP_EOL, $content);
        if ($newContent === null) {
            return false;
        }

        return file_put_contents($path, $newContent) !== false;
    }

    public function updateSlug(string $oldSlug, string $newSlug, ?string $oldLanguage = null, ?string $newLanguage = null): bool
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);
        if ($oldSlug === '' || $newSlug === '') {
            return false;
        }

        $this->removeSlug($oldSlug, $oldLanguage);
        return $this->addSlug($newSlug, $newLanguage ?? $oldLanguage);
    }

    public function hasSlug(string $slug, ?string $language = null): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        $path = $this->getSitemapPath();
        if ($path === '' || !is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        if ($language !== null && trim($language) !== '') {
            $escapedLoc = preg_quote($this->buildSlugUrl($slug, $language), '~');
            $pattern = '~<loc>' . $escapedLoc . '</loc>~i';
        } else {
            $pattern = '~<loc>[^<]*/'
                . preg_quote(self::SITEMAP_DIR, '~')
                . '/'
                . preg_quote(rawurlencode($slug), '~')
                . '</loc>~i';
        }
        return (bool) preg_match($pattern, $content);
    }

    public function getSlugUrl(string $slug, ?string $language = null): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        return $this->buildSlugUrl($slug, $language);
    }

    private function getSitemapPath(): string
    {
        $rootDir = dirname(__DIR__, 5);
        if ($rootDir === '' || !is_dir($rootDir)) {
            return '';
        }

        return $rootDir . '/' . self::SITEMAP_DIR . '/' . self::SITEMAP_FILE;
    }

    private function ensureSitemapExists(string $path): void
    {
        if (is_file($path)) {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }

        file_put_contents($path, $this->getSkeleton());
    }

    private function getSkeleton(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL
            . '</urlset>' . PHP_EOL;
    }

    /**
     * @return array<int, array{slug: string, lang: string}>
     */
    private function fetchSeoSlugs(): array
    {
        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return [];
        }

        $ch = curl_init();
        if ($ch === false) {
            return [];
        }

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, ELeadsApiRoutes::SEO_SLUGS);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code < 200 || $code >= 300) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['slugs']) || !is_array($data['slugs'])) {
            return [];
        }

        $result = [];
        foreach ($data['slugs'] as $item) {
            if (is_array($item)) {
                $slug = trim((string) ($item['slug'] ?? ''));
                $lang = trim((string) ($item['lang'] ?? ''));
            } else {
                $slug = trim((string) $item);
                $lang = '';
            }

            if ($slug === '') {
                continue;
            }

            $result[] = [
                'slug' => $slug,
                'lang' => $lang,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{slug: string, lang: string}> $slugs
     */
    private function buildSitemap(array $slugs): string
    {
        if ($slugs === []) {
            return $this->getSkeleton();
        }

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        $seen = [];
        foreach ($slugs as $item) {
            $slug = trim((string) ($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $loc = $this->buildSlugUrl($slug, (string) ($item['lang'] ?? ''));
            if (isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;

            $lines[] = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>';
        }

        $lines[] = '</urlset>';
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function buildSlugUrl(string $slug, ?string $apiLanguage = null): string
    {
        $languagePrefix = $this->resolveLanguagePrefix($apiLanguage);
        return $this->baseUrl
            . '/'
            . $languagePrefix
            . self::SITEMAP_DIR
            . '/'
            . rawurlencode($slug);
    }

    private function resolveLanguagePrefix(?string $apiLanguage): string
    {
        $apiLanguage = trim((string) $apiLanguage);
        if ($apiLanguage === '' || $this->languages === null) {
            return '';
        }

        $languages = $this->languages->getAllLanguages();
        if (empty($languages) || !is_array($languages)) {
            return '';
        }

        $requested = strtolower($apiLanguage);
        $labels = [];
        foreach ($languages as $language) {
            $label = strtolower((string) ($language->label ?? ''));
            if ($label !== '') {
                $labels[$label] = (int) ($language->id ?? 0);
            }
        }

        $label = $requested;
        if (!isset($labels[$label])) {
            if ($requested === 'uk' && isset($labels['ua'])) {
                $label = 'ua';
            } elseif ($requested === 'ua' && isset($labels['uk'])) {
                $label = 'uk';
            }
        }

        if (!isset($labels[$label])) {
            return '';
        }

        $langLink = $this->languages->getLangLink($labels[$label]);
        if ($langLink === false) {
            return '';
        }

        return (string) $langLink;
    }
}
