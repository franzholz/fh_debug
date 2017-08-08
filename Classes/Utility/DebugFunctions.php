<?php

namespace JambageCom\FhDebug\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;


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
    static private $bDevLog = false;
    static private $bSysLog = false;
    static private $proxyForward = false;
    static private $title = 'debug file';

    public function __construct (
        $extConf
    ) {
        self::$extConf = $extConf;

        $errorLogFile = self::getErrorLogFile();
        $debugFile = self::getDebugFile();

        self::$instanceCount++;
        self::$csConvObj =  GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
        if ($extConf['ERROR_LOG'] != '') {
            $errorLogFile = $extConf['ERROR_LOG'];
        }

        if ($extConf['USE_ERROR_LOG'] == '1') {
            self::setUseErrorLog(true);
        }

        if ($extConf['DEBUGFILE'] != '') {
            $debugFile = $extConf['DEBUGFILE'];
        }

        self::setErrorLogFile($errorLogFile);
        self::setDebugFile($debugFile);

//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ' .  self::$debugFilename . PHP_EOL, 3, self::getErrorLogFilename());

//   error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct $extConf = '. print_r($extConf, true) . PHP_EOL,  3, self::getErrorLogFilename());

//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ' .  print_r(\JambageCom\FhDebug\Utility\DebugFunctions::getTraceArray(4), true) . PHP_EOL, 3, self::getErrorLogFilename());

        self::setRecursiveDepth($extConf['LEVEL']);
        self::setTraceDepth($extConf['TRACEDEPTH']);
        self::setAppendDepth($extConf['APPENDDEPTH']);
        self::setStartFiles($extConf['STARTFILES']);

        self::setIgnore($extConf['IGNORE']);

        self::setIpAddress($extConf['IPADDRESS']);
        self::setDebugBegin($extConf['DEBUGBEGIN']);
        self::setTraceFields($extConf['TRACEFIELDS']);
        self::setFeUserNames($extConf['FEUSERNAMES']);
        self::setDebugFileMode($extConf['DEBUGFILEMODE']);
        self::setDevLog($extConf['DEVLOG']);
        self::setSysLog($extConf['SYSLOG']);
        self::setHtml($extConf['HTML']);
        self::setProxyForward($extConf['PROXY']);
        self::setTitle($extConf['TITLE']);

        $typo3Mode = ($extConf['TYPO3_MODE'] ? $extConf['TYPO3_MODE'] : 'OFF');
        self::setTypo3Mode($typo3Mode);

//   error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ENDE ' . PHP_EOL, 3, self::getErrorLogFilename());
    }

    static public function setTypo3Mode (
        $value
    ) {
        self::$typo3Mode = strtoupper($value);
    }

    static public function getTypo3Mode () {
        return self::$typo3Mode;
    }

    static public function setRecursiveDepth (
        $value
    ) {
        self::$recursiveDepth = intval($value);
    }

    static public function getRecursiveDepth () {
        return self::$recursiveDepth;
    }

    static public function setTraceDepth (
        $value
    ) {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::setTraceDepth : ' .  $value . PHP_EOL, 3, self::getErrorLogFilename());

        self::$traceDepth = intval($value);
    }

    static public function getTraceDepth () {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::getTraceDepth : ' .  self::$traceDepth . PHP_EOL, 3, self::getErrorLogFilename());

        return self::$traceDepth;
    }

    static public function setAppendDepth (
        $value
    ) {
        self::$appendDepth = intval($value);
    }

    static public function getAppendDepth () {
        return self::$appendDepth;
    }

    static public function setStartFiles (
        $value
    ) {
        self::$startFiles = trim($value);
    }

    static public function getStartFiles () {
        return self::$startFiles;
    }

    static public function setIgnore (
        $value
    ) {
        self::$ignore = trim($value);
    }

    static public function getIgnore () {
        return self::$ignore;
    }

    static public function setIpAddress (
        $value
    ) {
        self::$ipAddress = trim($value);
    }

    static public function getIpAddress () {
        return self::$ipAddress;
    }

    static public function setDebugBegin (
        $value
    ) {
//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::setDebugBegin : ' .  $value . PHP_EOL, 3, self::getErrorLogFilename());

        self::$debugBegin = (boolean) ($value);
    }

    static public function getDebugBegin () {
// error_log('JambageCom\FhDebug\Utility\DebugFunctions::getDebugBegin : ' .  self::$debugBegin . PHP_EOL, 3, self::getErrorLogFilename());

        return self::$debugBegin;
    }

    static public function setTraceFields (
        $value
    ) {
        self::$traceFields = trim($value);
    }

    static public function getTraceFields () {
        return self::$traceFields;
    }

    static public function setFeUserNames (
        $value
    ) {
        self::$feUserNames = trim($value);
    }

    static public function getFeUserNames () {
        return self::$feUserNames;
    }

    static public function setDebugFileMode (
        $value
    ) {
        self::$debugFileMode = trim($value);
    }

    static public function getDebugFileMode () {
        return self::$debugFileMode;
    }

    static public function setDevLog (
        $value
    ) {
        self::$bDevLog = (boolean) $value;
    }

    static public function getDevLog () {
// error_log('getDevLog self::$bDevLog = ' . self::$bDevLog . PHP_EOL, 3, self::getErrorLogFilename());

        return self::$bDevLog;
    }

    static public function setSysLog (
        $value
    ) {
        self::$bDevLog = (boolean) $value;
    }

    static public function getSysLog () {
        return self::$bSysLog;
    }

    static public function setHtml (
        $value
    ) {
        self::$html = (boolean) $value;
    }

    static public function getHtml () {
        return self::$html;
    }

    static public function setProxyForward (
        $value
    ) {
        self::$proxyForward = (boolean) $value;
    }

    static public function getProxyForward () {
        return self::$proxyForward;
    }

    static public function setTitle (
        $value
    ) {
        self::$title = $value;
    }

    static public function getTitle () {
        return self::$title;
    }

    static public function getErrorLogFile () {
        return self::$errorLogFile;
    }

    static public function setErrorLogFile (
        $errorLogFile = ''
    ) {
        if ($errorLogFile == '') {
            $errorLogFile = self::getErrorLogFile();
        } else {
            self::$errorLogFile = $errorLogFile;
        }
        self::$errorLogFilename = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $errorLogFile);
    }

    static public function getErrorLogFilename () {
        return self::$errorLogFilename;
    }

    static public function setUseErrorLog (
        $useErrorLog = true
    ) {
        self::$useErrorLog = $useErrorLog;
    }

    static public function getUseErrorLog () {
        return self::$useErrorLog;
    }

    static public function setDebugFile (
        $debugFile = ''
    ) {

        if ($debugFile == '') {
            $debugFile = self::getDebugFile();
        } else {
            self::$debugFile = $debugFile;
        }
        self::$debugFilename = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $debugFile);
    }

    static public function getDebugFile () {
        return self::$debugFile;
    }

    static public function getDebugFilename () {
        return self::$debugFilename;
    }

    static public function debugControl (array $parameters) {
    }

    static public function hasError () {
        $result = (self::$bErrorWritten);
//   error_log('hasError $result: ' . $result . PHP_EOL, 3, self::getErrorLogFilename());

        return $result;
    }

    static public function writeHeader (
        $cssFilename // filename with path
    ) {
        $title = self::getTitle();

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

// error_log('writeHeader $cssFilename: ' . $cssFilename . PHP_EOL, 3, self::getErrorLogFilename());
        $errorOut = '';

        if (self::getUseErrorLog()) {
            $errorOut = '=>';
        }
        self::write($out, $errorOut, (self::getDebugFile() == ''));
    }

    static public function writeBodyEnd () {
        $out =
'</body>';

// error_log('writeBodyEnd ' . PHP_EOL, 3, self::getErrorLogFilename());
        $errorOut = '';

        if (self::getUseErrorLog()) {
            $errorOut = '<=';
        }
        self::write($out, $errorOut, (self::getDebugFile() == ''));
    }

    static public function readIpAddress () {
        $ipAddress = '';
// error_log ('readIpAddress $_SERVER ' . print_r($_SERVER, true) . PHP_EOL, 3, self::getErrorLogFilename());

    // Nothing to do without any reliable information
        if (!isset ($_SERVER['REMOTE_ADDR'])) {
            return NULL;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
// error_log ('readIpAddress Pos 1 $ipAddress ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());
        } else if (
            self::getProxyForward() &&
            !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ips[count($ips) - 1]);
// error_log ('readIpAddress Pos 2 $ipAddress ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());
        } else {
            $ipAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
// error_log ('readIpAddress Pos 3 $ipAddress ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());
        }

