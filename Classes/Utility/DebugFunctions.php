<?php

namespace JambageCom\FhDebug\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* Debug extension.
*
* @author	Franz Holzinger <franz@ttproducts.de>
*
*/
class DebugFunctions {
    static public $prefixFieldArray =
        array(
            'file' => '',
            'line' => '#',
            'function' => '->'
        );
    static public $csConvObj;
    static public $errorLogFile = 'fileadmin/phpDebugErrorLog.txt';
    static public $debugFile = '';
    static public $internalErrorLog = false;

    static protected $active = false;	// inactive without initialization
    static protected $bInitialization = false;
    static protected $bErrorWritten = false;
    static protected $useErrorLog = false;

    static private $username;
    static private $bUserAllowed = true;
    static private $extConf = array();
    static private $hndFile = 0;
    static private $bHasBeenInitialized = false;
    static private $bNeedsFileInit = true;
    static private $starttimeArray = array();
    static private $bCreateFile = false;
    static private $hndProcessfile = false;
    static private $processCount = 0;
    static private $recursiveDepth = 3;
    static private $traceDepth = 5;
    static private $appendDepth = 3;
    static private $html = true;
    static private $bWriteHeader = false;
    static private $instanceCount = 0;
    static private $errorLogFilename = '';
    static private $debugFilename = '';
    static private $typo3Mode = 'ALL';
    static private $startFiles = '';
    static private $ignore = '';
    static private $ipAddress = '127.0.0.1';
    static private $debugBegin = false;
    static private $traceFields = 'file,line,function';
    static private $feUserNames = '';
    static private $debugFileMode = 'wb';
    static private $devLog = false;
    static private $sysLog = false;
    static private $sysLogExclude = '';
    static private $proxyForward = false;
    static private $title = 'debug file';
    static private $maxFileSize = 3.0;
    static private $maxFileSizeReached = false;

    public function __construct (
        $extConf
    ) {
        static::$extConf = $extConf;

        $errorLogFile = static::getErrorLogFile();
        $debugFile = static::getDebugFile();

        static::$instanceCount++;
        static::$csConvObj =  GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
        if ($extConf['ERROR_LOG'] != '') {
            $errorLogFile = $extConf['ERROR_LOG'];
        }

        if ($extConf['USE_ERROR_LOG'] == '1') {
            static::setUseErrorLog(true);
        }

        if ($extConf['DEBUGFILE'] != '') {
            $debugFile = $extConf['DEBUGFILE'];
        }

        static::setErrorLogFile($errorLogFile);
        static::setDebugFile($debugFile);

//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ' .  static::$debugFilename . PHP_EOL, 3, static::getErrorLogFilename());

//   error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct $extConf = '. print_r($extConf, true) . PHP_EOL,  3, static::getErrorLogFilename());

//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ' .  print_r(\JambageCom\FhDebug\Utility\DebugFunctions::getTraceArray(4), true) . PHP_EOL, 3, static::getErrorLogFilename());

        static::setRecursiveDepth($extConf['LEVEL']);
        static::setTraceDepth($extConf['TRACEDEPTH']);
        static::setAppendDepth($extConf['APPENDDEPTH']);
        static::setStartFiles($extConf['STARTFILES']);

        static::setIgnore($extConf['IGNORE']);

        static::setIpAddress($extConf['IPADDRESS']);
        static::setDebugBegin($extConf['DEBUGBEGIN']);
        static::setTraceFields($extConf['TRACEFIELDS']);
        static::setFeUserNames($extConf['FEUSERNAMES']);
        static::setDebugFileMode($extConf['DEBUGFILEMODE']);
        static::setDevLog($extConf['DEVLOG']);
        static::setSysLog($extConf['SYSLOG']);
        static::setSysLogExclude($extConf['SYSLOG_EXCLUDE']);
        static::setHtml($extConf['HTML']);
        static::setProxyForward($extConf['PROXY']);
        static::setTitle($extConf['TITLE']);
        static::setMaxFileSize(floatval($extConf['MAXFILESIZE']));

        $typo3Mode = ($extConf['TYPO3_MODE'] ? $extConf['TYPO3_MODE'] : 'OFF');
        static::setTypo3Mode($typo3Mode);

//   error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ENDE ' . PHP_EOL, 3, static::getErrorLogFilename());
    }

    static public function setTypo3Mode (
        $value
    ) {
        static::$typo3Mode = strtoupper($value);
    }

    static public function getTypo3Mode () {
        return static::$typo3Mode;
    }

    static public function setRecursiveDepth (
        $value
    ) {
        static::$recursiveDepth = intval($value);
    }

    static public function getRecursiveDepth () {
        return static::$recursiveDepth;
    }

    static public function setTraceDepth (
        $value
    ) {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::setTraceDepth : ' .  $value . PHP_EOL, 3, static::getErrorLogFilename());

        static::$traceDepth = intval($value);
    }

    static public function getTraceDepth () {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::getTraceDepth : ' .  static::$traceDepth . PHP_EOL, 3, static::getErrorLogFilename());

        return static::$traceDepth;
    }

    static public function setAppendDepth (
        $value
    ) {
        static::$appendDepth = intval($value);
    }

    static public function getAppendDepth () {
        return static::$appendDepth;
    }

    static public function setStartFiles (
        $value
    ) {
        static::$startFiles = trim($value);
    }

    static public function getStartFiles () {
        return static::$startFiles;
    }

    static public function setIgnore (
        $value
    ) {
        static::$ignore = trim($value);
    }

    static public function getIgnore () {
        return static::$ignore;
    }

    static public function setIpAddress (
        $value
    ) {
        static::$ipAddress = trim($value);
    }

    static public function getIpAddress () {
        return static::$ipAddress;
    }

