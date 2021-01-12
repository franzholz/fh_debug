<?php
/* Use this file only for TYPO3 9.5!
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
        if (is_object($GLOBALS['error']) && @is_callable([$GLOBALS['error'], 'debug'])) {
            $GLOBALS['error']->debug($variable, $title, $group);
        } else if (
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug') 
        ) {
            // allow debugging even if no code of fh_debug has been initialized yet
            if (!class_exists('\\JambageCom\\Fhdebug\\Utility\\DebugFunctions')) {
                $fhDebugFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Utility/DebugFunctions.php';
                require_once($fhDebugFile);
            }
            \JambageCom\Fhdebug\Utility\DebugFunctions::debug($variable, $title, $group);
        } else {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug($variable, $title, $group);
        }
    }
    catch (\Exception $e) {
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

