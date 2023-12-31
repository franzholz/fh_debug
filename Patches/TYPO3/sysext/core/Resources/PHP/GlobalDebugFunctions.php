<?php


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use JambageCom\FhDebug\Api\BootstrapApi;
use TYPO3\CMS\Core\Utility\DebugUtility;
use JambageCom\Fhdebug\Utility\DebugFunctions;
use TYPO3\CMS\Core\Http\ServerRequest;
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
    if (!GeneralUtility::cmpIP(
        GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
    )
    ) {
        return;
    }

    $requestEmpty = true;
    $request = getRequest($requestEmpty);

    try {
        $currentTypo3Mode = 'BE';
        if (
            !$requestEmpty &&
            $request instanceof ServerRequestInterface &&
            $request->getAttribute('applicationType')
        ) {
            $currentTypo3Mode = (ApplicationType::fromRequest($request)->isFrontend() ? 'FE' : 'BE');
        }

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
        } elseif (
            file_exists(ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Api/BootstrapApi.php') &&
            ExtensionManagementUtility::isLoaded('fh_debug')
        ) {
            $api = GeneralUtility::makeInstance(BootstrapApi::class);
            $api->init($request, $requestEmpty);

            if (isset($GLOBALS['error'])) {
                $GLOBALS['error']->debug($variable, $title, $group);
            }
        } else {
            DebugUtility::debug($variable, $title);
        }
    } catch (\Exception $e) {
        // error_log('debug Exception: ' .  $e->getMessage() . PHP_EOL, 3, 'mypath/fileadmin/phpDebugErrorLog.txt');

        // continue if an exception has been thrown
    }
}

function debugBegin(...$parameters)
{
    if (ExtensionManagementUtility::isLoaded('fh_debug')) {
        DebugFunctions::debugBegin();
    }
}

function debugEnd(...$parameters)
{
    if (ExtensionManagementUtility::isLoaded('fh_debug')) {
        DebugFunctions::debugEnd();
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
        $result = GeneralUtility::makeInstance(ServerRequest::class);
    }
    return $result;
}
