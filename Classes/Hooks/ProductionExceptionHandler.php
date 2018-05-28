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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

use JambageCom\FhDebug\Utility\DebugFunctions;

/**
 * Exception handler class for content object rendering
 */
class ProductionExceptionHandler extends \TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler
{
    const TYPO3_DIR         = 'typo3';

    /**
    * Handles exceptions thrown during rendering of content objects
    * The handler can decide whether to re-throw the exception or
    * return a nice error message for production context.
    *
    * @param \Exception $exception
    * @param AbstractContentObject $contentObject
    * @param array $contentObjectConfiguration
    * @return string
    * @throws \Exception
    */
    public function handle(\Exception $exception, AbstractContentObject $contentObject = null, $contentObjectConfiguration = array())
    {
        debugBegin();
        debug ($exception, 'fh_debug handle $exception'); // keep this
        if (!empty($this->configuration['ignoreCodes.'])) {
            if (
                in_array(
                    $exception->getCode(),
                    array_map(
                        'intval',
                        $this->configuration['ignoreCodes.']
                    ),
                    true
                )
            ) {
                throw $exception;
            }
        }
        $errorMessage = isset($this->configuration['errorMessage']) ? $this->configuration['errorMessage'] : 'Oops, an error occurred! Code: %s';
        $code = date('YmdHis', $_SERVER['REQUEST_TIME']) . GeneralUtility::getRandomHexString(8);
        $result = sprintf($errorMessage, $code);

        $typo3String = DIRECTORY_SEPARATOR . self::TYPO3_DIR;
        $typo3Position = strrpos($exception->getFile(), $typo3String);
        $result .= CRLF . $exception->getMessage() . CRLF . ' exception code:' . $exception->getCode() . ' file:' . substr($exception->getFile(), $typo3Position) . ' line:' . $exception->getLine() . CRLF;
        $trail = $exception->getTrace();
        $result .= 'fh_debug trace:' . CRLF;
        $maxCount = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][FH_DEBUG_EXT]['TRACEDEPTH_EXCEPTION'];

        $traceArray =
            DebugFunctions::getTraceArray(
                $trail,
                $maxCount,
                0
            );
        debug ($traceArray, 'fh_debug exception handler exception trace'); // keep this

        foreach ($trail as $trace) {
            $typo3Position = strrpos($trace['file'], $typo3String);
            $result .= 'file: ' . substr($trace['file'], $typo3Position) . '" line:' .
                $trace['line'] . ' function:' . $trace['function'];
            $result .= CRLF;
            $count++;
            if ($count > $maxCount) {
                break;
            }
        }

        $this->logException($exception, $errorMessage, $code);
        debug ($result, 'fh_debug exception handler'); // keep this
        debugEnd();

        return $result;
    }

    /**
    * @param \Exception $exception
    * @param string $errorMessage
    * @param string $code
    */
    protected function logException(\Exception $exception, $errorMessage, $code)
    {
        $this->getLogger()->alert(sprintf($errorMessage, $code), array('exception' => $exception));
    }
}
