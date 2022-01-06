<?php

namespace JambageCom\FhDebug\Hooks;


/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use JambageCom\FhDebug\Utility\DebugFunctions;

/**
 * Hooks for the TYPO3 extension patchem used by the debug extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 */
class PatchemHooks
{
    public function buildConfigurationArray (&$params, $pObj)
    {
        if (
            isset($params) &&
            is_array($params) &&
            isset($params['extensionKey']) &&
            $params['extensionKey'] == 'fh_debug' &&
            isset($params['configurationOption']) &&
            is_array($params['configurationOption'])
        ) {
            $configurationOption = &$params['configurationOption'];

            if (
                isset($configurationOption['label']) &&
                str_contains($configurationOption['label'], '###')
            ) {
                $configurationOption['label'] = str_replace('###IP###', DebugFunctions::readIpAddress(), $configurationOption['label']);
            }
        }
    }
}


