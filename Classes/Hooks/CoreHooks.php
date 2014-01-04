<?php

namespace JambageCom\FhDebug\Hooks;


/***************************************************************
*  Copyright notice
*
*  (c) 2013 Franz Holzinger (franz@ttproducts.de)
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

/**
 * Debug extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * $Id$
 */
class CoreHooks {
	public function preprocessRequest () {

		if (!class_exists('\\JambageCom\\FhDebug\\Utility\\DebugFunctions')) {

			require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Utility/DebugFunctions.php');  // use t3lib_extMgm::extPath in TYPO3 4.5
			// some configuration:
			\JambageCom\Fhdebug\Utility\DebugFunctions::setErrorLogFile('');
			// if you use the debug HTML file:
			\JambageCom\Fhdebug\Utility\DebugFunctions::setDebugFile('fileadmin/debug.html');
			\JambageCom\Fhdebug\Utility\DebugFunctions::setDebugBegin(FALSE);
			\JambageCom\Fhdebug\Utility\DebugFunctions::setRecursiveDepth('15');
			\JambageCom\Fhdebug\Utility\DebugFunctions::setTraceDepth('12');
			\JambageCom\Fhdebug\Utility\DebugFunctions::setAppendDepth('10');
			\JambageCom\Fhdebug\Utility\DebugFunctions::setTypo3Mode('ALL');
			\JambageCom\Fhdebug\Utility\DebugFunctions::setActive(TRUE);
			\JambageCom\Fhdebug\Utility\DebugFunctions::initFile();
		}
	}
}

?>