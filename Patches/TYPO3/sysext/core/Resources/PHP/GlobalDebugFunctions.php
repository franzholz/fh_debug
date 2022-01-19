<?php
/* You must use this file since TYPO3 9.5!
 * 
 * Short-hand debug function
 * If you wish to use the debug()-function, and it does not output something,
 * please edit the IP mask in TYPO3_CONF_VARS
 */

function debug($variable = '', $title = null, $group = null)
{
    if (!\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(
        \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
    )
    ) {
        return;
    }
    
    try {
        if (
            isset($GLOBALS['error']) &&
            is_object($GLOBALS['error']) &&
            @is_callable([$GLOBALS['error'], 'debug'])
        ) {
            $GLOBALS['error']->debug($variable, $title, $group);
        } else if (
            class_exists(\JambageCom\Fhdebug\Utility\DebugFunctions::class)
        ) {
            \JambageCom\Fhdebug\Utility\DebugFunctions::debug($variable, $title, $group);
        } else {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug($variable, $title, $group);
        }
    }
    catch (\Exception) {
        // continue if an exception has been thrown
    }
}

function debugBegin (...$parameters) {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugBegin($parameters);
    }
}

function debugEnd (...$parameters) {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugEnd($parameters);
    }
}

