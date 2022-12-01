<?php

/***************************************************************
* Extension Manager/Repository config file for ext "fh_debug".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Debug generator and Log Writer',
    'description' => 'This generates a PHP debug output file. FileWriter into debug',
    'category' => 'misc',
    'version' => '0.12.1',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '10.4.0-12.1.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'tsparser' => '0.9.0-0.0.0',
        ],
    ],
];

