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

/**
 * Components for the Debug
 */
class OldDebugApi extends BaseDebugApi
{
    public function getClass($variable)
    {
        return get_class($variable);
    }

    public function getTypeView($variable)
    {
        $result = '';
        $type = gettype($variable);

        switch ($type) {
            case 'array':
                $result = ' (' . $type . ' of ' . count($variable) . ' items )';
                break;
            case 'object':
                $result = ' (' . $type . ' of class ' . get_class($variable) . ')';
                break;
            default:
                $result = ' (' . $type . ')';
        }
        return $result;
    }

    public function printObjectVariable(
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    ) { // TODO: show private member variables
        //Instantiate the reflection object
        $variableArray = $this->object2array($variable);
        $classname = get_class($variable);
        $header .= $classname;
        $result = $this->printArrayVariable($header, $variableArray, $depth, $recursiveDepth, $html);

        return $result;
    }

    public function printVariable(
        $header,
        $variable,
        $recursiveDepth,
        $html
    ) {
        $result = '';
        $debugArray = [];

        if (is_array($variable)) {
            if (!$header) {
                $header = 'Array';
            }
            $result =
                $this->printArrayVariable(
                    $header,
                    $variable,
                    0,
                    $recursiveDepth,
                    $html
                );
        } elseif (is_object($variable)) {
            if ($header == '') {
                $header = 'Object ';
            }
            $result =
                $this->printObjectVariable(
                    $header,
                    $variable,
                    0,
                    $recursiveDepth,
                    $html
                );
        } else {
            if ($html) {
                if (is_bool($variable)) {
                    $result = '<td class="el">';
                    $result .= $this->printTypeVariable(
                        'Boolean',
                        ($variable ? 'true' : 'false'),
                        true
                    );
                    $result .= '</td>';
                } elseif (is_long($variable) || is_double($variable)) {
                    $result = '<td class="el">';
                    $result .= $this->printTypeVariable(
                        ($header == '' ? ucfirst(gettype($variable)) : ''),
                        $variable,
                        true
                    );
                    $result .= '</td>';
                } elseif (gettype($variable) == 'object') { // uninitialized object: is_object($variable) === false
                    $result = '<p>unloaded object of class "' . get_class($variable) . '"</p>';
                } elseif (is_resource($variable)) {
                    $result = '<p>*RESOURCE*</p>';
                } else {
                    $result = '<p>' . ($variable != '' ? nl2br(htmlspecialchars((string) $variable)) : '') . '</p>';
                }
            } else {
                $result = $variable;
            }
        }
        return $result;
    }
}
