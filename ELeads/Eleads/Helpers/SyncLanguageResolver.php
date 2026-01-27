<?php


namespace Okay\Modules\ELeads\Eleads\Helpers;


use Okay\Core\Languages;

class SyncLanguageResolver
{
    private Languages $languages;

    public function __construct(Languages $languages)
    {
        $this->languages = $languages;
    }

    public function resolve(): array
    {
        $langId = $this->languages->getLangId();
        $languageLabel = $this->languages->getLangLabel($langId);
        $payloadLanguage = $languageLabel === 'ua' ? 'uk' : $languageLabel;

        return [$langId, $languageLabel, $payloadLanguage];
    }
}
