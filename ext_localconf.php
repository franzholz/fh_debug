<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    if (!defined('FH_DEBUG_EXT')) {
        define('FH_DEBUG_EXT', 'fh_debug');
    }

    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get(FH_DEBUG_EXT);

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]) &&
        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT])
    ) {
        $tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT];
    } else {
        unset($tmpArray);
    }

    if (is_array($extensionConfiguration)) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT] = $extensionConfiguration;
        if (isset($tmpArray) && is_array($tmpArray)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT], $tmpArray);
        }
    }

    if (
        isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]) &&
        is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT])
    ) {
        $class = \JambageCom\FhDebug\Utility\DebugFunctions::class;
        if (
            !isset($GLOBALS['error']) ||
            !is_object($GLOBALS['error']) ||
            !($GLOBALS['error'] instanceof $class) ||
            !$GLOBALS['error']->hasBeenInitialized()
        ) {
            $myDebugObject = null;
            $newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT];

            if (
                !isset($GLOBALS['error']) ||
                !($GLOBALS['error'] instanceof $class)
            ) {
                $config = $newExtConf;
                $config['default'] = 1;
                $myConfiguratinVariant =
                    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        \JambageCom\FhDebug\Configuration\Variant::class,
                        $config
                    );
                // New operator used on purpose: This class is required early during
                // bootstrap before makeInstance() is properly set up
                $myDebugObject =
                    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        \JambageCom\FhDebug\Utility\DebugFunctions::class,
                        $newExtConf
                    );
                // The contructor contains important static initializations which are needed immediately.
            }
            $ipIsAllowed = false;
            $ipAdress = \JambageCom\FhDebug\Utility\DebugFunctions::initIpAddress($ipIsAllowed);
            $modeIsAllowed = \JambageCom\FhDebug\Utility\DebugFunctions::verifyTypo3Mode(TYPO3_MODE);
            $initResult = false;

            if (
                $modeIsAllowed &&
                (
                    $ipIsAllowed ||
                    \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP($ipAdress, '127.0.0.1') ||
                    TYPO3_MODE == 'FE' && $newExtConf['FEUSERNAMES']
                )
            ) {
                $GLOBALS['TYPO3_CONF_VARS']['LOG']['JambageCom']['FhDebug']['writerConfiguration'] = [
                    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                            'logFileInfix' => 'debug'
                        ]
                    ]
                ];

                if (
                    isset($GLOBALS['error']) &&
                    $GLOBALS['error'] instanceof $class
                ) {
                    $GLOBALS['error']->init($ipAdress);
                } else {
                    $initResult = \JambageCom\FhDebug\Utility\DebugFunctions::init($ipAdress);
                }

                if ($newExtConf['OOPS_AN_ERROR_OCCURRED']) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler::class] = [
                        'className' => \JambageCom\FhDebug\Hooks\ProductionExceptionHandler::class
                    ];
                }

                if ($newExtConf['DBAL']) {
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Doctrine\\DBAL\\DBALException'] = [
                        'className' => \JambageCom\FhDebug\Hooks\DBALException::class
                    ];
                }

                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tsparser']['configurationParser'][FH_DEBUG_EXT] = \JambageCom\FhDebug\Hooks\TsparserHooks::class;
            }

            if (
                is_object($myDebugObject) &&
                (
                    !isset($GLOBALS['error']) ||
                    !($GLOBALS['error'] instanceof $class)
                )
            ) {
            // the error object must always be set in order to show the debug output or to disable it
                $GLOBALS['error'] = $myDebugObject;
            }

            $logLevel = -1;
            if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($newExtConf['LOGLEVEL'])) {
                $logLevel = intval($newExtConf['LOGLEVEL']);
            }

            if (
                $logLevel >= \TYPO3\CMS\Core\Log\LogLevel::EMERGENCY &&
                $logLevel <= \TYPO3\CMS\Core\Log\LogLevel::DEBUG
            ) {
                $originalWriters = '';
                // configuration for DEBUG severity, including all
                // levels with higher severity (DEBUG, DEBUG, ERROR, CRITICAL, EMERGENCY)
                if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$logLevel])) {
                    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$logLevel] = [];
                }

                $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration']
                    [$logLevel][\JambageCom\FhDebug\Log\Writer\LogWriter::class] = [];
            }
        }
    }
});
