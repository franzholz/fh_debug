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
	'_md5_values_when_last_written' => 'a:10:{s:9:"ChangeLog";s:4:"aea7";s:16:"ext_autoload.php";s:4:"eeac";s:21:"ext_conf_template.txt";s:4:"7833";s:12:"ext_icon.gif";s:4:"fd3c";s:17:"ext_localconf.php";s:4:"a9dd";s:14:"ext_tables.sql";s:4:"d41d";s:14:"doc/manual.sxw";s:4:"abd7";s:35:"hooks/class.tx_fhdebug_hooks_em.php";s:4:"e20c";s:24:"lib/class.tx_fhdebug.php";s:4:"f79f";s:15:"res/fhdebug.css";s:4:"1c94";}',
	'suggests' => array(
	),
);

