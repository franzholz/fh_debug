<?php

/***************************************************************
* Extension Manager/Repository config file for ext "fh_debug".
***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Debug generator and Log Writer',
    'description' => 'This generates a PHP debug output file. FileWriter into debug',
    'category' => 'misc',
    'version' => '0.17.0',
    'state' => 'stable',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'tsparser' => '0.9.0-0.0.0',
        ],
    ],
];
