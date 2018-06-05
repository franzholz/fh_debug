<?php

if (!defined ('TYPO3_MODE')) {
    die('Access denied.');
}

define('FH_DEBUG_EXT', $_EXTKEY);

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
    $class = '\\JambageCom\\FhDebug\\Utility\\DebugFunctions';
    if (
        !isset($GLOBALS['error']) ||
        !is_object($GLOBALS['error']) ||
        !($GLOBALS['error'] instanceof $class)
    ) {
        $newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY];
        $myDebugObject = new \JambageCom\FhDebug\Utility\DebugFunctions($newExtConf);
        $ipIsAllowed = false;
        $ipAdress = \JambageCom\FhDebug\Utility\DebugFunctions::initIpAddress($ipIsAllowed);
        $modeIsAllowed = \JambageCom\FhDebug\Utility\DebugFunctions::verifyTypo3Mode(TYPO3_MODE);

        if (
            $modeIsAllowed &&
            (
                $ipIsAllowed ||
                \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($ipAdress, '127.0.0.1') ||
                TYPO3_MODE == 'FE' && $newExtConf['FEUSERNAMES']
            )
        ) {
            \JambageCom\FhDebug\Utility\DebugFunctions::init($ipAdress);

            if ($newExtConf['DEVLOG']) {
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['devLog'][$_EXTKEY] = 'JambageCom\\FhDebug\\Hooks\\CoreHooks->devLog';
            }

            if ($newExtConf['SYSLOG']) {
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'][$_EXTKEY] = 'JambageCom\\FhDebug\\Hooks\\CoreHooks->sysLog';
            }

            if ($newExtConf['OOPS_AN_ERROR_OCCURRED']) {
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\Exception\\ProductionExceptionHandler'] = array(
                    'className' => 'JambageCom\\FhDebug\\Hooks\\ProductionExceptionHandler',
                );
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Error\\ProductionExceptionHandler'] = array(
                    'className' => 'JambageCom\\FhDebug\\Hooks\\CoreProductionExceptionHandler',
                );
            }

            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patchem']['configurationItemLabel'][$_EXTKEY] = 'JambageCom\\FhDebug\\Hooks\\PatchemHooks';

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\Core\\ExtDirect\\ExtDirectRouter'] = array(
                'className' => 'JambageCom\\FhDebug\\Hooks\\ExtDirectRouter'
            );
        }

        // the error object must always be set in order to show the debug output or to disable it
        $GLOBALS['error'] = $myDebugObject;

// 		if (TYPO3_MODE === 'BE') {
// 			$signalSlotDispatcher =
// 				\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
// 			$signalSlotDispatcher->connect(
// 				'TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService',
// 				'hasInstalledExtensions',
// 				'JambageCom\\FhDebug\\Hooks\\EmListener',
// 				'executeOnSignal'
// 			);
// 		}
    }

}


