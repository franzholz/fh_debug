<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace JambageCom\FhDebug\Api;


use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\FhDebug\Configuration\Variant;
use JambageCom\FhDebug\Hooks\DBALException;
use JambageCom\FhDebug\Hooks\ProductionExceptionHandler;
use JambageCom\FhDebug\Hooks\TsparserHooks;
use JambageCom\FhDebug\Log\Writer\LogWriter;
use JambageCom\FhDebug\Utility\DebugFunctions;

/**
 * Components for the Debug
 */
class BootstrapApi
{
    /**
     * initialize the global debug object
     * @param ServerRequestInterface $request
     * @param it $requestEmpty
     * @return ResponseInterface
     */
    public function init (ServerRequestInterface $request, $requestEmpty = false)
    {
        $extensionKey = 'fh_debug';

        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get($extensionKey);

        $tmpArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] ?? null;

        if (is_array($extensionConfiguration)) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = $extensionConfiguration;
            if (isset($tmpArray) && is_array($tmpArray)) {
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey], $tmpArray);
            }
        }

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey])
        ) {
            $class = DebugFunctions::class;
            $currentTypo3Mode = (ApplicationType::fromRequest($request)->isFrontend() ? 'FE' : 'BE');

            if (
                !isset($GLOBALS['error']) ||
                !is_object($GLOBALS['error']) ||
                !($GLOBALS['error'] instanceof $class) ||
                !$GLOBALS['error']->hasBeenInitialized() ||
                $currentTypo3Mode != $GLOBALS['error']->getTypo3Mode() ||
                $requestEmpty
            ) {
                $myDebugObject = null;
                $newExtConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey];

                if (
                    !isset($GLOBALS['error']) ||
                    !($GLOBALS['error'] instanceof $class)
                ) {
                    $config = $newExtConf;
                    $config['default'] = 1;
                    $myConfiguratinVariant =
                        GeneralUtility::makeInstance(
                            Variant::class,
                            $config
                        );
                    // New operator used on purpose: This class is required early during
                    // bootstrap before makeInstance() is properly set up
                    $myDebugObject =
                        GeneralUtility::makeInstance(
                            DebugFunctions::class,
                            $newExtConf,
                            $currentTypo3Mode
                        );
                    // The contructor contains important static initializations which are needed immediately.
                }
                $ipIsAllowed = false;
                $ipAdress = DebugFunctions::initIpAddress($ipIsAllowed);
                $modeIsAllowed = DebugFunctions::verifyTypo3Mode($currentTypo3Mode);
                $modeIsAllowed = true; // ++++ TEST FHO
                $initResult = false;

                if (
                    $modeIsAllowed &&
                    (
                        $ipIsAllowed ||
                        GeneralUtility::cmpIP($ipAdress, '127.0.0.1') ||
                        $typo3Mode == 'FE' && $newExtConf['FEUSERNAMES']
                    )
                ) {
                    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JambageCom']['FhDebug']['writerConfiguration'] = [
                        LogLevel::DEBUG => [
                                FileWriter::class => [
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
                        $initResult = DebugFunctions::init($ipAdress);
                    }

                    if ($newExtConf['OOPS_AN_ERROR_OCCURRED']) {
                        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ProductionExceptionHandler::class] = [
                            'className' => ProductionExceptionHandler::class
                        ];
                    }

                    if ($newExtConf['DBAL']) {
                        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Doctrine\\DBAL\\DBALException'] = [
                            'className' => DBALException::class
                        ];
                    }

                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tsparser']['configurationParser'][$extensionKey] = TsparserHooks::class;
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
                if (MathUtility::canBeInterpretedAsInteger($newExtConf['LOGLEVEL'])) {
                    $logLevel = intval($newExtConf['LOGLEVEL']);
                }

                if (
                    $logLevel >= LogLevel::EMERGENCY &&
                    $logLevel <= LogLevel::DEBUG
                ) {
                    $originalWriters = '';
                    // configuration for DEBUG severity, including all
                    // levels with higher severity (DEBUG, DEBUG, ERROR, CRITICAL, EMERGENCY)
                    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$logLevel])) {
                        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$logLevel] = [];
                    }

                    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration']
                        [$logLevel][LogWriter::class] = [];
                }
            }
        }    
    }
}

