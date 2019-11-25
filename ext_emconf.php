<?php

/***************************************************************
* Extension Manager/Repository config file for ext "fh_debug".
***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Debug generator and Devlog',
    'description' => 'This generates a PHP debug output file. And this is a sys_log and devLog data logger',
    'category' => 'misc',
    'version' => '0.8.1',
    'state' => 'stable',
    'uploadfolder' => '',
    'createDirs' => '',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => 'jambage.com',
    'constraints' => array(
        'depends' => array(
            'php' => '7.0.0-7.99.99',
            'typo3' => '8.7.0-9.5.99',
        ),
        'conflicts' => array(
        ),
        'suggests' => array(
        ),
    ),
);

