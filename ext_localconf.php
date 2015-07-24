<?php

if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

if (
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY])
) {
	$tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY];
} else {
	unset($tmpArray);
}

if (isset($_EXTCONF) && is_array($_EXTCONF)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY] = $_EXTCONF;
	if (isset($tmpArray) && is_array($tmpArray)) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY], $tmpArray);
	}
}


if (
	defined('TYPO3_version') &&
	version_compare(TYPO3_version, '6.0.0', '>=') &&
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY])
) {
	$newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY];

	if (
		!isset($GLOBALS['error']) ||
		!is_object($GLOBALS['error']) ||
		get_class($GLOBALS['error']) != 'JambageCom\\FhDebug\\Utility\\DebugFunctions'
	) {
		$myDebugObject = new \JambageCom\FhDebug\Utility\DebugFunctions($newExtConf);
		$bIpIsAllowed = FALSE;
		$ipAdress = \JambageCom\FhDebug\Utility\DebugFunctions::initIpAddress($bIpIsAllowed);
		$bModeIsAllowed = \JambageCom\FhDebug\Utility\DebugFunctions::verifyTypo3Mode(TYPO3_MODE);

		if (
			$bModeIsAllowed &&
			(
				$bIpIsAllowed ||
				\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($ipAdress, '127.0.0.1') ||
				TYPO3_MODE == 'FE' && $newExtConf['FEUSERNAMES']
			)
		) {
			\JambageCom\FhDebug\Utility\DebugFunctions::init($ipAdress);
		}

		// the error object must always be set in order to show the debug output or to disable it
		$GLOBALS['error'] = $myDebugObject;
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'][$_EXTKEY] = 'tx_fhdebug_hooks_em';


		if ($newExtConf['DEVLOG']) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'][$_EXTKEY] = 'JambageCom\\FhDebug\\Hooks\\CoreHooks->devLog';
		}
	}
}