// error_log ('readIpAddress ENDE $ipAddress ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());
        return $ipAddress;
    }

    static public function verifyIpAddress (
        $ipAddress
    ) {
// error_log ('verifyIpAddress $ipAddress ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());

        $debugIpAddress = self::getIpAddress();
        $result =
            (
                GeneralUtility::cmpIP(
                    $ipAddress,
                    $debugIpAddress
                )
            );

// error_log ('verifyIpAddress $result ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
        return $result;
    }

    static public function verifyFeusername (
        $username
    ) {
// error_log ('verifyFeusername $username ' . $username . PHP_EOL, 3, self::getErrorLogFilename());
        $result = true;
        $feUserNames = self::getFeUserNames();
// error_log ('verifyFeusername $feUserNames ' . $feUserNames . PHP_EOL, 3, self::getErrorLogFilename());

        if (
            TYPO3_MODE == 'FE' &&
            $feUserNames != ''
        ) {
            $tmpArray = GeneralUtility::trimExplode(',', $feUserNames);
// error_log ('verifyFeusername $tmpArray ' . print_r($tmpArray, true) . PHP_EOL, 3, self::getErrorLogFilename());

            if (
                isset($tmpArray) &&
                is_array($tmpArray) &&
                in_array($username, $tmpArray) === false
            ) {
                $result = false;
// error_log ('verifyFeusername $username not found. ' . PHP_EOL, 3, self::getErrorLogFilename());
            }
        }

// error_log ('verifyFeusername $result ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
        return $result;
    }

    static public function verifyTypo3Mode (
        $verifyMode
    ) {
        $typo3Mode = self::getTypo3Mode();
//  error_log ('verifyTypo3Mode $typo3Mode ' . $typo3Mode . PHP_EOL, 3, self::getErrorLogFilename());

        $bIsAllowed =
            (
                $typo3Mode == $verifyMode ||
                $typo3Mode == 'ALL'
            );

//  error_log ('verifyTypo3Mode $bIsAllowed ' . $bIsAllowed . PHP_EOL, 3, self::getErrorLogFilename());
        return $bIsAllowed;
    }

    static public function initIpAddress (
        &$ipIsAllowed
    ) {
        $ipAdress = self::readIpAddress();
//  error_log ('initIpAddress $ipAdress ' . $ipAdress . PHP_EOL, 3, self::getErrorLogFilename());

        if (!$ipIsAllowed) {
            $ipIsAllowed = self::verifyIpAddress($ipAdress);
//  error_log ('initIpAddress $ipIsAllowed ' . $ipIsAllowed . PHP_EOL, 3, self::getErrorLogFilename());
        }

        if ($ipIsAllowed) {
            $devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];
//  error_log ('initIpAddress $devIPmask ' . $devIPmask . PHP_EOL, 3, self::getErrorLogFilename());

            if ($ipAdress == '*') {
                $devIPmask = '*';
            } else if ($ipAdress != '') {
                if ($devIPmask != '') {
                    $devIPmask .= ',' . $ipAdress;
                } else {
                    $devIPmask = $ipAdress;
                }
            }
//  error_log ('initIpAddress NEU $devIPmask ' . $devIPmask . PHP_EOL, 3, self::getErrorLogFilename());

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIPmask;
        }