    static public function setDebugBegin (
        $value
    ) {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::setDebugBegin : ' .  $value . PHP_EOL, 3, static::getErrorLogFilename());

        static::$debugBegin = (boolean) ($value);
    }

    static public function getDebugBegin () {
// error_log('JambageCom\FhDebug\Utility\DebugFunctions::getDebugBegin : ' .  static::$debugBegin . PHP_EOL, 3, static::getErrorLogFilename());

        return static::$debugBegin;
    }

    static public function setTraceFields (
        $value
    ) {
        static::$traceFields = trim($value);
    }

    static public function getTraceFields () {
        return static::$traceFields;
    }

    static public function setFeUserNames (
        $value
    ) {
        static::$feUserNames = trim($value);
    }

    static public function getFeUserNames () {
        return static::$feUserNames;
    }

    static public function setDebugFileMode (
        $value
    ) {
        static::$debugFileMode = trim($value);
    }

    static public function getDebugFileMode () {
        return static::$debugFileMode;
    }

    static public function setDevLog (
        $value
    ) {
        static::$devLog = (boolean) $value;
    }

    static public function getDevLog () {
// error_log('getDevLog static::$devLog = ' . static::$devLog . PHP_EOL, 3, static::getErrorLogFilename());

        return static::$devLog;
    }

    static public function setSysLog (
        $value
    ) {
        static::$sysLog = (boolean) $value;
    }

    static public function getSysLog () {
        return static::$sysLog;
    }

    static public function setSysLogExclude (
        $value
    ) {
        static::$sysLogExclude = $value;
    }

    static public function getSysLogExclude () {
        return static::$sysLogExclude;
    }

    static public function setHtml (
        $value
    ) {
        static::$html = (boolean) $value;
    }

    static public function getHtml () {
        return static::$html;
    }

    static public function setProxyForward (
        $value
    ) {
        static::$proxyForward = (boolean) $value;
    }

    static public function getProxyForward () {
        return static::$proxyForward;
    }

    static public function setTitle (
        $value
    ) {
        static::$title = $value;
    }

    static public function getTitle () {
        return static::$title;
    }

    static public function getErrorLogFile () {
        return static::$errorLogFile;
    }

    static public function setErrorLogFile (
        $errorLogFile = ''
    ) {
        if ($errorLogFile == '') {
            $errorLogFile = static::getErrorLogFile();
        } else {
            static::$errorLogFile = $errorLogFile;
        }
        static::$errorLogFilename = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $errorLogFile);
    }

    static public function getErrorLogFilename () {
        return static::$errorLogFilename;
    }

    static public function setUseErrorLog (
        $useErrorLog = true
    ) {
        static::$useErrorLog = $useErrorLog;
    }

    static public function getUseErrorLog () {
        return static::$useErrorLog;
    }

    static public function setDebugFile (
        $debugFile = ''
    ) {

        if ($debugFile == '') {
            $debugFile = static::getDebugFile();
        } else {
            static::$debugFile = $debugFile;
        }
        static::$debugFilename = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $debugFile);
    }

    static public function getDebugFile () {
        return static::$debugFile;
    }

    static public function getDebugFilename () {
        return static::$debugFilename;
    }

    static public function debugControl (array $parameters) {
    }

    static public function setMaxFileSizeReached (
        $value
    ) {
        static::$maxFileSizeReached = $value;
    }

    static public function getMaxFileSizeReached () {
        return static::$maxFileSizeReached;
    }

    static public function setMaxFileSize (
        $value
    ) {
        static::$maxFileSize = (int) $value;
    }

    static public function getMaxFileSize () {
        return static::$maxFileSize;
    }

    static public function hasError () {
        $result = (static::$bErrorWritten);
//   error_log('hasError $result: ' . $result . PHP_EOL, 3, static::getErrorLogFilename());

        return $result;
    }

    static public function writeHeader (
        $cssFilename // filename with path
    ) {
        $title = static::getTitle();

        if (TYPO3_MODE == 'FE') {
            $title .= ' id=' . $GLOBALS['TSFE']->id;
        }

        $out = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . $title . '</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="' . $cssFilename . '" />
</head>

<body>
';

// error_log('writeHeader $cssFilename: ' . $cssFilename . PHP_EOL, 3, static::getErrorLogFilename());
        $errorOut = '';

        if (static::getUseErrorLog()) {
            $errorOut = '=>';
        }
        static::write($out, $errorOut, (static::getDebugFile() == ''));
    }

    static public function writeBodyEnd () {
        $out =
'</body>';

// error_log('writeBodyEnd ' . PHP_EOL, 3, static::getErrorLogFilename());
        $errorOut = '';

        if (static::getUseErrorLog()) {
            $errorOut = '<=';
        }
        static::write($out, $errorOut, (static::getDebugFile() == ''));
    }

    static public function readIpAddress () {
        $ipAddress = '';
// error_log ('readIpAddress $_SERVER ' . print_r($_SERVER, true) . PHP_EOL, 3, static::getErrorLogFilename());

    // Nothing to do without any reliable information
        if (!isset ($_SERVER['REMOTE_ADDR'])) {
            return NULL;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
// error_log ('readIpAddress Pos 1 $ipAddress ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());
        } else if (
            static::getProxyForward() &&
            !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ips[count($ips) - 1]);
// error_log ('readIpAddress Pos 2 $ipAddress ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());
        } else {
            $ipAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
// error_log ('readIpAddress Pos 3 $ipAddress ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());
        }

