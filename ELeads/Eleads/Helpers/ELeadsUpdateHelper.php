<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class ELeadsUpdateHelper
{
    private const TIMEOUT = 10;

    public static function getUpdateInfo(): array
    {
        $localVersion = self::getLocalVersion();
        $latestData = self::getLatestReleaseData();

        $latestVersion = $latestData['version'] ?? null;
        $downloadUrl = $latestData['download_url'] ?? null;
        $htmlUrl = $latestData['html_url'] ?? null;
        $error = $latestData['error'] ?? null;

        $updateAvailable = false;
        if (!empty($localVersion) && !empty($latestVersion)) {
            $updateAvailable = version_compare($latestVersion, $localVersion, '>');
        }

        return [
            'local_version' => $localVersion,
            'latest_version' => $latestVersion,
            'download_url' => $downloadUrl,
            'html_url' => $htmlUrl,
            'update_available' => $updateAvailable,
            'error' => $error,
        ];
    }

    public static function updateToLatest(): array
    {
        $latestData = self::getLatestReleaseData();
        if (!empty($latestData['error'])) {
            return ['ok' => false, 'message' => $latestData['error']];
        }

        $downloadUrl = $latestData['download_url'] ?? '';
        if ($downloadUrl === '') {
            return ['ok' => false, 'message' => 'Update package not found'];
        }

        return self::downloadAndReplace($downloadUrl);
    }

    private static function getLocalVersion(): string
    {
        $moduleJson = dirname(__DIR__) . '/Init/module.json';
        if (!is_file($moduleJson)) {
            return '';
        }

        $data = json_decode((string) file_get_contents($moduleJson), true);
        if (!is_array($data)) {
            return '';
        }

        return (string) ($data['version'] ?? '');
    }

    private static function getLatestReleaseData(): array
    {
        $release = self::requestJson(ELeadsApiRoutes::GITHUB_LATEST_RELEASE);
        if (!empty($release['tag_name']) || !empty($release['name'])) {
            $tag = (string) ($release['tag_name'] ?? $release['name']);
            $version = ltrim($tag, 'vV');
            return [
                'version' => $version,
                'download_url' => $release['zipball_url'] ?? ELeadsApiRoutes::githubZipballUrl($tag),
                'html_url' => $release['html_url'] ?? null,
            ];
        }

        $tags = self::requestJson(ELeadsApiRoutes::GITHUB_TAGS);
        if (is_array($tags) && !empty($tags[0]['name'])) {
            $tag = (string) $tags[0]['name'];
            $version = ltrim($tag, 'vV');
            return [
                'version' => $version,
                'download_url' => ELeadsApiRoutes::githubZipballUrl($tag),
                'html_url' => ELeadsApiRoutes::GITHUB_REPO_URL,
            ];
        }

        return ['error' => 'Unable to fetch latest version'];
    }

    private static function requestJson(string $url): array
    {
        $ch = curl_init();
        if ($ch === false) {
            return [];
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: ELeads-OkayCMS',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code < 200 || $code >= 300) {
            return [];
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : [];
    }

    private static function downloadAndReplace(string $url): array
    {
        $tmpBase = sys_get_temp_dir() . '/eleads_update_' . uniqid('', true);
        $zipPath = $tmpBase . '.zip';
        $extractDir = $tmpBase . '_extract';

        $fp = fopen($zipPath, 'wb');
        if ($fp === false) {
            return ['ok' => false, 'message' => 'Cannot create temp file'];
        }

        $ch = curl_init();
        if ($ch === false) {
            fclose($fp);
            return ['ok' => false, 'message' => 'Cannot init curl'];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ELeads-OkayCMS');

        $ok = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $code < 200 || $code >= 300) {
            return ['ok' => false, 'message' => 'Download failed'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'Cannot open zip'];
        }
        mkdir($extractDir, 0777, true);
        $zip->extractTo($extractDir);
        $zip->close();

        $moduleRoot = self::findModuleRoot($extractDir);
        if ($moduleRoot === '') {
            return ['ok' => false, 'message' => 'Module root not found in archive'];
        }

        $targetRoot = dirname(__DIR__);
        $copyOk = self::copyRecursive($moduleRoot, $targetRoot);

        self::cleanupTemp($zipPath, $extractDir);

        if (!$copyOk) {
            return ['ok' => false, 'message' => 'Failed to update files'];
        }

        $updatedVersion = self::getLocalVersion();
        if ($updatedVersion !== '') {
            self::updateInstalledVersion($updatedVersion);
        }

        return ['ok' => true, 'message' => 'Updated'];
    }

    private static function findModuleRoot(string $baseDir): string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'module.json' && basename($file->getPath()) === 'Init') {
                return dirname($file->getPath());
            }
        }
        return '';
    }

    private static function copyRecursive(string $src, string $dst): bool
    {
        if (!is_dir($src)) {
            return false;
        }
        if (!is_dir($dst) && !mkdir($dst, 0777, true) && !is_dir($dst)) {
            return false;
        }

        $items = scandir($src);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $from = $src . '/' . $item;
            $to = $dst . '/' . $item;
            if (is_dir($from)) {
                if (!self::copyRecursive($from, $to)) {
                    return false;
                }
            } else {
                if (!copy($from, $to)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function cleanupTemp(string $zipPath, string $extractDir): void
    {
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }
        if (is_dir($extractDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }
            @rmdir($extractDir);
        }
    }

    private static function updateInstalledVersion(string $version): void
    {
        try {
            $serviceLocator = \Okay\Core\ServiceLocator::getInstance();
            /** @var \Okay\Core\EntityFactory $entityFactory */
            $entityFactory = $serviceLocator->getService(\Okay\Core\EntityFactory::class);
            /** @var \Okay\Entities\ModulesEntity $modulesEntity */
            $modulesEntity = $entityFactory->get(\Okay\Entities\ModulesEntity::class);

            $module = $modulesEntity->getByVendorModuleName('ELeads', 'Eleads');
            if (!empty($module->id)) {
                $modulesEntity->update((int) $module->id, ['version' => $version]);
            }
        } catch (\Throwable $e) {
        }
    }
}
