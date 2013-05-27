<?php

if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
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


if (
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT])
) {
	$newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT];

	if (
		!isset($GLOBALS['error']) ||
		!is_object($GLOBALS['error']) ||
		get_class($GLOBALS['error']) != 'tx_fhdebug'
	) {
		t3lib_div::requireOnce(t3lib_extMgm::extPath(FH_DEBUG_EXT) . 'lib/class.tx_fhdebug.php');
		$myDebugObject = new tx_fhdebug($newExtConf);
		$ipAdress = tx_fhdebug::readIpAddress();
// error_log ('ext_localconf $ipAdress = ' . $ipAdress . chr(13), 3, tx_fhdebug::getErrorLogFilename());

		$bIpIsAllowed = tx_fhdebug::verifyIpAddress($ipAdress, $newExtConf);
// error_log ('ext_localconf $bIpIsAllowed = ' . $bIpIsAllowed . chr(13), 3, tx_fhdebug::getErrorLogFilename());

		$bUsernameIsAllowed = FALSE;

		if ($bIpIsAllowed) {
			$devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
			if ($ipAdress == '*') {
				$devIPmask = '*';
			} else if ($ipAdress != '') {
				if ($devIPmask != '') {
					$devIPmask .= ',' . $ipAdress;
				} else {
					$devIPmask = $ipAdress;
				}
			}
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIPmask;
		}

		$bIsAllowed = tx_fhdebug::verifyDebugMode($newExtConf);
// error_log ('ext_localconf $bIsAllowed = ' . $bIsAllowed . chr(13), 3, tx_fhdebug::getErrorLogFilename());

		if (
			$bIsAllowed &&
			(
				$bIpIsAllowed ||
				t3lib_div::cmpIP($ipAdress, '127.0.0.1') ||
				TYPO3_MODE == 'FE' && $newExtConf['FEUSERNAMES']
			)
		) {
			tx_fhdebug::init($ipAdress);
		}

		// the error object must always be set in order to show the debug output or to disable it
		$GLOBALS['error'] = $myDebugObject;

		if (interface_exists('tx_em_Index_CheckDatabaseUpdatesHook')) {
			$typoVersion =
				class_exists('t3lib_utility_VersionNumber') ?
					t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) :
					t3lib_div::int_from_ver(TYPO3_version);

			if ($typoVersion >= 4005015) {
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'][] = 'tx_fhdebug_hooks_em';
			}
		}
	}
}

?>