// error_log ('readIpAddress ENDE $ipAddress ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());
        return $ipAddress;
    }

    static public function verifyIpAddress (
        $ipAddress
    ) {
// error_log ('verifyIpAddress $ipAddress ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());

        $debugIpAddress = static::getIpAddress();
        $result =
            (
                GeneralUtility::cmpIP(
                    $ipAddress,
                    $debugIpAddress
                )
            );

// error_log ('verifyIpAddress $result ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function verifyFeusername (
        $username
    ) {
// error_log ('verifyFeusername $username ' . $username . PHP_EOL, 3, static::getErrorLogFilename());
        $result = true;
        $feUserNames = static::getFeUserNames();
// error_log ('verifyFeusername $feUserNames ' . $feUserNames . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            TYPO3_MODE == 'FE' &&
            $feUserNames != ''
        ) {
            $tmpArray = GeneralUtility::trimExplode(',', $feUserNames);
// error_log ('verifyFeusername $tmpArray ' . print_r($tmpArray, true) . PHP_EOL, 3, static::getErrorLogFilename());

            if (
                isset($tmpArray) &&
                is_array($tmpArray) &&
                in_array($username, $tmpArray) === false
            ) {
                $result = false;
// error_log ('verifyFeusername $username not found. ' . PHP_EOL, 3, static::getErrorLogFilename());
            }
        }

