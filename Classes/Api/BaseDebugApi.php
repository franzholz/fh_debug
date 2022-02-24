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


use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Components for the Debug
 */
class BaseDebugApi
{
    protected $ignore = '';

    public function __construct (
        $extConf
    )
    {   
        $this->setIgnore($extConf['IGNORE']);        
    }

    public function setIgnore (
        $value
    )
    {
        $this->ignore = trim($value);
    }

    public function getIgnore ()
    {
        return $this->ignore;
    }

    public function object2array ($instance)
    {
        $clone = (array) $instance;
        $result = [];
        $sourceKeys = $clone;

        foreach ($clone as $key => $value) {
            $aux = explode("\0", $key);
            $newkey = $aux[count($aux) - 1];
            $result[$newkey] = $sourceKeys[$key];
        }

        return $result;
    }

    public function printTypeVariable (
        $header,
        $variable,
        $html
    )
    {
        $result = '';
        if ($html) {
            $result .= '<table>';
            $result .= '<tr><th>' . $header . '</th></tr>';
            $result .= '<tr><td>' . $variable . '</td></tr>';
            $result .= '</table>';
        }
        return $result;
    }

    public function printArrayVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    )
    {
        $result = '';

        if ($depth < $recursiveDepth) {

            $debugArray = [];
            if ($html) {
                if ($header != '') {
                    $debugArray[] = '<tr><th>' . $header . '</th><th></th></tr>';
                }

                foreach ($variable as $k => $v1) {
                    if (
                        $k != '' &&
                        GeneralUtility::inList($this->getIgnore(), $k)
                    ) {
                        continue;
                    }
                    $value = '';
                    $value .= '<tr>';
                    $td = '<td>';
                    $value .= $td;
                    if ($k != '') {
                        $value .=  nl2br(htmlspecialchars($k));
                    }
                    $value .= '</td>';
                    if (is_array($v1)) {
                        $value .= '<td class="ela">';
                        $value .= $this->printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_object($v1)) {
                        $value .= '<td class="elo">';
                        $value .= $this->printObjectVariable('', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_bool($v1)) {
                        $value .= '<td class="el">';
                        $value .= $this->printTypeVariable(
                            'Boolean',
                            ($v1 ? 'true' : 'false'),
                            true
                        );
                        $value .= '</td>';
                    } else if (is_long($v1) || is_double($v1)) {
                        $value .= '<td class="el">';
                        $value .= $this->printTypeVariable(
                            ucfirst(gettype($v1)),
                            $v1,
                            true
                        );
                        $value .= '</td>';
                    } else if (is_resource($v1) || ($v1 !== null && !is_scalar($v1))) {
                        $value .= '<td class="el">';
                        $value .= 'Resource of type ' . get_resource_type($v1) . ':' . $v1;
                        $value .= '</td>';
                    } else {
                        $value .= '<td class="el">';
                        if ($v1 != '') {
                            $value .= nl2br(htmlspecialchars($v1));
                        }
                        $value .= '</td>';
                    }
                    $value .= '</tr>' . chr(13);
                    $debugArray[] = $value;
                }
            } else {
                if ($header != '') {
                    $debugArray[] = '"' . $header . '"';
                }
                foreach ($variable as $k => $v1) {
                    if (
                        GeneralUtility::inList($this->getIgnore(), $k)
                    ) {
                        continue;
                    }

                    $value = '';
                    $value .=  $k;
                    $value .= '|';
                    if (is_array($v1)) {
                        $value .= $this->printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, $html);
                    } else if (is_object($v1)) {
                        $value .= $this->printObjectVariable('', $v1, $depth + 1, $recursiveDepth, $html);
                    } else {
                        $value .=  $v1;
                    }
                    $value .= '|' . PHP_EOL;
                    $debugArray[] = $value;
                }
            }

            $result = implode('', $debugArray);

            if ($html) {
                $result = '<table>' . $result . '</table>' . chr(13);
            }
        } else {
            $result = '->...';
        }

        return $result;
    }
}
