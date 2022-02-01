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
class ErrorHandler extends \TYPO3\CMS\Core\Error\ErrorHandler
{
    /**
     * Handles an error.
     * If the error is registered as exceptionalError it will by converted into an exception, to be handled
     * by the configured exceptionhandler. Additionally the error message is written to the configured logs.
     * If application is backend, the error message is also added to the flashMessageQueue, in frontend the
     * error message is displayed in the admin panel (as TsLog message).
     *
     * @param int $errorLevel The error level - one of the E_* constants
     * @param string $errorMessage The error message
     * @param string $errorFile Name of the file the error occurred in
     * @param int $errorLine Line number where the error occurred
     * @return bool
     * @throws Exception with the data passed to this method if the error is registered as exceptionalError
     */
    public function handleError($errorLevel, $errorMessage, $errorFile, $errorLine)
    {
        debug($errorLevel, 'handleError $errorLevel'); // keep this
        debug($errorMessage, 'handleError $errorMessage'); // keep this
        debug($errorFile, 'handleError $errorFile'); // keep this
        debug($errorLine, 'handleError $errorLine'); // keep this
        parent::handleError($errorLevel, $errorMessage, $errorFile, $errorLine);
        return true;
    }
}

