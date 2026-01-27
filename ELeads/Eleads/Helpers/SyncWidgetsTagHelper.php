<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Settings;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class SyncWidgetsTagHelper
{
    private const START_MARKER = '<!-- ELeads Widgets Loader Tag Start -->';
    private const END_MARKER = '<!-- ELeads Widgets Loader Tag End -->';

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function activate(): void
    {
        $templatePath = $this->getFooterTemplatePath();
        if ($templatePath === '') {
            return;
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            return;
        }

        if (strpos($content, self::START_MARKER) !== false) {
            return;
        }

        $tag = $this->fetchWidgetsTag();
        if ($tag === '') {
            return;
        }

        $block = self::START_MARKER . PHP_EOL . $tag . PHP_EOL . self::END_MARKER . PHP_EOL;

        if (stripos($content, '</body>') !== false) {
            $content = preg_replace('~</body>~i', $block . '</body>', $content, 1);
        } else {
            $content .= PHP_EOL . $block;
        }

        file_put_contents($templatePath, $content);
    }

    public function ensureInstalled(): void
    {
        $templatePath = $this->getFooterTemplatePath();
        if ($templatePath === '') {
            return;
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            return;
        }

        if (strpos($content, self::START_MARKER) !== false) {
            return;
        }

        $this->activate();
    }

    public function deactivate(): void
    {
        $templatePath = $this->getFooterTemplatePath();
        if ($templatePath === '') {
            return;
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            return;
        }

        $pattern = '~' . preg_quote(self::START_MARKER, '~') . '.*?' . preg_quote(self::END_MARKER, '~') . '\\s*~s';
        $updated = preg_replace($pattern, '', $content, -1, $count);
        if ($count === 0 || $updated === null) {
            return;
        }

        file_put_contents($templatePath, $updated);
    }

    private function fetchWidgetsTag(): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }

        curl_setopt($ch, CURLOPT_URL, ELeadsApiRoutes::WIDGETS_LOADER_TAG);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code < 200 || $code >= 300) {
            return '';
        }

        $response = trim($response);
        return $response;
    }

    private function getFooterTemplatePath(): string
    {
        $theme = (string) $this->settings->get('theme');
        $rootDir = dirname(__DIR__, 5);
        $designDirs = [
            $rootDir . '/design',
            $rootDir . '/app/design',
        ];

        foreach ($designDirs as $designDir) {
            if ($theme !== '') {
                $path = $designDir . '/' . $theme . '/html/index.tpl';
                if (is_file($path)) {
                    return $path;
                }
            }

            if (!is_dir($designDir)) {
                continue;
            }

            foreach (scandir($designDir) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $candidate = $designDir . '/' . $entry . '/html/index.tpl';
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return '';
    }
}
