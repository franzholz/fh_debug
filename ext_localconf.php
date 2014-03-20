<?php

if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

if (!defined ('FH_DEBUG_EXT')) {
	define('FH_DEBUG_EXT', $_EXTKEY);
}

if (
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT])
) {
	$tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT];
} else {
	unset($tmpArray);
}

if (isset($_EXTCONF) && is_array($_EXTCONF)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT] = $_EXTCONF;
	if (isset($tmpArray) && is_array($tmpArray)) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT], $tmpArray);
	}
}

$typoVersion = 0;

$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility';
if (
	class_exists($callingClassName) &&
	method_exists($callingClassName, 'convertVersionNumberToInteger')
) {
	$typoVersion = call_user_func($callingClassName . '::convertVersionNumberToInteger', TYPO3_version);
} else if (
	class_exists('t3lib_utility_VersionNumber') &&
	method_exists('t3lib_utility_VersionNumber', 'convertVersionNumberToInteger')
) {
	$typoVersion = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
}

if (
	$typoVersion >= 6000000 &&
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT])
) {
	$newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT];

	if (
		!isset($GLOBALS['error']) ||
		!is_object($GLOBALS['error']) ||
		get_class($GLOBALS['error']) != 'JambageCom\\FhDebug\\Utility\\DebugFunctions'
	) {
// 		t3lib_div::requireOnce(t3lib_extMgm::extPath(FH_DEBUG_EXT) . 'Classes/Utility/DebugFunctions.php');
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


unset($typoVersion);

?>
