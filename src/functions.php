<?php

function fhdebug($variable = '', $title = null, $group = null)
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
    catch (\Exception $e) {
        // continue if an exception has been thrown
    }
}

function fhdebugBegin (...$parameters) {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugBegin($parameters);
    }
}

function fhdebugEnd (...$parameters) {
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugEnd($parameters);
    }
}

