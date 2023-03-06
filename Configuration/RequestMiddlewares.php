<?php

return [
    'frontend' => [
        'jambagecom/fh-debug/preprocessing' => [
            'target' => \JambageCom\FhDebug\Middleware\Bootstrap::class,
            'description' => 'The global error object ($GLOBALS[\'error\']) must be set and initialized.',
            'after' => [
                'typo3/cms-frontend/tsfe'
            ],
        ]
    ]
];

