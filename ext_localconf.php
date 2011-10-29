<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

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

	$dbgMode = ($newExtConf['TYPO3_MODE'] ? strtoupper($newExtConf['TYPO3_MODE']) : 'OFF');
	$ipAdress = t3lib_div::getIndpEnv('REMOTE_ADDR');

	if (
		t3lib_div::cmpIP(
			$ipAdress,
			$newExtConf['IPADDRESS']
		) &&
		(TYPO3_MODE == $dbgMode || $dbgMode == 'ALL')
	) {
		include_once (t3lib_extMgm::extPath(FH_DEBUG_EXTkey) . 'lib/class.tx_fhdebug.php');

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] .= ',' . $ipAdress; // overwrite the devIPmask

		if (!isset($GLOBALS['error']) || !is_object($GLOBALS['error']) || get_class($GLOBALS['error']) != 'tx_fhdebug') {
			$myDebugObject = new tx_fhdebug($newExtConf);

			if (!$newExtConf['DEBUGBEGIN']) {
				tx_fhdebug::init();
			}
			$GLOBALS['error'] = $myDebugObject;
		}
	}
}


?>