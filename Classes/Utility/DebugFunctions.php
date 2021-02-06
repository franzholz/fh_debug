<?php

namespace JambageCom\FhDebug\Utility;
/***************************************************************
*  Copyright notice
*
*  (c) 2020 Franz Holzinger (franz@ttproducts.de)
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
        [
            'file' => '',
            'line' => '#',
            'function' => '->'
        ];
    static public $csConvObj;
    static public $errorLogFile = 'fileadmin/phpDebugErrorLog.txt';
    static public $debugFile = '';
    static public $internalErrorLog = false;

    static protected $active = false;	// inactive without initialization
    static protected $bInitialization = false;
    static protected $internalError = false;
    static protected $bErrorWritten = false;
    static protected $useErrorLog = false;

    static private $username;
    static private $isUserAllowed = true;
    static private $extConf = [];
    static private $hndFile = 0;
    static private $hasBeenInitialized = false;
    static private $needsFileInit = true;
    static private $starttimeArray = [];
    static private $createFile = false;
    static private $hndProcessfile = false;
    static private $processCount = 0;
    static private $recursiveDepth = 3;
    static private $exceptionRecursiveDepth = 3;
    static private $traceDepth = 5;
    static private $appendDepth = 3;
    static private $html = true;
    static private $headerWritten = false;
    static private $instanceCount = 0;
    static private $errorLogFilename = '';
    static private $debugFilename = '';
    static private $typo3Mode = 'ALL';
    static private $startFiles = '';
    static private $partFiles = '';
    static private $excludeFiles = '';
    static private $ignore = '';
    static private $ipAddress = '127.0.0.1';
    static private $debugBegin = false;
    static private $traceFields = 'file,line,function';
    static private $feUserNames = '';
    static private $debugFileMode = 'wb';
    static private $devLog = false;
    static private $devLogDebug = false;
    static private $sysLog = false;
    static private $sysLogExclude = '';
    static private $proxyForward = false;
    static private $title = 'debug file';
    static private $maxFileSize = 3.0;
    static private $maxFileSizeReached = false;
    static private $dateTime = 'l jS \of F Y h:i:s A';

    public function __construct (
        $extConf
    )
    {
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
        static::setDebugFileMode($extConf['DEBUGFILEMODE']);

//  error_log('JambageCom\FhDebug\Utility\DebugFunctions::__construct : ' .  static::$debugFilename . PHP_EOL, 3, static::getErrorLogFilename());

        static::setRecursiveDepth($extConf['LEVEL']);
        static::setExceptionRecursiveDepth($extConf['LEVEL_EXCEPTION']);
        static::setTraceDepth($extConf['TRACEDEPTH']);
        static::setAppendDepth($extConf['APPENDDEPTH']);
        static::setStartFiles($extConf['STARTFILES']);
        static::setPartFiles($extConf['PARTFILES']);
        static::setExcludeFiles($extConf['EXCLUDEFILES']);

        static::setIgnore($extConf['IGNORE']);

        static::setIpAddress($extConf['IPADDRESS']);
        static::setDebugBegin($extConf['DEBUGBEGIN']);
        static::setTraceFields($extConf['TRACEFIELDS']);
        static::setFeUserNames($extConf['FEUSERNAMES']);
        static::setDevLog($extConf['DEVLOG']);
        static::setDevLogDebug($extConf['DEVLOGDEBUG']);
        static::setSysLog($extConf['SYSLOG']);
        static::setSysLogExclude($extConf['SYSLOG_EXCLUDE']);
        static::setHtml($extConf['HTML']);
        static::setProxyForward($extConf['PROXY']);
        static::setTitle($extConf['TITLE']);
        static::setMaxFileSize(floatval($extConf['MAXFILESIZE']));
        if ($extConf['DATETIME'] != '') {
            static::setDateTime($extConf['DATETIME']);
        }

        $typo3Mode = ($extConf['TYPO3_MODE'] ? $extConf['TYPO3_MODE'] : 'OFF');
        static::setTypo3Mode($typo3Mode);
    }

    static public function setTypo3Mode
    (
        $value
    ) {
        static::$typo3Mode = strtoupper($value);
    }

    static public function getTypo3Mode ()
    {
        return static::$typo3Mode;
    }

    static public function setRecursiveDepth (
        $value
    )
    {
        static::$recursiveDepth = intval($value);
    }

    static public function getRecursiveDepth ()
    {
        return static::$recursiveDepth;
    }

    static public function setExceptionRecursiveDepth (
        $value
    )
    {
        static::$exceptionRecursiveDepth = intval($value);
    }

    static public function getExceptionRecursiveDepth ()
    {
        return static::$exceptionRecursiveDepth;
    }

    static public function setTraceDepth (
        $value
    ) 
    {
        static::$traceDepth = intval($value);
    }

    static public function getTraceDepth ()
    {
        return static::$traceDepth;
    }

    static public function setAppendDepth (
        $value
    )
    {
        static::$appendDepth = intval($value);
    }

    static public function getAppendDepth ()
    {
        return static::$appendDepth;
    }

    static public function setStartFiles (
        $value
    )
    {
        static::$startFiles = trim($value);
    }

    static public function getStartFiles ()
    {
        return static::$startFiles;
    }

    static public function setPartFiles (
        $value
    )
    {
        static::$partFiles = trim($value);
    }

    static public function getPartFiles ()
    {
        return static::$partFiles;
    }

    static public function setExcludeFiles (
        $value
    )
    {
        static::$excludeFiles = trim($value);
    }

    static public function getExcludeFiles ()
    {
        return static::$excludeFiles;
    }

    static public function setIgnore (
        $value
    )
    {
        static::$ignore = trim($value);
    }

    static public function getIgnore ()
    {
        return static::$ignore;
    }

    static public function setIpAddress (
        $value
    )
    {
        static::$ipAddress = trim($value);
    }

    static public function getIpAddress ()
    {
        return static::$ipAddress;
    }

    static public function setDebugBegin (
        $value
    )
    {
        static::$debugBegin = (boolean) ($value);
    }

    static public function getDebugBegin ()
    {
        return static::$debugBegin;
    }

    static public function setTraceFields (
        $value
    )
    {
        static::$traceFields = trim($value);
    }

    static public function getTraceFields ()
    {
        return static::$traceFields;
    }

    static public function setFeUserNames (
        $value
    )
    {
        static::$feUserNames = trim($value);
    }

    static public function getFeUserNames ()
    {
        return static::$feUserNames;
    }

    static public function setDebugFileMode (
        $value
    )
    {
        static::$debugFileMode = trim($value);
    }

    static public function getDebugFileMode ()
    {
        return static::$debugFileMode;
    }

    static public function setDevLog (
        $value
    )
    {
        static::$devLog = (boolean) $value;
    }

    static public function getDevLog ()
    {
        return static::$devLog;
    }

    static public function setDevLogDebug (
        $value
    )
    {
        static::$devLogDebug = (boolean) $value;
    }

    static public function getDevLogDebug ()
    {
        return static::$devLogDebug;
    }

    static public function setSysLog (
        $value
    )
    {
        static::$sysLog = (boolean) $value;
    }

    static public function getSysLog ()
    {
        return static::$sysLog;
    }

    static public function setSysLogExclude (
        $value
    )
    {
        static::$sysLogExclude = $value;
    }

    static public function getSysLogExclude ()
    {
        return static::$sysLogExclude;
    }

    static public function setHtml (
        $value
    )
    {
        static::$html = (boolean) $value;
    }

    static public function getHtml ()
    {
        return static::$html;
    }

    static public function setProxyForward (
        $value
    )
    {
        static::$proxyForward = (boolean) $value;
    }

    static public function getProxyForward ()
    {
        return static::$proxyForward;
    }

    static public function setTitle (
        $value
    )
    {
        static::$title = $value;
    }

    static public function getTitle ()
    {
        return static::$title;
    }

    static public function getErrorLogFile ()
    {
        return static::$errorLogFile;
    }

    static public function setErrorLogFile (
        $errorLogFile = ''
    )
    {
        if ($errorLogFile == '') {
            $errorLogFile = static::getErrorLogFile();
        } else {
            static::$errorLogFile = $errorLogFile;
        }
    
        $path = null;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        } else if (defined('PATH_typo3conf')) {
            $path = GeneralUtility::resolveBackPath(PATH_typo3conf . '../');
        }
    
        static::$errorLogFilename = $path . $errorLogFile;
    }

    static public function getErrorLogFilename ()
    {
        return static::$errorLogFilename;
    }

    static public function setUseErrorLog (
        $useErrorLog = true
    )
    {
        static::$useErrorLog = $useErrorLog;
    }

    static public function getUseErrorLog ()
    {
        return static::$useErrorLog;
    }

    static public function errorLog ($text, $comment) 
    {
        error_log($comment . '=' . (is_string($text) ? $text : print_r($text, true)) . PHP_EOL, 3, static::getErrorLogFilename());
    }

    static public function setDebugFile (
        $debugFile = ''
    )
    {
        if ($debugFile == '') {
            $debugFile = static::getDebugFile();
        } else {
            static::$debugFile = $debugFile;
        }

        $path = null;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        } else if (defined('PATH_typo3conf')) {
            $path = GeneralUtility::resolveBackPath(PATH_typo3conf . '../');
        }

        static::setDebugFilename($path . $debugFile);
    }

    static public function getDebugFile ()
    {
        return static::$debugFile;
    }

    static public function setDebugFilename ($debugFilename)
    {
        static::$debugFilename = $debugFilename;
    }

    static public function getDebugFilename ()
    {
        return static::$debugFilename;
    }

    static public function setMaxFileSizeReached
    (
        $value
    )
    {
        static::$maxFileSizeReached = $value;
    }

    static public function getMaxFileSizeReached ()
    {
        return static::$maxFileSizeReached;
    }

    static public function setMaxFileSize (
        $value
    )
    {
        static::$maxFileSize = (int) $value;
    }

    static public function getMaxFileSize ()
    {
        return static::$maxFileSize;
    }

    static public function setDateTime (
        $value
    )
    {
        static::$dateTime = $value;
    }

    static public function getDateTime ()
    {
        return static::$dateTime;
    }

    static public function hasError ()
    {
        $result = (static::$bErrorWritten);
        return $result;
    }

    static public function writeHeader (
        $cssFilename // filename with path
    )
    {
        $title = static::getTitle();

        if (
            TYPO3_MODE == 'FE' &&
            !static::getAppendDepth()
        ) {
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

    static public function writeBodyEnd ()
    {
        $out =
'</body></html>';

        $errorOut = '';

        if (static::getUseErrorLog()) {
            $errorOut = '<=';
        }
        static::write($out, $errorOut, (static::getDebugFile() == ''));
    }

    static public function readIpAddress ()
    {
        $ipAddress = '';
    // Nothing to do without any reliable information
        if (!isset ($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (
            static::getProxyForward() &&
            !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ips[count($ips) - 1]);
        } else {
            $ipAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        }

        return $ipAddress;
    }

    static public function verifyIpAddress (
        $ipAddress
    )
    { 
        $debugIpAddress = static::getIpAddress();
        $result =
            (
                GeneralUtility::cmpIP(
                    $ipAddress,
                    $debugIpAddress
                )
            );

        return $result;
    }

    static public function verifyFeusername (
        $username
    )
    {
        $result = true;
        $feUserNames = static::getFeUserNames();

        if (
            TYPO3_MODE == 'FE' &&
            $feUserNames != ''
        ) {
            $tmpArray = GeneralUtility::trimExplode(',', $feUserNames);

            if (
                isset($tmpArray) &&
                is_array($tmpArray) &&
                in_array($username, $tmpArray) === false
            ) {
                $result = false;
            }
        }

        return $result;
    }

    static public function verifyTypo3Mode (
        $verifyMode
    )
    {
        $typo3Mode = static::getTypo3Mode();
        $result =
            (
                $typo3Mode == $verifyMode ||
                $typo3Mode == 'ALL'
            );

        return $result;
    }

    static public function initIpAddress (
        &$ipIsAllowed
    )
    {
        $ipAdress = static::readIpAddress();

        if (!$ipIsAllowed) {
            $ipIsAllowed = static::verifyIpAddress($ipAdress);
        }

        if ($ipIsAllowed) {
            $devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];

            if ($ipAdress == '*') {
                $devIPmask = '*';
            } else if ($ipAdress != '') {
                if ($devIPmask != '') {
                    $devIPmask .= ',' . $ipAdress;
                } else {
                    $devIPmask = $ipAdress;
                }
            }

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIPmask;
        }

// error_log ('initIpAddress $ipAdress ' . $ipAdress . PHP_EOL, 3, static::getErrorLogFilename());
        return $ipAdress;
    }

    static public function init (
        $ipAddress = ''
    )
    {
//  error_log('init $ipAddress: ' . print_r($ipAddress, true) . PHP_EOL, 3, static::getErrorLogFilename());
        $result = true;
        $startFiles = static::getStartFiles();
        $initialized = static::hasBeenInitialized();
//  error_log('init $initialized: ' . print_r($initialized, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            $initialized &&
            $startFiles == ''
        ) {
            return false;
        }

        $traceFieldArray = static::getTraceFieldArray();
        $trailOptions = DEBUG_BACKTRACE_IGNORE_ARGS;
        if (in_array('args', $traceFieldArray)) {
            $trailOptions = '';
        }
        $trail = debug_backtrace($trailOptions);
        $backtrace = static::getTraceArray($trail);

        if (
            $startFiles != '' &&
            is_array($backtrace) &&
            !empty($backtrace)
        ) {
            $startFileArray = GeneralUtility::trimExplode(',', $startFiles);
            $startFileFound = false;
            $traceRow = current($backtrace);

            foreach ($startFileArray as $startFile) {
                if ($traceRow['file'] == $startFile) {
                    $startFileFound = true;
                    break;
                }
            }

            if (!$startFileFound) {
                $result = false;
            }
        }

        static::setIsInitialization(true);

        if (
            $result &&
            !static::getDebugBegin()
        ) {
            static::setActive(true);
        }

        if (
            $result &&
            GeneralUtility::cmpIP($ipAddress, '::1')
        ) {
            if (
                !GeneralUtility::cmpIP(
                    $ipAddress,
                    static::getIpAddress()
                )
            ) {
                static::$starttimeArray =
                    [
                        'no debugging possible',
                        'Attention: The server variable REMOTE_ADDR is set to local.'
                    ];
                static::setActive(false);
                $result = false;
            }
        }

        if ($result) {
            static::setHasBeenInitialized(true);
        } else {
            static::setHasBeenInitialized(false);
        }
        static::setIsInitialization(false);
        return $result;
    }

    static public function initFile (&$errorText)
    {
        $result = true;
        $extConf = static::getExtConf();

        if (static::$isUserAllowed && static::getDebugFilename() != '') {

            $processFilename = static::getProcessFilename();
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
                } else {
                    $processCount = 1;
                }

                if (
                    $processCount > intval(static::getAppendDepth())
                ) {
                    $processCount = 1;
                    static::setCreateFile();
                }
                static::writeTemporaryFile($processCount);
            }

            $filename = static::getDebugFilename();
            $path_parts = pathinfo($filename);

            if (
                $filename != '' &&
                is_writable($path_parts['dirname'])
            ) {
                static::$headerWritten = static::getHtml();
                if (static::getAppendDepth() > 1) {
                    if (static::getCreateFile()) {
                        $openMode = 'w+b';
                    } else {
                        $openMode = 'a+b';
                    }
                } else {
                    $openMode = static::getDebugFileMode();
                }

                static::$hndFile = fopen($filename, $openMode);

                if (static::$hndFile !== false) {

                    $ipAddress = static::readIpAddress();
                    static::$starttimeArray =
                        [
                            date(static::getDateTime()) . '  (' . $ipAddress . ')',
                            'start time, date and IP of debug session (mode "' . $openMode . '")'
                        ];
                } else if (
                    !is_writable($filename)
                ) {
                    $result = false;
                    $errorText = 'DEBUGFILE: "' . $filename . '" is not writable in mode="' . $openMode . '"';
                }
            } else {
                $result = false;
                $errorText = 'DEBUGFILE: directory "' . $path_parts['dirname'] . '" is not writable for file "' . $filename . '" .';
            }

            if ($result == false) {
                if (
                    version_compare(TYPO3_version, '10.0.0', '<')
                ) {
                    if (static::getDevLogDebug()) {
                        GeneralUtility::devLog(
                            $errorText,
                            FH_DEBUG_EXT,
                            0
                        );
                        GeneralUtility::sysLog(
                            $errorText,
                            FH_DEBUG_EXT,
                            0
                        );
                    }
                } else {
                    error_log(FH_DEBUG_EXT . ': ' . $errorText . PHP_EOL);
                    error_log(FH_DEBUG_EXT . ': ' . $errorText . PHP_EOL, 3, static::getErrorLogFilename());
                }
                static::setActive(false); // no debug is necessary when the file cannot be written anyways
            }
        }
        return $result;
    }

    static public function getProcessFilename ()
    {
        $path = '';
        if (version_compare(TYPO3_version, '9.2.0', '>=')) {
            $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        } else {
            $path = PATH_site;
        }
        $result = $path . 'typo3temp/fh_debug.txt';

        return $result;
    }

    static public function getActive ()
    {
        return static::$active;
    }

    static public function setActive ($v)
    {
        static::$active = $v;
    }

    static public function setIsInitialization (
        $bInitialization
    )
    {
        static::$bInitialization = $bInitialization;
    }

    static public function bIsInitialization ()
    {
        return static::$bInitialization;
    }

    static public function setHasBeenInitialized (
        $hasBeenInitialized
    )
    {
        static::$hasBeenInitialized = $hasBeenInitialized;
    }

    static public function hasBeenInitialized ()
    {
        return static::$hasBeenInitialized;
    }

    static public function truncateFile ()
    {
// TODO
// 		if (static::$hndFile) {
// 			static::$hndFile = ftruncate(static::$hndFile, 0);
// 			static::writeTemporaryFile(0);
// 			static::setHasBeenInitialized(false);
// 		}
    }

    static public function setCreateFile ()
    {

        static::$createFile = true;
    }

    static public function getCreateFile ()
    {

        return static::$createFile;
    }

    static public function createInfoText () {
        $ipAddress = static::readIpAddress();
        $result = date(static::getDateTime()) . ', ' . $ipAddress;
        if (TYPO3_MODE == 'FE') {
            $result .= ', id=' . $GLOBALS['TSFE']->id;
        }
        return $result;
    }

    static public function debugBegin ()
    {
        static::$internalErrorLog = true;

        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
                static::setActive(true);

                $infoText = static::createInfoText();
                static::debug(
                    'debugBegin (' . $infoText . ') BEGIN [--->',
                    'debugBegin',
                    '',
                    '',
                    true
                );
            }
        }

        static::$internalErrorLog = false;
    }

    static public function debugEnd ()
    {
        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
                $infoText = static::createInfoText();
                static::debug(
                    'debugEnd (' . $infoText . ') END <---]',
                    'debugEnd',
                    '',
                    '',
                    true
                );
                static::setActive(false);
            }
        }
    }

    static public function getExtConf ()
    {
        $result = static::$extConf;

        return $result;
    }

    static public function getTraceFieldArray ()
    {
        $result = GeneralUtility::trimExplode(',',  static::getTraceFields());
        return $result;
    }

    static public function getTraceArray (
        $trail,
        $depth = 0,
        $offset = 0
    )
    {
        $last = count($trail) - 1;

        if (
            !$depth
        ) {
            $depth = $last + 1;
            $offset = 0;
        } else  if (
            $depth - $offset > $last
        ) {
            $depth = $last - $offset + 1;
        }

        $theFilename = basename(__FILE__);
        $traceFieldArray = static::getTraceFieldArray();
        $traceArray = [];
        $j = 0;

        for ($i = $offset; $i <= $last ; ++$i) {
            if (!isset($trail[$i])) {
                continue;
            }
            $theTrail = $trail[$i];
 
            if (
                !is_array($theTrail) ||
                $theTrail['file'] == '' ||
                $theTrail['line'] == '' ||
                isset($theTrail['class']) &&
                strpos($theTrail['class'], '\\FhDebug\\') !== false
            ) {
                continue;
            }

            foreach ($traceFieldArray as $traceField) {
                $traceValue = $theTrail[$traceField];
                if (
                    $traceField == 'file'
                ) {
                    $value = basename($traceValue);

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
            }

            $j++;
            if ($j > $depth) {
                break;
            }
        }

        return $traceArray;
    }

    static public function printTraceLine (
        array $traceArray,
        $html,
        $inverted = true
    )
    {
        $result = '';
        $debugTrail = [];

        if (!empty($traceArray)) {
            if ($inverted) {
                $traceArray = array_reverse($traceArray);
            }
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

    static public function printArrayVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    )
    {
        $result = '';

// error_log ('printArrayVariable $header = ' . $header . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('$variable = ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());
// error_log ('$depth = ' . $depth . PHP_EOL, 3, static::getErrorLogFilename());

        if ($depth < $recursiveDepth) {

            $debugArray = [];
            if ($html) {
                if ($header != '') {
                    $debugArray[] = '<tr><th>' . $header . '</th><th></th></tr>';
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
                            ($v1 ? 'true' : 'false'),
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
                    } else if (is_resource($v1) || ($v1 !== null && !is_scalar($v1))) {
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

    static public function object2array ($instance)
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

    static public function printObjectVariable (
        $header,
        $variable,
        $depth,
        $recursiveDepth,
        $html
    )
    { // TODO: show private member variables
        //Instantiate the reflection object
// error_log ('printObjectVariable $header = ' . print_r($header, true) . PHP_EOL, 3, static::getErrorLogFilename());

        $variableArray = static::object2array($variable);

// error_log ('printObjectVariable $variableArray = ' . print_r($variableArray, true) . PHP_EOL, 3, static::getErrorLogFilename());

        $classname = @get_class($variable);
        $header .= $classname;
        $result = static::printArrayVariable($header, $variableArray, $depth, $recursiveDepth, $html);

// error_log ('printObjectVariable $result = ' . print_r($result, true) . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function printVariable (
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
                static::printArrayVariable(
                    $header,
                    $variable,
                    0,
                    $recursiveDepth,
                    $html
                );
        } else if (is_object($variable)) {
            if ($header == '') {
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
                } else if (is_long($variable) || is_double($variable)) {
                    $result = '<td class="el">';
                    $result .= static::printTypeVariable(
                        ($header == '' ? ucfirst(gettype($variable)) : ''),
                        $variable,
                        true
                    );
                    $result .= '</td>';
                } else if (gettype($variable) == 'object') { // uninitialized object: is_object($variable) === false
                    $result = '<p>unloaded object of class "' . get_class($variable) . '"</p>';
                } else if (is_resource($variable)) {
                    $result = '<p>*RESOURCE*</p>';
                } else {
                    $result = '<p>' . nl2br(htmlspecialchars((string) $variable)) . '</p>';
                }
            } else {
                $result = $variable;
            }
        }

// 	error_log ('printVariable Ende $result = ' . $result . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function processUser ()
    {
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

                static::$isUserAllowed = $bAllowFeuser;

                if ($bAllowFeuser) {
                    static::$username = $username;
                }
            }
        }
    }

    static public function getTypeView ($variable)
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

    static public function writeTemporaryFile ($processCount)
    {
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
    )
    {
        $result = true;

        if ($errorOut != '') {
            // keep the following line
            $result = error_log($errorOut . PHP_EOL, 3, static::getErrorLogFilename()); // keep this
        }

        if (static::$hndFile) {
            fputs(static::$hndFile, $out);
        } else if ($bPrintOnScreen) {
            echo $out;
        } else {
            $result = false;
        }

        return $result;
    }

    static function readBackTrace () {
        $traceFieldArray = static::getTraceFieldArray();
        $trailOptions = DEBUG_BACKTRACE_IGNORE_ARGS;
        if (in_array('args', $traceFieldArray)) {
            $trailOptions = '';
        }
        $trail = debug_backtrace($trailOptions);

        $traceArray =
            static::getTraceArray(
                $trail,
                static::getTraceDepth(),
                0
            );
        $result = array_reverse($traceArray);
        return $result;
    }

    static public function writeOut (
        $variable,
        $title,
        $recursiveDepth,
        $html,
        $traceArray = [],
        $showHeader = false
    )
    {
        $type = '';
        $out = '';
        $errorOut = '';
        $backTrace = '';

        if ($showHeader) {
            $type = static::getTypeView($variable);
        }

//   error_log('writeOut $variable ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());
//   error_log('writeOut $title ' . $title . PHP_EOL, 3, static::getErrorLogFilename());
//   error_log('writeOut $recursiveDepth ' . $recursiveDepth . PHP_EOL, 3, static::getErrorLogFilename());

        $debugFile = static::getDebugFile();
//  error_log('writeOut $debugFile ' . $debugFile . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            static::$hndFile ||
            static::getUseErrorLog() ||
            $debugFile == ''
        ) {
            if (!empty($traceArray)) {
                $backTrace = static::printTraceLine($traceArray, $html, true);
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
                    '###' . $title . $type . '###' . PHP_EOL;
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
                    '<h3>' . $title . $type . '</h3>';
                $out .= chr(13) . $backTrace . chr(13) .
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
            $overwriteModeArray = ['x', 'x+', 'xb', 'x+b'];

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

    static function checkTrace ($traceArray) {
        $result = true;
        $partFiles = static::getPartFiles();
        $excludeFiles = static::getExcludeFiles();
        $partFileCheck = true;

//  error_log('checkTrace $traceArray: ' . print_r($traceArray, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            (
                $partFiles != '' ||
                $excludeFiles != ''
            ) && 
            is_array($traceArray) &&
            !empty($traceArray)
        ) {
            $partFileArray = GeneralUtility::trimExplode(',', $partFiles);
            $excludeFileArray = GeneralUtility::trimExplode(',', $excludeFiles);
            $partFileFound = false;
            if ($partFiles == '') {
                $partFileCheck = false;
            }
            $excludeFileFound = false;

            foreach ($traceArray as $traceRow) {
                if ($partFileCheck) {
                    foreach ($partFileArray as $partFile) {
                        if ($traceRow['file'] == $partFile) {
                            $partFileFound = true;
                            break;
                        }
                    }
                }

                foreach ($excludeFileArray as $excludeFile) {
                    if ($traceRow['file'] == $excludeFile) {
                        $excludeFileFound = true;
                        break;
                    }
                }

                if (
                    $partFileCheck && $partFileFound ||
                    $excludeFileFound
                ) {
                    break;
                }
            } 

            if (
                $partFileCheck && !$partFileFound ||
                $excludeFileFound
            ) {
                $result = false;
            }
        }

//  error_log('checkTrace $result: ' . print_r($result, true) . PHP_EOL, 3, static::getErrorLogFilename());
        return $result;
    }

    static public function debug (
        $variable = '',
        $title = null,
        $group = null
    )
    {
        $force = false;
        if (
            $title == '*variable*'
        ) { // backwards compatibility to TYPO3 8.7 where the second parameter is the former default value for variable
            $title = null;
        }

        // neu Anfang
        if (
            $group == 'F'
        ) { // backwards compatibility to TYPO3 8.7 where the third parameter is the former default value for __LINE__
            $force = true;
        }
        // neu Ende

        if (
            $group == '*line*'
        ) { // backwards compatibility to TYPO3 8.7 where the third parameter is the former default value for __LINE__
            $group = null;
        }

// error_log('### debug $variable = ' . print_r($variable, true) . PHP_EOL, 3, static::getErrorLogFilename());
// error_log('### debug $title = ' . print_r($title, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            $title === null &&
            $group === null &&
            is_string($variable)
        ) {
            $isControlMode = true;
            switch ($variable) {
                case 'B':
                case 'b':
                    static::debugBegin();
                    break;
                case 'E':
                case 'e':
                    static::debugEnd();
                    break;
                case 'resetTemporaryFile':
                    static::truncateFile();
                    break;
                default:
                    $isControlMode = false;
                    break;
            }

            if ($isControlMode) {
                return true;
            }
        }

        if (
            !$force &&
            GeneralUtility::inList(static::getIgnore(), $title) ||
            static::$internalError
        ) {
            return;
        }

        $storeIsActive = static::getActive();
        $isValidTrace = false;
        $charset = '';

        static::processUser();

        $debugSysLog = false;
        $debugDevLog = false;
        $excludeSysLog = false;

        if (
            static::getSysLog() &&
            isset($title) &&
            strpos($title, 'sysLog from ' . FH_DEBUG_EXT) !== false
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
                    $excludeSysLog = true;
                }
            }
        }

        if (
            static::getDevLog() &&
            isset($title) &&
            strpos($title, 'devLog from ' . FH_DEBUG_EXT) !== false
        ) {
            $debugDevLog = true;
        }

// error_log('### debug $storeIsActive = ' . print_r($storeIsActive, true) . PHP_EOL, 3, static::getErrorLogFilename());

        if (
            (
                $storeIsActive ||
                static::bIsInitialization() ||
                $debugSysLog && !$excludeSysLog ||
                $debugDevLog
            ) &&
            !self::getMaxFileSizeReached()
        ) {
            static::setActive(false);

            if (static::$isUserAllowed) {
                $recursiveDepth = null;
                if (is_object($variable)) {
                    $classname = get_class($variable);
                    $exceptionPos = strlen($classname) - strlen('Exception');
                    $comparator = substr($classname, $exceptionPos);
                    if ($comparator == 'Exception') {
                        $recursiveDepth = static::getExceptionRecursiveDepth();
                    }
                }

                if (!isset($recursiveDepth)) {
                    $recursiveDepth = static::getRecursiveDepth();
                }

                if (static::$needsFileInit) {
                    $errorText = '';
                    $resultInit = static::initFile($errorText);
                    if ($resultInit) {
                        static::$needsFileInit = false;
                    } else {
                        static::$internalError = true;
                        echo $errorText;
                        error_log(FH_DEBUG_EXT . ': ' . $errorText, 0); // It must be written directly to the PHP error_log file, because this debug extension must work from the beginning before TYPO3 might have initialized its objects.
                        return false;
                    }
                }

                if (static::$headerWritten) {
                    $headerPostFix = '';
                    $headerValue = '';

                    $cssPath = '';
                    $extConf = static::getExtConf();
                    if (
                        ($position = strpos($extConf['CSSPATH'], 'EXT:' . FH_DEBUG_EXT)) !== false
                    ) {
                        $subdirectory = '';
                        if ($position > 0) {
                            $subdirectory = substr($extConf['CSSPATH'], 0, $position);
                        } else {
                            $slashArray = preg_split('$/$', $_SERVER['SCRIPT_NAME'], -1, PREG_SPLIT_NO_EMPTY);
                            if (
                                is_array($slashArray) &&
                                count($slashArray) > 1
                            ) {
                                array_pop($slashArray);
                                $subdirectory = implode('/', $slashArray);
                                $subdirectory .= '/';
                            }
                            if (TYPO3_MODE == 'BE') {
                                $position = strpos($subdirectory, 'typo3/');
                                    // Remove the 'typo3' part of the directory in order not to have a duplicate of it.
                                $subdirectory = substr($subdirectory, 0, $position);
                            }
//  error_log('Pos 2 $subdirectory = ' . print_r($subdirectory, TRUE) . PHP_EOL, 3, static::getErrorLogFilename());
                        }

                        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
                            $relPath =                  
                                \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(FH_DEBUG_EXT)
                                );
                        } else {
                            $relPath =  \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath(FH_DEBUG_EXT);
                        }                        
                        $cssPath = 'http' . ($_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/' . $subdirectory . $relPath . 'Resources/Public/Css/';
                    } else {
                        $cssPath = $extConf['CSSPATH'];
                    }
                    static::writeHeader($cssPath . $extConf['CSSFILE']);
                    static::$headerWritten = false;

                    if (count(static::$starttimeArray)) {
                        $headerPostFix = static::$starttimeArray['1'];
                        $headerValue = static::$starttimeArray['0'];
                        static::$starttimeArray = [];
                    }

                    $appendText = ' - counter: ' . static::$processCount . ' ' . $headerPostFix;
                    switch (TYPO3_MODE) {
                        case 'FE':
                            if (static::getHtml()) {
                                $head = 'Front End Debugging' . chr(13) . $appendText;
                            } else {
                                $head = '#Front End Debugging' . $appendText . '#';
                            }
                            break;
                        case 'BE':
                            if (static::getHtml()) {
                                $head = 'Back End Debugging' . chr(13) . $appendText;
                            } else {
                                $head = '#Back End Debugging' . $appendText . '#';
                            }
                            break;
                    }

                    static::writeOut(
                        $headerValue,
                        $head,
                        $recursiveDepth,
                        static::getHtml(),
                        [],
                        true
                    );
                }
                $traceArray = static::readBackTrace();
                $isValidTrace = static::checkTrace($traceArray);
//  error_log('debug $isValidTrace = ' . $isValidTrace . PHP_EOL, 3, static::getErrorLogFilename());

                if ($isValidTrace) {
                    static::writeOut(
                        $variable,
                        $title,
                        $recursiveDepth,
                        static::getHtml(),
                        $traceArray,
                        false
                    );

                    if (static::$hndFile) {
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
                                    [],
                                    false
                                );
                            }
                        }
                    }
                }
            }

            if (!$isValidTrace || !self::getMaxFileSizeReached()) {
                static::setActive($storeIsActive);
            }
        }
    }

    /**
    * Returns the internal debug messages as a string.
    *
    * @return string
    */
    static public function toString ()
    {
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

    static public function close ()
    {
        if (static::$hndFile) {

            $headerValue = date(static::getDateTime()) . '  (' . static::readIpAddress() . ')';
            $head = '=== END time, date and IP of debug session  ===';

            static::writeOut(
                $headerValue,
                $head,
                static::getRecursiveDepth(),
                static::getHtml(),
                [],
                true
            );

            static::$instanceCount--;

            if (!static::$instanceCount && static::getAppendDepth() == '0') {
                static::writeBodyEnd();
            }

            if (!static::$instanceCount) {
                fclose(static::$hndFile);
                static::setHasBeenInitialized(false);
                static::$hndFile = null; // this is a static class which remains even after the closing of the object
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