// error_log ('initIpAddress $ipAdress ' . $ipAdress . PHP_EOL, 3, self::getErrorLogFilename());
        return $ipAdress;
    }

    static public function init (
        $ipAddress
    ) {
//  error_log ('init START ========================================== ' . PHP_EOL, 3, self::getErrorLogFilename());

        if (self::hasBeenInitialized()) {
// error_log ('init Abbruch $bHasBeenInitialized' . PHP_EOL, 3, self::getErrorLogFilename());
            return false;
        }

//  error_log('init $ipAddress: ' . $ipAddress . PHP_EOL, 3, self::getErrorLogFilename());

        $extConf = self::getExtConf();
        $backtrace = self::getTraceArray();
        $startFiles = self::getStartFiles();

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
//  error_log('init backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, self::getErrorLogFilename());
//   error_log('init cancelled because no STARTFILES for "' . $backtrace['0']['file'] . '"' . PHP_EOL, 3, self::getErrorLogFilename());
                return false;
            }
        }

        $bResetFileFound = false;
        if ($backtrace['0']['file'] == 'mod.php') {
            $bResetFileFound = true;
        }
//  error_log('init $bResetFileFound: ' . $bResetFileFound . PHP_EOL, 3, self::getErrorLogFilename());

        self::setIsInitialization(true);

        if (!self::getDebugBegin()) {
//   error_log ('Pos 1 vor setActive true ' . PHP_EOL, 3, self::getErrorLogFilename() );
            self::setActive(true);
        }

        if (GeneralUtility::cmpIP($ipAddress, '127.0.0.1')) {
            if (
                !GeneralUtility::cmpIP(
                    $ipAddress,
                    self::getIpAddress()
                )
            ) {
                self::$starttimeArray = array('no debugging possible', 'Attention: The server variable REMOTE_ADDR is set to local.');

//   error_log ('Pos 2 vor setActive false ' . PHP_EOL, 3, self::getErrorLogFilename() );
                self::setActive(false);
            }
        }

        self::setHasBeenInitialized(true);
        self::setIsInitialization(false);
