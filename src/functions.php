<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use JambageCom\Fhdebug\Utility\DebugFunctions;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
function fhdebug($variable = '', $title = null, $group = null)
{
    if (!GeneralUtility::cmpIP(
        GeneralUtility::getIndpEnv('REMOTE_ADDR'),
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
        } elseif (
            class_exists(DebugFunctions::class)
        ) {
            DebugFunctions::debug($variable, $title, $group);
        } else {
            DebugUtility::debug($variable, $title);
        }
    } catch (\Exception $e) {
        // continue if an exception has been thrown
    }
}

function fhdebugBegin(...$parameters)
{
    if (ExtensionManagementUtility::isLoaded('fh_debug')) {
        DebugFunctions::debugBegin();
    }
}

function fhdebugEnd(...$parameters)
{
    if (ExtensionManagementUtility::isLoaded('fh_debug')) {
        DebugFunctions::debugEnd();
    }
}
