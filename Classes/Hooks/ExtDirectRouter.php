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
 * Ext Direct Router
 */
class ExtDirectRouter extends \TYPO3\CMS\Core\ExtDirect\ExtDirectRouter
{
    /**
     * Dispatches the incoming calls to methods about the ExtDirect API.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function routeAction(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        $class = '\JambageCom\FhDebug\Utility\DebugFunctions';
        if (
            isset($GLOBALS['error']) &&
            is_object($GLOBALS['error']) &&
            ($GLOBALS['error'] instanceof $class) &&
            (
                $GLOBALS['error']->getTypo3Mode() == 'ALL' ||
                $GLOBALS['error']->getTypo3Mode() == 'BE'
            )
        ) {
            $backupError = $GLOBALS['error'];
        }
        $response = parent::routeAction($request, $response);
        if (isset($backupError)) {
            $GLOBALS['error'] = $backupError;
        }

        return $response;
    }
}

