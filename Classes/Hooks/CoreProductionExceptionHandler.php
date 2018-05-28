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


/**
 * Exception handler class for content object rendering
 */
class CoreProductionExceptionHandler extends \TYPO3\CMS\Core\Error\ProductionExceptionHandler
{

    /**
     * Echoes an exception for the web.
     *
     * @param \Exception|\Throwable $exception The exception
     * @return void
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    public function echoExceptionWeb($exception)
    {
        $maxCount = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]['TRACEDEPTH_EXCEPTION'];
        $trail = $exception->getTrace();
        $traceArray =
            DebugFunctions::getTraceArray(
                $trail,
                $maxCount,
                0
            );
        debug ($traceArray, 'fh_debug exception handler exception trace'); // keep this

        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);
        $messageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\ErrorpageMessage::class,
            $this->getMessage($exception),
            $this->getTitle($exception)
        );
        debugBegin();
        debug($messageObj, 'CoreProductionExceptionHandler::echoExceptionWeb $messageObj'); // keep this
        debugEnd();

        $messageObj->output();
    }

    /**
     * Returns the title for the error message
     *
     * @param \Exception|\Throwable $exception Exception causing the error
     * @return string
     * @TODO #72293 This will change to \Throwable only if we are >= PHP7.0 only
     */
    protected function getTitle($exception)
    {
        if ($this->discloseExceptionInformation($exception) && method_exists($exception, 'getTitle') && $exception->getTitle() !== '') {
            return htmlspecialchars($exception->getTitle());
        } else {
            return $this->defaultTitle;
        }
    }
}