// error_log ('verifyFeusername $result ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function verifyTypo3Mode (
        $verifyMode
    ) {
        $typo3Mode = static::getTypo3Mode();
//  error_log ('verifyTypo3Mode $typo3Mode ' . $typo3Mode . PHP_EOL, 3, static::getErrorLogFilename());

        $result =
            (
                $typo3Mode == $verifyMode ||
                $typo3Mode == 'ALL'
            );

//  error_log ('verifyTypo3Mode $result ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function initIpAddress (
        &$ipIsAllowed
    ) {
        $ipAdress = static::readIpAddress();
//  error_log ('initIpAddress $ipAdress ' . $ipAdress . PHP_EOL, 3, static::getErrorLogFilename());

        if (!$ipIsAllowed) {
            $ipIsAllowed = static::verifyIpAddress($ipAdress);
//  error_log ('initIpAddress $ipIsAllowed ' . $ipIsAllowed . PHP_EOL, 3, static::getErrorLogFilename());
        }

        if ($ipIsAllowed) {
            $devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
//  error_log ('initIpAddress $devIPmask ' . $devIPmask . PHP_EOL, 3, static::getErrorLogFilename());

            if ($ipAdress == '*') {
                $devIPmask = '*';
            } else if ($ipAdress != '') {
                if ($devIPmask != '') {
                    $devIPmask .= ',' . $ipAdress;
                } else {
                    $devIPmask = $ipAdress;
                }
            }
//  error_log ('initIpAddress NEU $devIPmask ' . $devIPmask . PHP_EOL, 3, static::getErrorLogFilename());

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIPmask;
        }

// error_log ('initIpAddress $ipAdress ' . $ipAdress . PHP_EOL, 3, static::getErrorLogFilename());
        return $ipAdress;
    }

    static public function init (
        $ipAddress
    ) {
//  error_log ('init START ========================================== ' . PHP_EOL, 3, static::getErrorLogFilename());

        if (static::hasBeenInitialized()) {
// error_log ('init Abbruch $bHasBeenInitialized' . PHP_EOL, 3, static::getErrorLogFilename());
            return false;
        }

//  error_log('init $ipAddress: ' . $ipAddress . PHP_EOL, 3, static::getErrorLogFilename());

        $extConf = static::getExtConf();
        $trail = debug_backtrace(false);
        $backtrace = static::getTraceArray($trail);
        $startFiles = static::getStartFiles();

        if ($startFiles != '') {
            $startFileArray = GeneralUtility::trimExplode(',', $startFiles);
            $bStartFileFound = false;
            if (is_array($startFileArray)) {
                foreach ($startFileArray as $startFile) {
                    if ($backtrace['0']['file'] == $startFile) {
                        $bStartFileFound = true;
                        break;
                    }
                }
            }

            if (!$bStartFileFound) {
//  error_log('init backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, static::getErrorLogFilename());
//   error_log('init cancelled because no STARTFILES for "' . $backtrace['0']['file'] . '"' . PHP_EOL, 3, static::getErrorLogFilename());
                return false;
            }
        }

        $resetFileFound = false;
        if ($backtrace['0']['file'] == 'mod.php') {
            $resetFileFound = true;
        }
//  error_log('init $resetFileFound: ' . $resetFileFound . PHP_EOL, 3, static::getErrorLogFilename());

        static::setIsInitialization(true);

        if (!static::getDebugBegin()) {
//   error_log ('Pos 1 vor setActive true ' . PHP_EOL, 3, static::getErrorLogFilename() );
            static::setActive(true);
        }

        if (GeneralUtility::cmpIP($ipAddress, '127.0.0.1')) {
            if (
                !GeneralUtility::cmpIP(
                    $ipAddress,
                    static::getIpAddress()
                )
            ) {
                static::$starttimeArray = array('no debugging possible', 'Attention: The server variable REMOTE_ADDR is set to local.');

//   error_log ('Pos 2 vor setActive false ' . PHP_EOL, 3, static::getErrorLogFilename() );
                static::setActive(false);
            }
        }

        static::setHasBeenInitialized(true);
        static::setIsInitialization(false);
//  error_log ('init ENDE ========================================== '. PHP_EOL, 3, static::getErrorLogFilename());
        return true;
    }

    static public function initFile () {
// error_log ('initFile START ============= ' . PHP_EOL, 3, static::getErrorLogFilename());

        $extConf = static::getExtConf();

//  error_log('initFile static::$bUserAllowed: ' . static::$bUserAllowed . PHP_EOL, 3, static::getErrorLogFilename());

        if (static::$bUserAllowed && static::getDebugFilename() != '') {

            $processFilename = static::getProcessFilename();
// 	 error_log ('initFile $processFilename = ' . $processFilename . PHP_EOL, 3, static::getErrorLogFilename());

            $readBytes = 0;
            if (!is_writable($processFilename)) {
                static::$hndProcessfile = fopen($processFilename, 'w+b');
                $readBytes = 0;
            } else {
                static::$hndProcessfile = fopen($processFilename, 'r+b');
                $readBytes = filesize($processFilename);
            }

            if (static::$hndProcessfile) {
                if ($readBytes) {
                    $processCount = intval(fread(static::$hndProcessfile, $readBytes));
                    $processCount++;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 1 ' . PHP_EOL, 3, static::getErrorLogFilename());
                } else {
                    $processCount = 1;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 2 ' .  PHP_EOL, 3, static::getErrorLogFilename());
                }

                if (
                    $resetFileFound ||
                    $processCount > intval(static::getAppendDepth())
                ) {
                    $processCount = 1;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 3 ' .  PHP_EOL, 3, static::getErrorLogFilename());
                    static::setCreateFile();
                }
// 	error_log ('initFile write $processCount = ' . $processCount  .  ' Pos 8 ' . PHP_EOL, 3, static::getErrorLogFilename());
                static::writeTemporaryFile($processCount);
            }

            $extPath = PATH_typo3conf;
            $filename = static::getDebugFilename();
//  	error_log ('initFile write $filename = ' . $filename . PHP_EOL, 3, static::getErrorLogFilename());
            $path_parts = pathinfo($filename);

            if (
                $filename != '' &&
                is_writable($path_parts['dirname'])
            ) {
                static::$bWriteHeader = static::getHtml();
//  	error_log ('initFile write static::$bWriteHeader = ' . static::$bWriteHeader . PHP_EOL, 3, static::getErrorLogFilename());

                if (static::getAppendDepth() > 1) {
                    if (static::$bCreateFile) {
                        $openMode = 'w+b';
// error_log('initFile $openMode Pos 1 = ' . $openMode . PHP_EOL, 3, static::getErrorLogFilename());
                    } else {
                        $openMode = 'a+b';
// error_log('initFile $openMode Pos 2 = ' . $openMode . PHP_EOL, 3, static::getErrorLogFilename());
                    }
                } else {
                    $openMode = static::getDebugFileMode();
// error_log('initFile $openMode Pos 3 = ' . $openMode . PHP_EOL, 3, static::getErrorLogFilename());
                }
// error_log ('initFile fopen(' . $filename . ', ' . $openMode . ') ' . PHP_EOL, 3, static::getErrorLogFilename() );

                static::$hndFile = fopen($filename, $openMode);

                if (static::$hndFile !== false) {

                    $ipAddress = static::readIpAddress();
                    static::$starttimeArray =
                        array(
                            date('H:i:s  d.m.Y') . '  (' . $ipAddress . ')',
                            'start time, date and IP of debug session (mode "' . $openMode . '")'
                        );
                } else if (
                    static::getDevLog() &&
                    !is_writable($filename)
                ) {
                    GeneralUtility::devLog(
                        'DEBUGFILE: "' . $filename . '" is not writable in mode="' . $openMode . '"',
                        FH_DEBUG_EXT,
                        0
                    );

                    GeneralUtility::sysLog(
                        'DEBUGFILE: "' . $filename . '" is not writable in mode="' . $openMode . '"',
                        FH_DEBUG_EXT,
                        0
                    );
// error_log('initFile no file handle ERROR = ' . $out . PHP_EOL, 3, static::getErrorLogFilename());
                }
            } else {
                if (static::getDevLog()) {
// error_log('devLog initFile not writable directory "' . $path_parts['dirname'] . '"' . PHP_EOL, 3, static::getErrorLogFilename());

                    GeneralUtility::devLog(
                        'DEBUGFILE: directory "' . $path_parts['dirname'] . '" is not writable. "',
                        FH_DEBUG_EXT,
                        0
                    );
                }
// error_log('initFile not writable directory "' . $path_parts['dirname'] . '"' . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('Pos 3 vor setActive false ' . PHP_EOL, 3, static::getErrorLogFilename() );
                static::setActive(false); // no debug is necessary when the file cannot be written anyways
            }
        }
    }

    static public function getProcessFilename () {
        $result = PATH_site . 'typo3temp/fh_debug.txt';
        return $result;
    }

    static public function getActive () {
        return static::$active;
    }

    static public function setActive ($v) {
        static::$active = $v;
    }

    static public function setIsInitialization (
        $bInitialization
    ) {
        static::$bInitialization = $bInitialization;
    }

    static public function bIsInitialization () {
        return static::$bInitialization;
    }

    static public function setHasBeenInitialized (
        $bHasBeenInitialized
    ) {
        static::$bHasBeenInitialized = $bHasBeenInitialized;
//  error_log('setHasBeenInitialized static::$bHasBeenInitialized: ' . static::$bHasBeenInitialized . PHP_EOL, 3, static::getErrorLogFilename());
    }

    static public function hasBeenInitialized () {
//  error_log('hasBeenInitialized static::$bHasBeenInitialized: ' . static::$bHasBeenInitialized . PHP_EOL, 3, static::getErrorLogFilename());

        return static::$bHasBeenInitialized;
    }

    static public function truncateFile () {

// 		if (static::$hndFile) {
// 			static::$hndFile = ftruncate(static::$hndFile, 0);
// 			static::writeTemporaryFile(0);
// 			static::setHasBeenInitialized(false);
// 		}
    }

    static public function setCreateFile () {

        static::$bCreateFile = true;
    }

    static public function debugBegin () {
//  error_log('debugBegin ANFANG'. PHP_EOL, 3, static::getErrorLogFilename());
        static::$internalErrorLog = true;

        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
// error_log ('Pos 5 vor setActive true ' . PHP_EOL, 3, static::getErrorLogFilename() );
                static::setActive(true);

                $ipAddress = static::readIpAddress();
                static::debug(
                    'debugBegin (' . $ipAddress . ') BEGIN [--->',
                    'debugBegin',
                    '',
                    '',
                    true
                );

// $backtrace = static::getTraceArray();
// error_log('debugBegin backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, static::getErrorLogFilename());
            }
        }

        static::$internalErrorLog = false;
    }

    static public function debugEnd () {
//   error_log('debugEnd ANFANG' . PHP_EOL, 3, static::getErrorLogFilename());

        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
                $ipAddress = static::readIpAddress();
                static::debug(
                    'debugEnd (' . $ipAddress . ') END <---]',
                    'debugEnd',
                    '',
                    '',
                    true
                );
//  error_log ('Pos 6 vor setActive false ' . PHP_EOL, 3, static::getErrorLogFilename() );
                static::setActive(false);

// $backtrace = static::getTraceArray();
// error_log('debugEnd backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, static::getErrorLogFilename());
            }
        }
    }

    static public function getExtConf () {
        $result = static::$extConf;

        return $result;
    }

    static public function getTraceFieldArray () {
        $result = GeneralUtility::trimExplode(',',  static::getTraceFields());
        return $result;
    }

    static public function getTraceArray (
        $trail,
        $depth = 0,
        $offset = 0
    ) {

// if (static::$internalErrorLog) error_log('my debug getTraceArray: $depth = ' . $depth . PHP_EOL, 3, static::getErrorLogFilename());
// error_log('my debug getTraceArray Pos 1: $offset = ' . $offset . PHP_EOL, 3, static::getErrorLogFilename());

        $last = count($trail) - 1;

        if (!$depth) {
            $depth = $last + 1;
            $offset = 0;
        }

// error_log('my debug getTraceArray Pos 1: $offset = ' . $offset . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('__FILE__ = "' . __FILE__ . '"'. PHP_EOL, 3, static::getErrorLogFilename());
        $theFilename = basename(__FILE__);
// error_log ('$theFilename = "' . $theFilename . '"'. PHP_EOL, 3, static::getErrorLogFilename());
        $traceFieldArray = static::getTraceFieldArray();
        $traceArray = array();
        $j = $depth - 1;

        for ($i = $offset; $i <= $last ; ++$i) {
            unset($trail[$i]['args']);
            if (!isset($trail[$i])) {
                continue;
            }
            $theTrail = $trail[$i];
            if (
                !is_array($theTrail) ||
                $theTrail['file'] == '' ||
                $theTrail['line'] == '' ||
                strpos($theTrail['class'], '\\FhDebug\\') !== false
            ) {
                continue;
            }

            foreach ($traceFieldArray as $traceField) {
                $traceValue = $theTrail[$traceField];
                if (
                    $traceField == 'file'
                ) {
// error_log ('Pos 1 $traceValue = "' . $traceValue . '"'. PHP_EOL, 3, static::getErrorLogFilename());
                    $value = basename($traceValue);
// error_log ('Pos 2 $value = "' . $value . '"'. PHP_EOL, 3, static::getErrorLogFilename());
                    if (
                        (
                            !$offset &&
                            stripos($value, 'debug') !== false
                        )
                    ) {
                        if (isset($traceArray[$j])) {
                            unset($traceArray[$j]);
                        }
                        break;
                    }

                    $traceValue = $value;
                }

                $traceArray[$j][$traceField] = $traceValue;
// if (static::$internalErrorLog) error_log('my debug getTraceArray Pos 2: ' . $traceArray . '['.$j.']['.$traceField.'] = ' . $traceArray[$j][$traceField] . PHP_EOL, 3, static::getErrorLogFilename());
            }
            $j--;

            if ($j < 0) {
                break;
            }
        }
        ksort($traceArray);

// if (static::$internalErrorLog) error_log('my debug getTraceArray ENDE: ' . print_r($traceArray, true) . PHP_EOL, 3, static::getErrorLogFilename());

        return $traceArray;
    }

    static public function printTraceLine (
        array $traceArray,
        $html
    ) {
        $result = '';
        $debugTrail = array();

        if (!empty($traceArray)) {
            foreach ($traceArray as $i => $trace) {
                if ($html) {
                    $debugTrail[$i] .= '<tr>';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .= '<td>'; //  bgcolor="#E79F9F"
                        $debugTrail[$i] .=  static::$prefixFieldArray[$traceField] . $v;
                        $debugTrail[$i] .= '</td>';
                    }
                    $debugTrail[$i] .= '</tr>';
                } else {
                    $debugTrail[$i] .= '|';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .=  static::$prefixFieldArray[$traceField] . $v;
                        $debugTrail[$i] .= '|';
                    }
                    $debugTrail[$i] .= chr(13);
                }
            }
