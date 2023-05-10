<?php

return [
    'frontend' => [
        'jambagecom/fh-debug/preprocessing' => [
            'target' => \JambageCom\FhDebug\Middleware\Bootstrap::class,
            'description' => 'The global error object ($GLOBALS[\'error\']) must be set and initialized.',
            'before' => [
                'typo3/cms-backend/locked-backend'
            ],
        ]
    ]
];
