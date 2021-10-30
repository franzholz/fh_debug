<?php

namespace JambageCom\FhDebug\Configuration;

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

class Variant 
{
    static private $default = [];
    private $config = [];
    protected $debugFileMode;

    public function __construct(...$params) {
        if ($params['default'] == 1) {
            static::$default = $params;
            $this->config = $params;
        } else {
            $this->config = array_merge(static::$default, $this->config);
        }

        $conf = $this->config;
        static::setDebugFileMode($conf['DEBUGFILEMODE']);
    }
    
    public function setDebugFileMode (
        $value
    )
    {
        $this->debugFileMode = trim($value);
    }
    
    static public function getDebugFileMode ()
    {
        return $this->debugFileMode;
    }
}
 