//  error_log ('init ENDE ========================================== '. PHP_EOL, 3, self::getErrorLogFilename());
        return true;
    }

    static public function initFile () {
// error_log ('initFile START ============= ' . PHP_EOL, 3, self::getErrorLogFilename());

        $extConf = self::getExtConf();

//  error_log('initFile self::$bUserAllowed: ' . self::$bUserAllowed . PHP_EOL, 3, self::getErrorLogFilename());

        if (self::$bUserAllowed && self::getDebugFilename() != '') {

            $processFilename = self::getProcessFilename();
// 	 error_log ('initFile $processFilename = ' . $processFilename . PHP_EOL, 3, self::getErrorLogFilename());

            $readBytes = 0;
            if (!is_writable($processFilename)) {
                self::$hndProcessfile = fopen($processFilename, 'w+b');
                $readBytes = 0;
            } else {
                self::$hndProcessfile = fopen($processFilename, 'r+b');
                $readBytes = filesize($processFilename);
            }

            if (self::$hndProcessfile) {
                if ($readBytes) {
                    $processCount = intval(fread(self::$hndProcessfile, $readBytes));
                    $processCount++;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 1 ' . PHP_EOL, 3, self::getErrorLogFilename());
                } else {
                    $processCount = 1;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 2 ' .  PHP_EOL, 3, self::getErrorLogFilename());
                }

                if (
                    $bResetFileFound ||
                    $processCount > intval(self::getAppendDepth())
                ) {
                    $processCount = 1;
// 	error_log ('initFile $processCount = ' . $processCount . ' Pos 3 ' .  PHP_EOL, 3, self::getErrorLogFilename());
                    self::setCreateFile();
                }
// 	error_log ('initFile write $processCount = ' . $processCount  .  ' Pos 8 ' . PHP_EOL, 3, self::getErrorLogFilename());
                self::writeTemporaryFile($processCount);
            }

            $extPath = PATH_typo3conf;
            $filename = self::getDebugFilename();
//  	error_log ('initFile write $filename = ' . $filename . PHP_EOL, 3, self::getErrorLogFilename());
            $path_parts = pathinfo($filename);

            if (
                $filename != '' &&
                is_writable($path_parts['dirname'])
            ) {
                self::$bWriteHeader = self::getHtml();
//  	error_log ('initFile write self::$bWriteHeader = ' . self::$bWriteHeader . PHP_EOL, 3, self::getErrorLogFilename());

                if (self::getAppendDepth() > 1) {
                    if (self::$bCreateFile) {
                        $openMode = 'w+b';
// error_log('initFile $openMode Pos 1 = ' . $openMode . PHP_EOL, 3, self::getErrorLogFilename());
                    } else {
                        $openMode = 'a+b';
// error_log('initFile $openMode Pos 2 = ' . $openMode . PHP_EOL, 3, self::getErrorLogFilename());
                    }
                } else {
                    $openMode = self::getDebugFileMode();
// error_log('initFile $openMode Pos 3 = ' . $openMode . PHP_EOL, 3, self::getErrorLogFilename());
                }
// error_log ('initFile fopen(' . $filename . ', ' . $openMode . ') ' . PHP_EOL, 3, self::getErrorLogFilename() );

                self::$hndFile = fopen($filename, $openMode);

                if (self::$hndFile !== false) {

                    $ipAddress = self::readIpAddress();
                    self::$starttimeArray =
                        array(
                            date('H:i:s  d.m.Y') . '  (' . $ipAddress . ')',
                            'start time, date and IP of debug session (mode "' . $openMode . '")'
                        );
                } else if (
                    self::getDevLog() &&
                    !is_writable($filename)
                ) {
                    GeneralUtility::devLog(
                        'DEBUGFILE: "' . $filename . '" is not writable in mode="' . $openMode . '"',
                        'fh_debug',
                        0
                    );

                    GeneralUtility::sysLog(
                        'DEBUGFILE: "' . $filename . '" is not writable in mode="' . $openMode . '"',
                        'fh_debug',
                        0
                    );
// error_log('initFile no file handle ERROR = ' . $out . PHP_EOL, 3, self::getErrorLogFilename());
                }
            } else {
                if (self::getDevLog()) {
// error_log('devLog initFile not writable directory "' . $path_parts['dirname'] . '"' . PHP_EOL, 3, self::getErrorLogFilename());

                    GeneralUtility::devLog(
                        'DEBUGFILE: directory "' . $path_parts['dirname'] . '" is not writable. "',
                        'fh_debug',
                        0
                    );
                }
// error_log('initFile not writable directory "' . $path_parts['dirname'] . '"' . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('Pos 3 vor setActive false ' . PHP_EOL, 3, self::getErrorLogFilename() );
                self::setActive(false); // no debug is necessary when the file cannot be written anyways
            }
        }
    }

    static public function getProcessFilename () {
        $result = PATH_site . 'typo3temp/fh_debug.txt';
        return $result;
    }

    static public function getActive () {

//  error_log ('bIsActive self::$active = ' .  self::$active . PHP_EOL, 3, self::getErrorLogFilename());
        return self::$active;
    }

    static public function setActive ($v) {

//   error_log ('function setActive = ' . $v  . PHP_EOL, 3, self::getErrorLogFilename() );

//  $backtrace = self::getTraceArray();
//  error_log('setActive backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, self::getErrorLogFilename());

        self::$active = $v;
    }

    static public function setIsInitialization (
        $bInitialization
    ) {
        self::$bInitialization = $bInitialization;
    }

    static public function bIsInitialization () {
        return self::$bInitialization;
    }

    static public function setHasBeenInitialized (
        $bHasBeenInitialized
    ) {
        self::$bHasBeenInitialized = $bHasBeenInitialized;
//  error_log('setHasBeenInitialized self::$bHasBeenInitialized: ' . self::$bHasBeenInitialized . PHP_EOL, 3, self::getErrorLogFilename());
    }

    static public function hasBeenInitialized () {
//  error_log('hasBeenInitialized self::$bHasBeenInitialized: ' . self::$bHasBeenInitialized . PHP_EOL, 3, self::getErrorLogFilename());

        return self::$bHasBeenInitialized;
    }

    static public function truncateFile () {

// 		if (self::$hndFile) {
// 			self::$hndFile = ftruncate(self::$hndFile, 0);
// 			self::writeTemporaryFile(0);
// 			self::setHasBeenInitialized(false);
// 		}
    }

    static public function setCreateFile () {

        self::$bCreateFile = true;
    }

    static public function debugBegin () {
//  error_log('debugBegin ANFANG'. PHP_EOL, 3, self::getErrorLogFilename());
        self::$internalErrorLog = true;

        if (self::hasBeenInitialized() && !self::hasError()) {

            if (self::getDebugBegin()) {
// error_log ('Pos 5 vor setActive true ' . PHP_EOL, 3, self::getErrorLogFilename() );
                self::setActive(true);

                $ipAddress = self::readIpAddress();
                self::debug(
                    'debugBegin (' . $ipAddress . ') BEGIN [--->',
                    'debugBegin',
                    '',
                    '',
                    true
                );

// $backtrace = self::getTraceArray();
// error_log('debugBegin backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, self::getErrorLogFilename());
            }
        }

        self::$internalErrorLog = false;
    }

    static public function debugEnd () {
//   error_log('debugEnd ANFANG' . PHP_EOL, 3, self::getErrorLogFilename());

        if (self::hasBeenInitialized() && !self::hasError()) {

            if (self::getDebugBegin()) {
                $ipAddress = self::readIpAddress();
                self::debug(
                    'debugEnd (' . $ipAddress . ') END <---]',
                    'debugEnd',
                    '',
                    '',
                    true
                );
//  error_log ('Pos 6 vor setActive false ' . PHP_EOL, 3, self::getErrorLogFilename() );
                self::setActive(false);

// $backtrace = self::getTraceArray();
// error_log('debugEnd backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, self::getErrorLogFilename());
            }
        }
    }

    static public function getExtConf () {
        $result = self::$extConf;

        return $result;
    }

    static public function getTraceFieldArray () {
        $result = GeneralUtility::trimExplode(',',  self::getTraceFields());
        return $result;
    }

    static public function getTraceArray (
        $depth = 0,
        $offset = 0,
        $debugLevel = E_DEBUG
    ) {

// if (self::$internalErrorLog) error_log('my debug getTraceArray: $depth = ' . $depth . PHP_EOL, 3, self::getErrorLogFilename());
// error_log('my debug getTraceArray Pos 1: $offset = ' . $offset . PHP_EOL, 3, self::getErrorLogFilename());

        $trail = debug_backtrace(false);
        $last = count($trail) - 1;

        if (!$depth) {
            $depth = $last + 1;
// error_log('my debug getTraceArray Pos 2: $depth = ' . $depth . PHP_EOL, 3, self::getErrorLogFilename());
            $offset = 0;
        }

// error_log('my debug getTraceArray Pos 1: $offset = ' . $offset . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('__FILE__ = "' . __FILE__ . '"'. PHP_EOL, 3, self::getErrorLogFilename());
        $theFilename = basename(__FILE__);
// error_log ('$theFilename = "' . $theFilename . '"'. PHP_EOL, 3, self::getErrorLogFilename());
        $traceFieldArray = self::getTraceFieldArray();
        $traceArray = array();
        $j = $depth - 1;

        for ($i = $offset; $i <= $last ; ++$i) {
            unset($trail[$i]['args']);
// if (self::$internalErrorLog) error_log ('$trail['.$i.'] = "' . print_r($trail[$i], true) . '"'. PHP_EOL, 3, self::getErrorLogFilename());
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
// error_log ('Pos 1 $traceValue = "' . $traceValue . '"'. PHP_EOL, 3, self::getErrorLogFilename());
                    $value = basename($traceValue);
// error_log ('Pos 2 $value = "' . $value . '"'. PHP_EOL, 3, self::getErrorLogFilename());
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
// if (self::$internalErrorLog) error_log('my debug getTraceArray Pos 2: ' . $traceArray . '['.$j.']['.$traceField.'] = ' . $traceArray[$j][$traceField] . PHP_EOL, 3, self::getErrorLogFilename());
            }
            $j--;

            if ($j < 0) {
                break;
            }
        }
        ksort($traceArray);

