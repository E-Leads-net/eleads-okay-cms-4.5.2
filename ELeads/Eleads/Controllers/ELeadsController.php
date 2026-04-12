<?php


namespace Okay\Modules\ELeads\Eleads\Controllers;


use Okay\Controllers\AbstractController;
use Okay\Core\Languages;
use Okay\Core\Request;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsAccessGuard;
use Okay\Modules\ELeads\Eleads\Helpers\ELeadsFeedLanguageHelper;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedPathResolver;
use Okay\Modules\ELeads\Eleads\Helpers\FeedGeneration\ELeadsFeedWriter;

class ELeadsController extends AbstractController
{
    public function render(
        Languages $languages,
        Request $request,
        $lang
    ) {
        if (!ELeadsAccessGuard::allowFeed($this->settings, $request)) {
            $this->response->setStatusCode(401);
            $this->response->setContentType(RESPONSE_TEXT);
            $this->response->setContent('Forbidden');
            return;
        }

        $externalLang = ELeadsFeedLanguageHelper::normalizeExternalLanguage($lang, $languages);
        $pathResolver = new ELeadsFeedPathResolver($this->config);
        $writer = new ELeadsFeedWriter($pathResolver);
        $feedPath = $writer->getFinalFeedPath($externalLang);

        if (!is_file($feedPath)) {
            $this->response->setStatusCode(404);
            $this->response->setContentType(RESPONSE_TEXT);
            $this->response->setContent('Not Found');
            return;
        }

        $handle = fopen($feedPath, 'rb');
        if ($handle === false) {
            $this->response->setStatusCode(500);
            $this->response->setContentType(RESPONSE_TEXT);
            $this->response->setContent('Feed read failed');
            return;
        }

        $this->response->setContentType(RESPONSE_XML);
        $this->response->addHeader('Content-Length: ' . (string) filesize($feedPath));
        $this->response->sendHeaders();

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            $this->response->sendStream($chunk);
        }

        fclose($handle);
    }

}
