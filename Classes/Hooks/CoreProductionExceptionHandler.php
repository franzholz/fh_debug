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
error_log('ProductionExceptionHandler::echoExceptionWeb' . PHP_EOL, 3, \JambageCom\Fhdebug\Utility\DebugFunctions::getErrorLogFilename());

        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);
        $messageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\ErrorpageMessage::class,
            $this->getMessage($exception),
            $this->getTitle($exception)
        );
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
        $backtrace = \JambageCom\Fhdebug\Utility\DebugFunctions::getTraceArray();
error_log('ProductionExceptionHandler::getTitle - backtrace: ' . print_r($backtrace, TRUE) . PHP_EOL, 3, \JambageCom\Fhdebug\Utility\DebugFunctions::getErrorLogFilename());

            return $this->defaultTitle;
        }
    }
}

