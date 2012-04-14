<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}

$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here:

if (!defined ('FH_DEBUG_EXTkey')) {
	define('FH_DEBUG_EXTkey', $_EXTKEY);
}

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey]) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey])) {
	$tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey];
} else {
	unset($tmpArray);
}

if (isset($_EXTCONF) && is_array($_EXTCONF)) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey] = $_EXTCONF;
	if (isset($tmpArray) && is_array($tmpArray)) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey], $tmpArray);
	}
}


if (
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey])
) {
	$newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXTkey];

	if (
		!isset($GLOBALS['error']) ||
		!is_object($GLOBALS['error']) ||
		get_class($GLOBALS['error']) != 'tx_fhdebug'
	) {
		include_once(t3lib_extMgm::extPath(FH_DEBUG_EXTkey) . 'lib/class.tx_fhdebug.php');
		$myDebugObject = new tx_fhdebug($newExtConf);

		$ipAdress = tx_fhdebug::readIpAddress();
		$bIpIsAllowed = tx_fhdebug::verifyIpAddress($ipAdress, $newExtConf);

		if ($bIpIsAllowed) {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] .= ',' . $ipAdress; // overwrite the devIPmask
		}

		if (
			$bIpIsAllowed ||
			t3lib_div::cmpIP($ipAdress, '127.0.0.1')
		) {
			tx_fhdebug::init($ipAdress);
		}

		// the error object must always be set in order to show the debug output or to disable it
		$GLOBALS['error'] = $myDebugObject;
	}
}

?>