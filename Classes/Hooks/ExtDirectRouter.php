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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    public function routeAction(ServerRequestInterface $request, ResponseInterface $response)
    {
    // FHO start
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
            // do nothing
        } else {
            $GLOBALS['error'] = GeneralUtility::makeInstance(\TYPO3\CMS\Core\ExtDirect\ExtDirectDebug::class);
        }
    // FHO end
        $isForm = false;
        $isUpload = false;
        $rawPostData = file_get_contents('php://input');
        $postParameters = $request->getParsedBody();
        $namespace = isset($request->getParsedBody()['namespace']) ? $request->getParsedBody()['namespace'] : $request->getQueryParams()['namespace'];
        $extResponse = [];
        $extRequest = null;
        $isValidRequest = true;
        if (!empty($postParameters['extAction'])) {
            $isForm = true;
            $isUpload = $postParameters['extUpload'] === 'true';
            $extRequest = new \stdClass();
            $extRequest->action = $postParameters['extAction'];
            $extRequest->method = $postParameters['extMethod'];
            $extRequest->tid = $postParameters['extTID'];
            unset($_POST['securityToken']);
            $extRequest->data = [$_POST + $_FILES];
            $extRequest->data[] = $postParameters['securityToken'];
        } elseif (!empty($rawPostData)) {
            $extRequest = json_decode($rawPostData);
        } else {
            $extResponse[] = [
                'type' => 'exception',
                'message' => 'Something went wrong with an ExtDirect call!',
                'code' => 'router'
            ];
            $isValidRequest = false;
        }
        if (!is_array($extRequest)) {
            $extRequest = [$extRequest];
        }
        if ($isValidRequest) {
            $validToken = false;
            $firstCall = true;
            foreach ($extRequest as $index => $singleRequest) {
                $extResponse[$index] = [
                    'tid' => $singleRequest->tid,
                    'action' => $singleRequest->action,
                    'method' => $singleRequest->method
                ];
                $token = is_array($singleRequest->data) ? array_pop($singleRequest->data) : null;
                if ($firstCall) {
                    $firstCall = false;
                    $formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                    $validToken = $formprotection->validateToken($token, 'extDirect');
                }
                try {
                    if (!$validToken) {
                        throw new \TYPO3\CMS\Core\FormProtection\Exception('ExtDirect: Invalid Security Token!', 1476046324);
                    }
                    $extResponse[$index]['type'] = 'rpc';
                    $extResponse[$index]['result'] = $this->processRpc($singleRequest, $namespace);
                    $extResponse[$index]['debug'] = $GLOBALS['error']->toString();
                } catch (\Exception $exception) {
                    $extResponse[$index]['type'] = 'exception';
                    $extResponse[$index]['message'] = $exception->getMessage();
                    $extResponse[$index]['code'] = 'router';
                }
            }
        }
        if ($isForm && $isUpload) {
            $extResponse = json_encode($extResponse);
            $extResponse = preg_replace('/&quot;/', '\\&quot;', $extResponse);
            $extResponse = [
                '<html><body><textarea>' . $extResponse . '</textarea></body></html>'
            ];
        } else {
            $extResponse = json_encode($extResponse);
        }
        $response->getBody()->write($extResponse);
        return $response;
    }
}

