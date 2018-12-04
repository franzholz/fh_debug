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

/**
 * Core hooks used by the debug extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 */
class CoreHooks {
    public function preprocessRequest ()
    {
        if (!class_exists('\\JambageCom\\FhDebug\\Utility\\DebugFunctions')) {

            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Utility/DebugFunctions.php');
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


    /**
    * Development log.
    * If you want to implement the devLog in your applications, simply add lines like:
    * if (TYPO3_DLOG)	\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[write message in english here]', 'extension key');
    *
    * @param string $msg Message (in english).
    * @param string $extKey Extension key (from which extension you are calling the log)
    * @param integer $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
    * @param mixed $dataVar Additional data you want to pass to the logger.
    * @return void
    */

    /**
    * Developer log
    *
    * $devLogArray = array('msg'=>$msg, 'extKey'=>$extKey, 'severity'=>$severity, 'dataVar'=>$dataVar);
    * msg		string		Message (in English).
    * extKey	string		Extension key (from which extension the devLog function has been executed)
    * severity	integer		Severity: 0 ... info
    *                                1 ... notice
    *                                2 ... warning,
    *                                3 ... fatal error,
    *                               -1 ... "OK" message
    * dataVar	array		Additional data you want to pass to the logger.
    *
    * @param	array		$devLogArray: log data array
    * @return	void
    */
    static public function devLog ($devLogArray) {
        $class = '\\JambageCom\\FhDebug\\Utility\\DebugFunctions';

        if ($GLOBALS['error'] instanceof $class) {
            debug($devLogArray, 'devLog from fh_debug: $devLogArray');
        }
    }

    /**
    * System log
    *
    * $params = array('msg' => $msg, 'extKey' => $extKey, 'backTrace' => debug_backtrace(), 'severity' => $severity);
    * msg       string      Message (in English).
    * extKey    string      Extension key (from which extension the devLog function has been executed)
    * backTrace  array      index 0 ... file, line, function, class, type, args
    *                       The rest of the backtrace is removed in order to
    *                       keep the amount of data low.
    * severity  integer     Severity: 0 ... info
    *                                1 ... notice
    *                                2 ... warning,
    *                                3 ... fatal error,
    *                               -1 ... "OK" message
    *
    * @param    array       $params: log data array
    * @return   void
    */
    static public function sysLog($params)
    {
        $class = '\\JambageCom\\FhDebug\\Utility\\DebugFunctions';

        if (
            $GLOBALS['error'] instanceof $class &&
            !isset($params['initLog'])
        ) {
            $params['backTrace'] = $params['backTrace']['0'];
            debug($params, 'sysLog from fh_debug: $params');
        }
    }
}