// if (self::$internalErrorLog) error_log('my debug getTraceArray ENDE: ' . print_r($traceArray, true) . PHP_EOL, 3, self::getErrorLogFilename());

        return $traceArray;
    }

    static public function printTraceLine (
        $traceArray,
        $html
    ) {
        $result = '';
        $debugTrail = array();

        if (is_array($traceArray) && count($traceArray)) {
            foreach ($traceArray as $i => $trace) {
                if ($html) {
                    $debugTrail[$i] .= '<tr>';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .= '<td>'; //  bgcolor="#E79F9F"
                        $debugTrail[$i] .=  self::$prefixFieldArray[$traceField] . $v;
                        $debugTrail[$i] .= '</td>';
                    }
                    $debugTrail[$i] .= '</tr>';
                } else {
                    $debugTrail[$i] .= '|';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .=  self::$prefixFieldArray[$traceField] . $v;
                        $debugTrail[$i] .= '|';
                    }
                    $debugTrail[$i] .= chr(13);
                }
            }
//  error_log('printTraceLine $debugTrail: ' . print_r($debugTrail, true) . PHP_EOL, 3, self::getErrorLogFilename());

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

// error_log ('printArrayVariable $header = ' . $header . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('$variable = ' . print_r($variable, true) . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('$depth = ' . $depth . PHP_EOL, 3, self::getErrorLogFilename());

        if ($depth < $recursiveDepth) {

            $debugArray = array();
            if ($html) {
                if ($header != '') {
                    $debugArray[] = '<tr><th>' . $header . '</th></tr>';
                }

                foreach ($variable as $k => $v1) {
                    if (
                        $k != '' &&
                        GeneralUtility::inList(self::getIgnore(), $k)
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
                        $value .= self::printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_object($v1)) {
                        $value .= '<td class="elo">';
                        $value .= self::printObjectVariable('', $v1, $depth + 1, $recursiveDepth, true);
                        $value .= '</td>';
                    } else if (is_bool($v1)) {
                        $value .= '<td class="el">';
                        $value .= self::printTypeVariable(
                            'Boolean',
                            ($v1 ? 'true' : 'false') ,
                            true
                        );
                        $value .= '</td>';
                    } else if (is_long($v1) || is_double($v1)) {
                        $value .= '<td class="el">';
                        $value .= self::printTypeVariable(
// 							($header == '' ? ucfirst(gettype($v1)) : ''),
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
                        GeneralUtility::inList(self::getIgnore(), $k)
                    ) {
                        continue;
                    }

                    $value = '';
                    $value .=  $k;
                    $value .= '|';
                    if (is_array($v1)) {
                        $value .= self::printArrayVariable('Array', $v1, $depth + 1, $recursiveDepth, $html);
                    } else if (is_object($v1)) {
                        $value .= self::printObjectVariable('', $v1, $depth + 1, $recursiveDepth, $html);
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
// error_log ('printArrayVariable ENDE $result = ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
        return $result;
    }

    static public function printObjectVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    ) { // TODO: show private member variables

// error_log ('printObjectVariable ' . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('printObjectVariable $header = ' . $header . PHP_EOL, 3, self::getErrorLogFilename());

        //Instantiate the reflection object
        $reflector = new \ReflectionClass($variable);
        $properties = $reflector->getProperties();
// error_log ('printObjectVariable $properties = ' . print_r($properties, true) . PHP_EOL, 3, self::getErrorLogFilename());

        $variableArray = array();
        foreach($properties as $property) {
// error_log ('LOOP ' . PHP_EOL, 3, self::getErrorLogFilename());

            //Populating properties
// error_log ('$property->getDeclaringClass()->getName() = ' . $property->getDeclaringClass()->getName() . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('property->getName() = ' . $property->getName() . PHP_EOL, 3, self::getErrorLogFilename());

            $theProperty = $reflector->getProperty($property->getName());
            $theProperty->setAccessible(true);
// error_log ('printObjectVariable $theProperty = ' . print_r($theProperty, true) . PHP_EOL, 3, self::getErrorLogFilename());

// error_log ('$theProperty->getValue($variable) = ' . print_r($theProperty->getValue($variable), true) . PHP_EOL, 3, self::getErrorLogFilename());
            $variableArray[$property->getName()] = $theProperty->getValue($variable);
        }

        $classname = @get_class($variable);
        $header .= $classname;
        $result = self::printArrayVariable($header, $variableArray, $depth, $recursiveDepth, $html);

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
// error_log ('printVariable $header = ' . print_r($header, true) . PHP_EOL, 3, self::getErrorLogFilename());
// error_log ('printVariable $variable = ' . print_r($variable, true) . PHP_EOL, 3, self::getErrorLogFilename());

        if (is_array($variable)) {
            if (!$header) {
                $header = 'Array';
            }
            $result =
                self::printArrayVariable(
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
                self::printObjectVariable(
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
                    $result .= self::printTypeVariable(
                        'Boolean',
                        ($variable ? 'true' : 'false') ,
                        true
                    );
                    $result .= '</td>';
// 	error_log ('printVariable Pos 3 $result = ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
                } else if (is_long($variable) || is_double($variable)) {
                    $result = '<td class="el">';
                    $result .= self::printTypeVariable(
                        ($header == '' ? ucfirst(gettype($variable)) : ''),
                        $variable,
                        true
                    );
                    $result .= '</td>';
// 	error_log ('printVariable Pos 4 $result = ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
                } else if (gettype($variable) == 'object') { // uninitialized object: is_object($variable) === false
                    $result = '<p>unloaded object of class "' . get_class($variable) . '"</p>';
                } else {
                    $result = '<p>' . nl2br(htmlspecialchars($variable)) . '</p>';
// 	error_log ('printVariable Pos 5 $result = ' . $result . PHP_EOL, 3, self::getErrorLogFilename());

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
            self::getFeUserNames() != '' &&
            isset($GLOBALS['TSFE']) &&
            is_object($GLOBALS['TSFE'])
        ) {
            if (is_array($GLOBALS['TSFE']->fe_user->user)) {
                $username = $GLOBALS['TSFE']->fe_user->user['username'];
            }

            if ($username != self::$username) {
                $bAllowFeuser = self::verifyFeusername(
                    $username
                );
//  error_log('processUser vorher self::$bUserAllowed: ' . self::$bUserAllowed . PHP_EOL, 3, self::getErrorLogFilename());

                self::$bUserAllowed = $bAllowFeuser;
//  error_log('processUser nachher self::$bUserAllowed: ' . self::$bUserAllowed . PHP_EOL, 3, self::getErrorLogFilename());

                if ($bAllowFeuser) {
                    self::$username = $username;
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
        $processFilename = self::getProcessFilename();
        if (!self::$hndProcessfile) {
            self::$hndProcessfile = fopen($processFilename, 'r+');
        }
        ftruncate(self::$hndProcessfile, 0);
        rewind(self::$hndProcessfile);
        fwrite(self::$hndProcessfile, $processCount);
        self::$processCount = $processCount;
        fclose(self::$hndProcessfile);
    }

    static public function write (
        $out,
        $errorOut,
        $bPrintOnScreen
    ) {
//   error_log('write START ' . PHP_EOL, 3, self::getErrorLogFilename());
        $result = true;

        if ($errorOut != '') {
//  error_log('write $errorOut =  ' . $errorOut . PHP_EOL, 3, self::getErrorLogFilename());
            // keep the following line
            $result = error_log($errorOut . PHP_EOL, 3, self::getErrorLogFilename()); // keep this
        }

        if (self::$hndFile) {
//  error_log('write vor fputs ' . PHP_EOL, 3, self::getErrorLogFilename());
            fputs(self::$hndFile, $out);
//  error_log('write nach fputs $out = ' . $out . PHP_EOL, 3, self::getErrorLogFilename());
        } else if ($bPrintOnScreen) {
//  error_log('write kein DEBUGFILE ' . PHP_EOL, 3, self::getErrorLogFilename());
            echo $out;
        } else {
//  error_log('write no file handle ERROR = ' . $out . PHP_EOL, 3, self::getErrorLogFilename());
            $result = false;
        }

//  error_log('write ENDE $result = ' . $result . PHP_EOL, 3, self::getErrorLogFilename());
        return $result;
    }

    static public function writeOut (
        $variable,
        $name,
        $recursiveDepth,
        $html,
        $bTrace = true,
        $bHeader = false,
        $debugLevel = E_DEBUG
    ) {
        $type = '';
        $out = '';
        $errorOut = '';

        if ($bHeader) {
            $type = self::getTypeView($variable);
        }

// error_log('writeOut $variable ' . print_r($variable, true) . PHP_EOL, 3, self::getErrorLogFilename());
//   error_log('writeOut $name ' . $name . PHP_EOL, 3, self::getErrorLogFilename());
//   error_log('writeOut $recursiveDepth ' . $recursiveDepth . PHP_EOL, 3, self::getErrorLogFilename());

        $debugFile = self::getDebugFile();
//  error_log('writeOut $debugFile ' . $debugFile . PHP_EOL, 3, self::getErrorLogFilename());

        if (
            self::$hndFile ||
            self::getUseErrorLog() ||
            $debugFile == ''
        ) {
            $traceArray = ($bTrace ? self::getTraceArray(self::getTraceDepth(), 0, $debugLevel) : array());

// error_log('writeOut $traceArray ' . print_r($traceArray, true) . PHP_EOL, 3, self::getErrorLogFilename());
            $content = self::printTraceLine($traceArray, $html);

// error_log('writeOut $content ' . $content . PHP_EOL, 3, self::getErrorLogFilename());

            if (
                !$html ||
                self::getUseErrorLog()
            ) {
                $out = $content . '|' .
                    self::printVariable(
                        '',
                        $variable,
                        $recursiveDepth,
                        false
                    ) . PHP_EOL .
                    '###' . $name . $type . '###' . PHP_EOL .
                    '--------------------------------------------' . PHP_EOL;

                if (self::getUseErrorLog()) {
                    $errorOut = $out;
                }
            }
// error_log('writeOut $out ' . $out . PHP_EOL, 3, self::getErrorLogFilename());

            if ($html) {
                $out = $content . '<br/>' .
                    self::printVariable(
                        '',
                        $variable,
                        $recursiveDepth,
                        true
                    ) . chr(13) .
                    '<h3>' . $name . $type . '</h3>' .
                    '<hr/>' . chr(13);
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
                self::$csConvObj->conv(
                    $out,
                    $charset,
                    'UTF-8'
                );
            if (self::getUseErrorLog()) {
                $errorOut =
                    self::$csConvObj->conv(
                        $errorOut,
                        $charset,
                        'UTF-8'
                    );
            }
        }

        $bWritten = self::write($out, $errorOut, ($debugFile == ''));
//   error_log('debug nach write $bWritten = ' . $bWritten . PHP_EOL, 3, self::getErrorLogFilename());

        if (
            !$bWritten &&
            !self::hasError()
        ) {
            $overwriteModeArray = array('x', 'x+', 'xb', 'x+b');

            if (
                file_exists($debugFile) &&
                in_array(self::getDebugFileMode(), $overwriteModeArray)
            ) {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not empty.</b>';
            } else {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not writable.</b>';
            }
            self::$bErrorWritten = true;
//  error_log('debug self::$bErrorWritten = ' . self::$bErrorWritten . PHP_EOL, 3, self::getErrorLogFilename());
        }

        return $bWritten;
    }

    static public function debug (
        $variable = '',
        $name = '*variable*',
        $line = '*line*',
        $file = '*file*',
        $recursiveDepth = 3,
        $debugLevel = E_DEBUG
    ) {
        if (
            GeneralUtility::inList(self::getIgnore(), $name)
        ) {
            return;
        }

// error_log('### debug $name = ' . print_r($name, true) . PHP_EOL, 3, self::getErrorLogFilename());
//
// error_log('### debug $variable = ' . print_r($variable, true) . PHP_EOL, 3, self::getErrorLogFilename());

        $extConf = self::getExtConf();

        if ($recursiveDepth == 3) {
            $recursiveDepth = self::getRecursiveDepth();
        }

        $bControlMode = false;
        $charset = '';

        if ($name == 'control:resetTemporaryFile') {
            self::truncateFile();
            $bControlMode = true;
// error_log('### debug $bControlMode = ' . print_r($bControlMode, true) . PHP_EOL, 3, self::getErrorLogFilename());
        }
        $storeIsActive = self::getActive();


// comment these lines out
// $searchPHPFile = 'class.tx_ttproducts_eid.php'; // 'class.tx_transactorpaymill_request.php';
// $searchFileFound = false;
// $backtrace = self::getTraceArray();
// foreach ($backtrace as $traceArray) {
// 	if ($traceArray['file'] == $searchPHPFile) {
// 		$searchFileFound = true;
// 		break;
// 	}
// }

/*
$searchFileFound = true;
if ($searchFileFound) {
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug ================ ANFANG ' . PHP_EOL, 3, self::getErrorLogFilename());
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug backtrace: ' . print_r($backtrace, true) . PHP_EOL, 3, self::getErrorLogFilename());
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug $name = ' . print_r($name, true) . PHP_EOL, 3, self::getErrorLogFilename());
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug $variable = ' . print_r($variable, true) . PHP_EOL, 3, self::getErrorLogFilename());
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug self::$useErrorLog = ' . self::$useErrorLog . PHP_EOL, 3, self::getErrorLogFilename());
    error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug $storeIsActive = ' . $storeIsActive . PHP_EOL, 3, self::getErrorLogFilename());
}*/

        self::processUser();
        $bDebugSysLog = false;

        if (
            self::getSysLog() &&
            isset($variable) &&
            is_array($variable) &&
            isset($variable['SQL query']) &&
            strpos($variable['SQL query'], ' sys_log ') !== false
        ) {
            $bDebugSysLog = true;
// error_log('### debug $bDebugSysLog = ' . print_r($bDebugSysLog, true) . PHP_EOL, 3, self::getErrorLogFilename());
        }

        if (
            !$bControlMode &&
            (
                $storeIsActive ||
                self::bIsInitialization() ||
                $bDebugSysLog
            )
        ) {
// error_log ('debug Bearbeitung: Pos 7 vor setActive false ' . PHP_EOL, 3, self::getErrorLogFilename() );

            self::setActive(false);

            if (self::$bUserAllowed) {

                if (self::$bNeedsFileInit) {
                    self::initFile();
                    self::$bNeedsFileInit = false;
                }

// error_log('debug self::$bWriteHeader = ' . self::$bWriteHeader . PHP_EOL, 3, self::getErrorLogFilename());
                if (self::$bWriteHeader) {
// error_log('debug self::$processCount = ' . self::$processCount . PHP_EOL, 3, self::getErrorLogFilename());

                    $headerPostFix = '';
                    $headerValue = '';

//  error_log('debug self::$bWriteHeader = ' . self::$bWriteHeader . PHP_EOL, 3, self::getErrorLogFilename());

                    $cssPath = '';
                    if ($extConf['CSSPATH'] == 'EXT:fh_debug') {
                        $cssPath = '../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('fh_debug') . 'Resources/Public/Css/';
                    } else {
                        $cssPath = $extConf['CSSPATH'];
                    }
                    self::writeHeader($cssPath . $extConf['CSSFILE']);
                    self::$bWriteHeader = false;
//  error_log('nach  self::$bWriteHeader = ' . self::$bWriteHeader . PHP_EOL, 3, self::getErrorLogFilename());

// error_log('self::$starttimeArray: ' . print_r(self::$starttimeArray, true) . PHP_EOL, 3, self::getErrorLogFilename());
                    if (count(self::$starttimeArray)) {
                        $headerPostFix = self::$starttimeArray['1'];
                        $headerValue = self::$starttimeArray['0'];
                        self::$starttimeArray = array();
                    }

                    $appendText = ' - counter: ' . self::$processCount . ' ' . $headerPostFix;
                    switch (TYPO3_MODE) {
                        case 'FE':
                            if (self::getHtml()) {
                                $head = 'Front End Debugging<br />' . $appendText;
                            } else {
                                $head = '#Front End Debugging' . $appendText . '#';
                            }
                            break;
                        case 'BE':
                            if (self::getHtml()) {
                                $head = 'Back End Debugging<br />' . $appendText;
                            } else {
                                $head = '#Back End Debugging' . $appendText . '#';
                            }
                            break;
                    }
//  error_log('debug $headerValue = ' . print_r($headerValue, true) . PHP_EOL, 3, self::getErrorLogFilename());
//  error_log('debug $head = ' . $head . PHP_EOL, 3, self::getErrorLogFilename());
                    self::writeOut(
                        $headerValue,
                        $head,
                        $recursiveDepth,
                        self::getHtml(),
                        false,
                        true,
                        $debugLevel
                    );
                }

                self::writeOut(
                    $variable,
                    $name,
                    $recursiveDepth,
                    self::getHtml(),
                    true,
                    false,
                    $debugLevel
                );
            }
// error_log ('debug Bearbeitung: Pos 8 vor setActive ' . $storeIsActive . PHP_EOL, 3, self::getErrorLogFilename() );
            self::setActive($storeIsActive);
        }

// if ($searchFileFound) {
//  	error_log('JambageCom\FhDebug\Utility\DebugFunctions::debug ================ ENDE ' . PHP_EOL, 3, self::getErrorLogFilename());
// }

    }

    /**
    * Returns the internal debug messages as a string.
    *
    * @return string
    */
    static public function toString () {

        $errorLogFilename = '';
        $debugFilename = self::getDebugFilename();

        if (self::getUseErrorLog()) {
            $errorLogFilename = self::getErrorLogFilename();
            $result = 'The debug messages have been written to the files "' . $debugFilename . '" and "' . $errorLogFilename . '"';
        } else {
            $result = 'The debug messages have been written to the file "' . $debugFilename . '"';
        }

        return $result;
    }

    public function close () {
        if (self::$hndFile) {

            $headerValue = date('H:i:s  d.m.Y') . '  (' . self::readIpAddress() . ')';
            $head = '=== END time, date and IP of debug session  ===';

            self::writeOut(
                $headerValue,
                $head,
                self::getRecursiveDepth(),
                self::getHtml(),
                false,
                true,
                0
            );

            self::$instanceCount--;

            if (!self::$instanceCount && self::getAppendDepth() == '0') {
                self::writeBodyEnd();
            }

            if (!self::$instanceCount) {
                fclose(self::$hndFile);
                self::setHasBeenInitialized(false);
                self::$hndFile = NULL; // this is a static class which remains even after the closing of the object
            } else {
                fflush(self::$hndFile);
            }
        }
    }

    public function __destruct () {

        self::close();
    }
}

