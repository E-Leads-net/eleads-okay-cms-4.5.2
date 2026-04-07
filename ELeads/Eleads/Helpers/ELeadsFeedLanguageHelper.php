<?php

namespace Okay\Modules\ELeads\Eleads\Helpers;

use Okay\Core\Languages;

class ELeadsFeedLanguageHelper
{
    public static function normalizeExternalLanguage(?string $lang, Languages $languages): string
    {
        $lang = strtolower(trim((string) $lang));
        if ($lang === '') {
            $mainLanguage = $languages->getMainLanguage();
            $lang = strtolower((string) ($mainLanguage->label ?? ''));
        }

        if ($lang === 'ua') {
            return 'uk';
        }

        return $lang;
    }

    public static function toStoreLanguageLabel(string $externalLang): string
    {
        $externalLang = strtolower(trim($externalLang));
        if ($externalLang === 'uk') {
            return 'ua';
        }

        return $externalLang;
    }
}
