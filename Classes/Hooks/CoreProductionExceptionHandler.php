<?php

namespace JambageCom\FhDebug\Hooks;

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

use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\FhDebug\Utility\DebugFunctions;


/**
 * Exception handler class for content object rendering
 */
class CoreProductionExceptionHandler extends \TYPO3\CMS\Core\Error\ProductionExceptionHandler
{
    /**
     * Echoes an exception for the web.
     *
     * @param \Throwable $exception The throwable object.
     */
    public function echoExceptionWeb(\Throwable $exception)
    {
        $maxCount = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]['TRACEDEPTH_EXCEPTION'];
        $trail = $exception->getTrace();

        $traceArray =
            DebugFunctions::getTraceArray(
                $trail,
                $maxCount,
                0
            );
        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);
        $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            $this->getTitle($exception),
            $this->getMessage($exception),
            AbstractMessage::ERROR,
            $this->discloseExceptionInformation($exception) ? $exception->getCode() : 0
        );

        debugBegin();
        debug ($traceArray, 'fh_debug exception handler exception trace'); // keep this
        debug ($exception->getFile(), 'fh_debug exception handler exception File'); // keep this
        debug ($exception->getLine(), 'fh_debug exception handler exception Line'); // keep this
        debug($content, 'CoreProductionExceptionHandler::echoExceptionWeb $content'); // keep this
        debugEnd();

        echo $content;
    }
}

