<?php

namespace Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration;

class ELeadsFeedJobStorage
{
    private ELeadsFeedPathResolver $pathResolver;

    public function __construct(ELeadsFeedPathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function get(string $lang): array
    {
        $jobPath = $this->pathResolver->getJobPath($lang);
        if (is_file($jobPath)) {
            $state = json_decode((string) file_get_contents($jobPath), true);
            if (is_array($state)) {
                return $this->normalizeState($lang, $state);
            }
        }

        $finalPath = $this->pathResolver->getFinalFeedPath($lang);
        if (is_file($finalPath)) {
            return $this->normalizeState($lang, [
                'status' => 'ready',
                'lang' => $lang,
                'processed' => 0,
                'batch_size' => 300,
                'last_product_id' => 0,
                'updated_at' => date('Y-m-d H:i:s', (int) filemtime($finalPath)),
                'finished_at' => date('Y-m-d H:i:s', (int) filemtime($finalPath)),
                'size' => (int) filesize($finalPath),
                'error' => '',
            ]);
        }

        return $this->normalizeState($lang, ['status' => 'idle', 'lang' => $lang]);
    }

    public function create(string $lang, int $batchSize): array
    {
        $state = $this->normalizeState($lang, [
            'status' => 'running',
            'lang' => $lang,
            'processed' => 0,
            'batch_size' => $batchSize,
            'last_product_id' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'finished_at' => '',
            'size' => 0,
            'error' => '',
        ]);

        $this->write($lang, $state);

        return $state;
    }

    public function updateProgress(string $lang, int $processed, int $lastProductId): array
    {
        $state = $this->get($lang);
        $state['status'] = 'running';
        $state['processed'] = $processed;
        $state['last_product_id'] = $lastProductId;
        $state['updated_at'] = date('Y-m-d H:i:s');
        $state['finished_at'] = '';
        $state['error'] = '';
        $state['size'] = 0;
        $this->write($lang, $state);

        return $state;
    }

    public function markReady(string $lang, int $processed, int $lastProductId, int $size): array
    {
        $state = $this->get($lang);
        $state['status'] = 'ready';
        $state['processed'] = $processed;
        $state['last_product_id'] = $lastProductId;
        $state['updated_at'] = date('Y-m-d H:i:s');
        $state['finished_at'] = $state['updated_at'];
        $state['size'] = $size;
        $state['error'] = '';
        $this->write($lang, $state);

        return $state;
    }

    public function markFailed(string $lang, string $error, ?int $processed = null, ?int $lastProductId = null): array
    {
        $state = $this->get($lang);
        $state['status'] = 'failed';
        $state['updated_at'] = date('Y-m-d H:i:s');
        $state['finished_at'] = $state['updated_at'];
        $state['error'] = $error;
        if ($processed !== null) {
            $state['processed'] = $processed;
        }
        if ($lastProductId !== null) {
            $state['last_product_id'] = $lastProductId;
        }
        $state['size'] = 0;
        $this->write($lang, $state);

        return $state;
    }

    public function clearTempState(string $lang): void
    {
        $jobPath = $this->pathResolver->getJobPath($lang);
        if (is_file($jobPath)) {
            unlink($jobPath);
        }
    }

    private function write(string $lang, array $state): void
    {
        $path = $this->pathResolver->getJobPath($lang);
        $tmpPath = $path . '.tmp';
        file_put_contents($tmpPath, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        rename($tmpPath, $path);
    }

    private function normalizeState(string $lang, array $state): array
    {
        return [
            'status' => (string) ($state['status'] ?? 'idle'),
            'lang' => (string) ($state['lang'] ?? $lang),
            'processed' => (int) ($state['processed'] ?? 0),
            'batch_size' => (int) ($state['batch_size'] ?? 300),
            'last_product_id' => (int) ($state['last_product_id'] ?? 0),
            'updated_at' => (string) ($state['updated_at'] ?? ''),
            'finished_at' => (string) ($state['finished_at'] ?? ''),
            'size' => (int) ($state['size'] ?? 0),
            'error' => (string) ($state['error'] ?? ''),
        ];
    }
}
