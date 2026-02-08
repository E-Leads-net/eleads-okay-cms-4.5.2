<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Settings;
use Okay\Modules\ELeads\Eleads\Config\ELeadsApiRoutes;

class SeoPagesApiHelper
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPage(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $apiKey = trim((string) $this->settings->get('eleads__api_key'));
        if ($apiKey === '') {
            return null;
        }

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, ELeadsApiRoutes::seoPageUrl($slug));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);

        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code < 200 || $code >= 300) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['page']) || !is_array($data['page'])) {
            return null;
        }

        return $data['page'];
    }
}