//  error_log('printTraceLine $debugTrail: ' . print_r($debugTrail, true) . PHP_EOL, 3, static::getErrorLogFilename());

            $result = implode('', $debugTrail);
            if ($html) {
                $result = '<table>' . $result . '</table>';
            } else {
                $result = PHP_EOL . '==============================' . PHP_EOL . $result . PHP_EOL;
            }
        }
        return $result;
    }

    static public function printTypeVariable (
        $header,
        $variable,
        $html
    ) {
        $result = '';
        if ($html) {
            $result .= '<table>';
            $result .= '<tr><th>' . $header . '</th></tr>';
            $result .= '<tr><td>' . $variable . '</td></tr>';
            $result .= '</table>';
        }
        return $result;
    }

    static public function printArrayVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    ) {
        $result = '';

// error_log ('printArrayVariable $header = ' . $header . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('$variable = ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('$depth = ' . $depth . PHP_EOL, 3, static::getErrorLogFilename());

        if ($depth < $recursiveDepth) {

            $debugArray = array();
            if ($html) {
                if ($header != '') {
                    $debugArray[] = '<tr><th>' . $header . '</th></tr>';
                }

                foreach ($variable as $k => $v1) {
                    if (
                        $k != '' &&
                        GeneralUtility::inList(static::getIgnore(), $k)
                    ) {
                        continue;
                    }

                    $value = '';
                    $value .= '<tr>';
                    $td = '<td>';
                    $value .= $td;
                    $value .=  nl2br(htmlspecialchars($k));
                    $value .= '</td>';
                    if (is_array($v1)) {
                        $value .= '<td class="ela"">';
                        $value .= static::printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_object($v1)) {
                        $value .= '<td class="elo">';
                        $value .= static::printObjectVariable('', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_bool($v1)) {
                        $value .= '<td class="el">';
                        $value .= static::printTypeVariable(
                            'Boolean',
                            ($v1 ? 'true' : 'false') ,
                            true
                        );
                        $value .= '</td>';
                    } else if (is_long($v1) || is_double($v1)) {
                        $value .= '<td class="el">';
                        $value .= static::printTypeVariable(
                            ucfirst(gettype($v1)),
                            $v1,
                            true
                        );
                        $value .= '</td>';
                    } else if (is_resource($v1)) {
                        $value .= '<td class="el">';
                        $value .= 'Resource of type ' . get_resource_type($v1) . ':' . $v1;
                        $value .= '</td>';
                    } else {
                        $value .= '<td class="el">';
                        $value .= nl2br(htmlspecialchars($v1));
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
                        GeneralUtility::inList(static::getIgnore(), $k)
                    ) {
                        continue;
                    }

                    $value = '';
                    $value .=  $k;
                    $value .= '|';
                    if (is_array($v1)) {
                        $value .= static::printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, $html);
                    } else if (is_object($v1)) {
                        $value .= static::printObjectVariable('', $v1, $depth + 1, $recursiveDepth, $html);
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
// error_log ('printArrayVariable ENDE $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function printObjectVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    ) { // TODO: show private member variables
        //Instantiate the reflection object
        $reflector = new \ReflectionClass($variable);
        $properties = $reflector->getProperties();

        $variableArray = array();
        foreach($properties as $property) {

            //Populating properties

            $theProperty = $reflector->getProperty($property->getName());
            $theProperty->setAccessible(true);
            $variableArray[$property->getName()] = $theProperty->getValue($variable);
        }

        $classname = @get_class($variable);
        $header .= $classname;
        $result = static::printArrayVariable($header, $variableArray, $depth, $recursiveDepth, $html);

        return $result;
    }

    static public function printVariable (
        $header,
        $variable,
        $recursiveDepth,
        $html
    ) {
        $result = '';
        $debugArray = array();
// error_log ('printVariable $header = ' . print_r($header, true) . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('printVariable $variable = ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (is_array($variable)) {
            if (!$header) {
                $header = 'Array';
            }
            $result =
                static::printArrayVariable(
                    $header,
                    $variable,
                    0,
                    $recursiveDepth,
                    $html
                );
        } else if (is_object($variable)) {
            if (!$header) {
                $header = 'Object ';
            }
            $result =
                static::printObjectVariable(
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
                    $result .= static::printTypeVariable(
                        'Boolean',
                        ($variable ? 'true' : 'false') ,
                        true
                    );
                    $result .= '</td>';
// 	error_log ('printVariable Pos 3 $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
                } else if (is_long($variable) || is_double($variable)) {
                    $result = '<td class="el">';
                    $result .= static::printTypeVariable(
                        ($header == '' ? ucfirst(gettype($variable)) : ''),
                        $variable,
                        true
                    );
                    $result .= '</td>';
// 	error_log ('printVariable Pos 4 $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
                } else if (gettype($variable) == 'object') { // uninitialized object: is_object($variable) === false
                    $result = '<p>unloaded object of class "' . get_class($variable) . '"</p>';
                } else if (is_resource($variable)) {
                    $result = '<p>*RESOURCE*</p>';
                } else {
                    $result = '<p>' . nl2br(htmlspecialchars((string) $variable)) . '</p>';
// 	error_log ('printVariable Pos 5 $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
                }
            } else {
                $result = $variable;
            }
        }
        return $result;
    }

    static public function processUser () {

        if (
            TYPO3_MODE == 'FE' &&
            static::getFeUserNames() != '' &&
            isset($GLOBALS['TSFE']) &&
            is_object($GLOBALS['TSFE'])
        ) {
            if (is_array($GLOBALS['TSFE']->fe_user->user)) {
                $username = $GLOBALS['TSFE']->fe_user->user['username'];
            }

            if ($username != static::$username) {
                $bAllowFeuser = static::verifyFeusername(
                    $username
                );
//  error_log('processUser vorher static::$bUserAllowed: ' . static::$bUserAllowed . PHP_EOL, 3, static::getErrorLogFilename());

                static::$bUserAllowed = $bAllowFeuser;
//  error_log('processUser nachher static::$bUserAllowed: ' . static::$bUserAllowed . PHP_EOL, 3, static::getErrorLogFilename());

                if ($bAllowFeuser) {
                    static::$username = $username;
                }
            }
        }
    }

    static public function getTypeView ($variable) {
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

    static public function writeTemporaryFile ($processCount) {
        $processFilename = static::getProcessFilename();
        if (!static::$hndProcessfile) {
            static::$hndProcessfile = fopen($processFilename, 'r+');
        }
        ftruncate(static::$hndProcessfile, 0);
        rewind(static::$hndProcessfile);
        fwrite(static::$hndProcessfile, $processCount);
        static::$processCount = $processCount;
        fclose(static::$hndProcessfile);
    }

    static public function write (
        $out,
        $errorOut,
        $bPrintOnScreen
    ) {
//   error_log('write START ' . PHP_EOL, 3, static::getErrorLogFilename());
        $result = true;

        if ($errorOut != '') {
            // keep the following line
            $result = error_log($errorOut . PHP_EOL, 3, static::getErrorLogFilename()); // keep this
        }

        if (static::$hndFile) {
//  error_log('write vor fputs ' . PHP_EOL, 3, static::getErrorLogFilename());
            fputs(static::$hndFile, $out);
//  error_log('write nach fputs $out = ' . $out . PHP_EOL, 3, static::getErrorLogFilename());
        } else if ($bPrintOnScreen) {
//  error_log('write kein DEBUGFILE ' . PHP_EOL, 3, static::getErrorLogFilename());
            echo $out;
        } else {
//  error_log('write no file handle ERROR = ' . $out . PHP_EOL, 3, static::getErrorLogFilename());
            $result = false;
        }

//  error_log('write ENDE $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function writeOut (
        $variable,
        $name,
        $recursiveDepth,
        $html,
        $showTrace = true,
        $showHeader = false
    ) {
        $type = '';
        $out = '';
        $errorOut = '';
        $backTrace = '';

        if ($showHeader) {
            $type = static::getTypeView($variable);
        }

// error_log('writeOut $variable ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());
//   error_log('writeOut $name ' . $name . PHP_EOL, 3, static::getErrorLogFilename());
//   error_log('writeOut $recursiveDepth ' . $recursiveDepth . PHP_EOL, 3, static::getErrorLogFilename());

        $debugFile = static::getDebugFile();
//  error_log('writeOut $debugFile ' . $debugFile . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            static::$hndFile ||
            static::getUseErrorLog() ||
            $debugFile == ''
        ) {
            if ($showTrace) {
                $trail = debug_backtrace(false);

                $traceArray =
                    static::getTraceArray(
                        $trail,
                        static::getTraceDepth(),
                        0
                    );
                $traceArray = array_reverse($traceArray);
                $backTrace = static::printTraceLine($traceArray, $html);
            }

            if (
                !$html ||
                static::getUseErrorLog()
            ) {
                $out =
                    static::printVariable(
                        '',
                        $variable,
                        $recursiveDepth,
                        false
                    ) . PHP_EOL .
                    '###' . $name . $type . '###' . PHP_EOL;
                $out .= '|' . $backTrace . PHP_EOL .
                    '--------------------------------------------' . PHP_EOL;

                if (static::getUseErrorLog()) {
                    $errorOut = $out;
                }
            }
// error_log('writeOut $out ' . $out . PHP_EOL, 3, static::getErrorLogFilename());

            if ($html) {
                $out =
                    static::printVariable(
                        '',
                        $variable,
                        $recursiveDepth,
                        true
                    ) . chr(13) .
                    '<h3>' . $name . $type . '</h3>';
                $out .= '<br />' . $backTrace . chr(13) .
                    '<hr />' . chr(13);
            }
        }

        if (
            function_exists('mb_detect_encoding') &&
            is_callable('mb_detect_encoding')
        ) {
            $charset = mb_detect_encoding($out, 'UTF-8,ASCII,ISO-8859-1,ISO-8859-15', true);
        }

        if (
            $charset != '' &&
            $charset != 'UTF-8' &&
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_convMethod'] != ''
        ) {
            $out =
                static::$csConvObj->conv(
                    $out,
                    $charset,
                    'UTF-8'
                );
            if (static::getUseErrorLog()) {
                $errorOut =
                    static::$csConvObj->conv(
                        $errorOut,
                        $charset,
                        'UTF-8'
                    );
            }
        }

        $bWritten = static::write($out, $errorOut, ($debugFile == ''));
//   error_log('debug nach write $bWritten = ' . $bWritten . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            !$bWritten &&
            !static::hasError()
        ) {
            $overwriteModeArray = array('x', 'x+', 'xb', 'x+b');

            if (
                file_exists($debugFile) &&
                in_array(static::getDebugFileMode(), $overwriteModeArray)
            ) {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not empty.</b>';
            } else {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not writable.</b>';
            }
            static::$bErrorWritten = true;
//  error_log('debug static::$bErrorWritten = ' . static::$bErrorWritten . PHP_EOL, 3, static::getErrorLogFilename());
        }

        return $bWritten;
    }

    static public function debug (
        $variable = '',
        $name = '*variable*',
        $line = '*line*',
        $file = '*file*',
        $recursiveDepth = 3,
        $debugLevel = 'E_DEBUG'
    ) {
// error_log('### debug $name = ' . print_r($name, true) . PHP_EOL, 3, static::getErrorLogFilename());
// 
// error_log('### debug $variable = ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            GeneralUtility::inList(static::getIgnore(), $name)
        ) {
            return;
        }

        $storeIsActive = static::getActive();
        $bControlMode = false;
        $charset = '';

        if ($storeIsActive) {
            if ($recursiveDepth == 3) {
                $recursiveDepth = static::getRecursiveDepth();
            }
        }
        static::processUser();

        if ($name == 'control:resetTemporaryFile') {
            static::truncateFile();
            $bControlMode = true;
// error_log('### debug $bControlMode = ' . print_r($bControlMode, true) . PHP_EOL, 3, static::getErrorLogFilename());
        }

        $debugSysLog = false;

        if (
            static::getSysLog() &&
            isset($name) &&
            strpos($name, 'sysLog from ' . FH_DEBUG_EXT) !== false
        ) {
            $debugSysLog = true;

            if (
                is_array($variable) &&
                isset($variable['backTrace']) &&
                is_array($variable['backTrace']) &&
                isset($variable['backTrace']['args']) &&
                is_array($variable['backTrace']['args']) &&
                isset($variable['backTrace']['args']['0'])
            ) {
                $sysLogTopic = $variable['backTrace']['args']['0'];
                $expression = '/' . preg_quote(static::getSysLogExclude(), '/') . '/';
                preg_match($expression, $sysLogTopic, $matches);
                if (
                    !empty($matches) &&
                    !empty($matches['0'])
                ) {
                    $debugSysLog = false;
                }
            }
        }

        if (
            !$bControlMode &&
            (
                $storeIsActive ||
                static::bIsInitialization() ||
                $debugSysLog
            ) &&
            !self::getMaxFileSizeReached()
        ) {
// error_log ('debug Bearbeitung: Pos 7 vor setActive false ' . PHP_EOL, 3, static::getErrorLogFilename() );

            static::setActive(false);

            if (static::$bUserAllowed) {

                if (static::$bNeedsFileInit) {
                    static::initFile();
                    static::$bNeedsFileInit = false;
                }

// error_log('debug static::$bWriteHeader = ' . static::$bWriteHeader . PHP_EOL, 3, static::getErrorLogFilename());
                if (static::$bWriteHeader) {
// error_log('debug static::$processCount = ' . static::$processCount . PHP_EOL, 3, static::getErrorLogFilename());

                    $headerPostFix = '';
                    $headerValue = '';

//  error_log('debug static::$bWriteHeader = ' . static::$bWriteHeader . PHP_EOL, 3, static::getErrorLogFilename());

                    $cssPath = '';
                    $extConf = static::getExtConf();
                    if ($extConf['CSSPATH'] == 'EXT:' . FH_DEBUG_EXT) {
                        $cssPath = '../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath(FH_DEBUG_EXT) . 'Resources/Public/Css/';
                    } else {
                        $cssPath = $extConf['CSSPATH'];
                    }
                    static::writeHeader($cssPath . $extConf['CSSFILE']);
                    static::$bWriteHeader = false;
//  error_log('nach  static::$bWriteHeader = ' . static::$bWriteHeader . PHP_EOL, 3, static::getErrorLogFilename());

// error_log('static::$starttimeArray: ' . print_r(static::$starttimeArray, true) . PHP_EOL, 3, static::getErrorLogFilename());
                    if (count(static::$starttimeArray)) {
                        $headerPostFix = static::$starttimeArray['1'];
                        $headerValue = static::$starttimeArray['0'];
                        static::$starttimeArray = array();
                    }

                    $appendText = ' - counter: ' . static::$processCount . ' ' . $headerPostFix;
                    switch (TYPO3_MODE) {
                        case 'FE':
                            if (static::getHtml()) {
                                $head = 'Front End Debugging<br />' . $appendText;
                            } else {
                                $head = '#Front End Debugging' . $appendText . '#';
                            }
                            break;
                        case 'BE':
                            if (static::getHtml()) {
                                $head = 'Back End Debugging<br />' . $appendText;
                            } else {
                                $head = '#Back End Debugging' . $appendText . '#';
                            }
                            break;
                    }
//  error_log('debug $headerValue = ' . print_r($headerValue, true) . PHP_EOL, 3, static::getErrorLogFilename());
//  error_log('debug $head = ' . $head . PHP_EOL, 3, static::getErrorLogFilename());
                    static::writeOut(
                        $headerValue,
                        $head,
                        $recursiveDepth,
                        static::getHtml(),
                        false,
                        true
                    );
                }

                static::writeOut(
                    $variable,
                    $name,
                    $recursiveDepth,
                    static::getHtml(),
                    true,
                    false
                );

                $fileInformation = fstat(static::$hndFile);

                if (is_array($fileInformation)) {
                    $size = round(($fileInformation['size'] / 1048576), 3);
                    $maxSize = self::getMaxFileSize();
                    if (
                        $size > $maxSize &&
                        $maxSize > 0
                    ) {
                        self::setMaxFileSizeReached(true);
                        static::writeOut(
                            $size . ' MByte',
                            FH_DEBUG_EXT . ': Maximum filesize reached for the debug output file.',
                            0,
                            static::getHtml(),
                            false,
                            false
                        );
                    }
                }
            }

            if (!self::getMaxFileSizeReached()) {
                static::setActive($storeIsActive);
            }
        }

// if ($searchFileFound) {
//  	error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug ================ ENDE ' . PHP_EOL, 3, static::getErrorLogFilename());
// }

    }

    /**
    * Returns the internal debug messages as a string.
    *
    * @return string
    */
    static public function toString () {

        $errorLogFilename = '';
        $debugFilename = static::getDebugFilename();

        if (static::getUseErrorLog()) {
            $errorLogFilename = static::getErrorLogFilename();
            $result = FH_DEBUG_EXT . ': Debug messages have been written to the files "' . $debugFilename . '" and "' . $errorLogFilename . '"';
        } else {
            $result = FH_DEBUG_EXT . ': Debug messages have been written to the file "' . $debugFilename . '"';
        }

        return $result;
    }

    public function close () {
        if (static::$hndFile) {

            $headerValue = date('H:i:s  d.m.Y') . '  (' . static::readIpAddress() . ')';
            $head = '=== END time, date and IP of debug session  ===';

            static::writeOut(
                $headerValue,
                $head,
                static::getRecursiveDepth(),
                static::getHtml(),
                false,
                true
            );

            static::$instanceCount--;

            if (!static::$instanceCount && static::getAppendDepth() == '0') {
                static::writeBodyEnd();
            }

            if (!static::$instanceCount) {
                fclose(static::$hndFile);
                static::setHasBeenInitialized(false);
                static::$hndFile = NULL; // this is a static class which remains even after the closing of the object
            } else {
                fflush(static::$hndFile);
            }
        }
    }

    public function __destruct ()
    {

        static::close();
    }
}

