<?php


namespace Okay\Modules\ELeads\YmlFeed;


return [
    'ELeads_Yml_Feed' => [
        'slug' => 'eleads-yml/{$lang}.xml',
        'patterns' => [
            '{$lang}' => '([a-z]{2})',
        ],
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\ELeadsYmlFeedController',
            'method' => 'render',
        ],
    ],
];
