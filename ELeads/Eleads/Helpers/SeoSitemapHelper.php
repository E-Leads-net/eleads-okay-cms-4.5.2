<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Settings;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class SeoSitemapHelper
{
    private const SITEMAP_DIR = 'e-search';
    private const SITEMAP_FILE = 'sitemap.xml';

    private Settings $settings;
    private string $baseUrl;

    public function __construct(Settings $settings, string $baseUrl)
    {
        $this->settings = $settings;
        $this->baseUrl = rtrim($baseUrl, '/');
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

    public function addSlug(string $slug): bool
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

        if ($this->hasSlug($slug)) {
            return true;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $loc = $this->baseUrl . '/' . self::SITEMAP_DIR . '/' . rawurlencode($slug);
        $entry = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>' . PHP_EOL;

        if (strpos($content, '</urlset>') === false) {
            $content = $this->getSkeleton();
        }

        $content = str_replace('</urlset>', $entry . '</urlset>', $content);
        return file_put_contents($path, $content) !== false;
    }

    public function removeSlug(string $slug): bool
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

        $pattern = '~\\s*<url>\\s*<loc>[^<]*/'
            . preg_quote(self::SITEMAP_DIR, '~')
            . '/' . preg_quote($slug, '~')
            . '</loc>\\s*</url>\\s*~i';
        $newContent = preg_replace($pattern, PHP_EOL, $content);
        if ($newContent === null) {
            return false;
        }

        return file_put_contents($path, $newContent) !== false;
    }

    public function updateSlug(string $oldSlug, string $newSlug): bool
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);
        if ($oldSlug === '' || $newSlug === '') {
            return false;
        }

        $this->removeSlug($oldSlug);
        return $this->addSlug($newSlug);
    }

    public function hasSlug(string $slug): bool
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

        $pattern = '~<loc>[^<]*/' . preg_quote(self::SITEMAP_DIR, '~') . '/' . preg_quote($slug, '~') . '</loc>~i';
        return (bool) preg_match($pattern, $content);
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
     * @return string[]
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

        return array_values(array_filter(array_map('strval', $data['slugs'])));
    }

    /**
     * @param string[] $slugs
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

        foreach ($slugs as $slug) {
            $slug = trim($slug);
            if ($slug === '') {
                continue;
            }

            $loc = $this->baseUrl . '/' . self::SITEMAP_DIR . '/' . rawurlencode($slug);
            $lines[] = '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>';
        }

        $lines[] = '</urlset>';
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
