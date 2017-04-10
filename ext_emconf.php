<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fh_debug".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Debug and Devlog',
	'description' => 'PHP debugger, sys_log and devLog Logger',
	'category' => 'misc',
	'shy' => 0,
	'version' => '0.5.7',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => '',
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-7.99.99',
			'typo3' => '6.0.0-8.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

