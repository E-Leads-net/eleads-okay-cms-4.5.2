<?php


namespace Okay\Modules\ELeads\Eleads;


return [
    'ELeads_Yml_Feed' => [
        'slug' => 'eleads-yml/{$lang}.xml',
        'patterns' => [
            '{$lang}' => '([a-z]{2})',
        ],
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\ELeadsController',
            'method' => 'render',
        ],
    ],
    'ELeads_Seo_Sitemap_Sync' => [
        'slug' => 'e-search/api/sitemap-sync',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\SeoSitemapSyncController',
            'method' => 'render',
        ],
    ],
    'ELeads_Seo_Languages' => [
        'slug' => 'e-search/api/languages',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\SeoLanguagesController',
            'method' => 'render',
        ],
    ],
    'ELeads_Seo_Page' => [
        'slug' => 'e-search/{$slug}',
        'patterns' => [
            '{$slug}' => '([0-9A-Za-z\\-_]+)',
        ],
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\SeoPagesController',
            'method' => 'render',
        ],
    ],
];
