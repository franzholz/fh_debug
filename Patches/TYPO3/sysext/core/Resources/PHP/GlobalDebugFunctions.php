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

    $requestEmpty = true;
    $request = getRequest($requestEmpty);

    try {
        $currentTypo3Mode = (\TYPO3\CMS\Core\Http\ApplicationType::fromRequest($request)->isFrontend() ? 'FE' : 'BE');

        if (
            isset($GLOBALS['error']) &&
            is_object($GLOBALS['error']) &&
            @is_callable([$GLOBALS['error'], 'debug']) &&
            @is_callable([$GLOBALS['error'], 'getTypo3Mode']) &&
            (
                $currentTypo3Mode == $GLOBALS['error']->getTypo3Mode() &&
                !$requestEmpty
            )
        ) {
            $GLOBALS['error']->debug($variable, $title, $group);
        } else if (
            file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Api/BootstrapApi.php') &&
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')
        ) {
            $api = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\FhDebug\Api\BootstrapApi::class);
            $api->init($request, $requestEmpty);
            if (isset($GLOBALS['error'])) {
                $GLOBALS['error']->debug($variable, $title, $group);
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
function getRequest(&$empty): \Psr\Http\Message\ServerRequestInterface
{
    $empty = true;
    if (isset($GLOBALS['TYPO3_REQUEST'])) {
        $result = $GLOBALS['TYPO3_REQUEST'];
        $empty = false;
    } else {
        $result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\ServerRequest::class);
    }
    return $result;
}

