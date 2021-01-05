<?php

/***************************************************************
* Extension Manager/Repository config file for ext "fh_debug".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Debug generator and Log Writer',
    'description' => 'This generates a PHP debug output file. And this is a sys_log and LogLevel::DEBUG writer',
    'category' => 'misc',
    'version' => '0.9.0',
    'state' => 'stable',
    'uploadfolder' => '',
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.3.99',
            'typo3' => '8.7.0-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];

