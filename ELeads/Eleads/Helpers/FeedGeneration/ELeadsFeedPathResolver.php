<?php

namespace Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration;

use Okay\Core\Config;

class ELeadsFeedPathResolver
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function ensureStorageDirectory(): string
    {
        $directory = $this->config->get('root_dir') . 'files/cache/eleads/';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        return $directory;
    }

    public function getFinalFeedPath(string $lang): string
    {
        return $this->ensureStorageDirectory() . 'feed-' . $this->sanitizeLang($lang) . '.xml';
    }

    public function getTempFeedPath(string $lang): string
    {
        return $this->ensureStorageDirectory() . 'feed-' . $this->sanitizeLang($lang) . '.tmp.xml';
    }

    public function getJobPath(string $lang): string
    {
        return $this->ensureStorageDirectory() . 'feed-' . $this->sanitizeLang($lang) . '.job.json';
    }

    public function getLockPath(string $lang): string
    {
        return $this->ensureStorageDirectory() . 'feed-' . $this->sanitizeLang($lang) . '.lock';
    }

    private function sanitizeLang(string $lang): string
    {
        $lang = strtolower(trim($lang));
        $lang = preg_replace('/[^a-z]/', '', $lang);
        return $lang === '' ? 'default' : $lang;
    }
}
