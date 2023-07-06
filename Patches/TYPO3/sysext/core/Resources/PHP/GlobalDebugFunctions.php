<?php


/* Replace the file typo3/sysext/core/Resources/PHP/GlobalDebugFunctions.php by this file!
 * Otherwise this debug extension will not work, because it is the only way to define a 
 * global function with a short name. 
 * 
 * Short-hand debug function
 * If you wish to use the debug()-function, and it does not output something,
 * please edit the dev IP mask in TYPO3_CONF_VARS
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
            file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Api/BootstrapApi.php') &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug') &&
            $group != 'init'
        ) {
            $request = getRequest();
            $api = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\FhDebug\Api\BootstrapApi::class);
            $api->init($request);
            if (isset($GLOBALS['error'])) {
                debug($variable, $title, 'init'); // use 'init' to prevent an endless loop
            }
        } else {
            \TYPO3\CMS\Core\Utility\DebugUtility::debug($variable, $title, $group);
        }
    }
    catch (\Exception $e) {
        // continue if an exception has been thrown
    }
}

function debugBegin (...$parameters)
{
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugBegin($parameters);
    }
}

function debugEnd (...$parameters)
{
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
        \JambageCom\Fhdebug\Utility\DebugFunctions::debugEnd($parameters);
    }
}
    
// use Psr\Http\Message\ServerRequestInterface;
function getRequest(): \Psr\Http\Message\ServerRequestInterface
{
    return $GLOBALS['TYPO3_REQUEST'];
}

