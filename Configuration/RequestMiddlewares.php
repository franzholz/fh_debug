<?php

use JambageCom\FhDebug\Middleware\Bootstrap;
return [
    'frontend' => [
        'jambagecom/fh-debug/preprocessing' => [
            'target' => Bootstrap::class,
            'description' => 'The global error object ($GLOBALS[\'error\']) must be set and initialized.',
            'before' => [
                'typo3/cms-backend/locked-backend'
            ],
        ]
    ]
];
