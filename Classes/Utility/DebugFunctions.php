<?php

namespace JambageCom\FhDebug\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2024 Franz Holzinger (franz@ttproducts.de)
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

use Psr\Http\Message\ServerRequestInterface;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use JambageCom\FhDebug\Api\DebugApi;
use JambageCom\FhDebug\Api\OldDebugApi;


/**
* Debug extension.
*
* @author	Franz Holzinger <franz@ttproducts.de>
*
*/
class DebugFunctions
{
    public const BEGIN = 'B';
    public const END = 'E';
    public const RESET = 'RESET';
    public const CONFIG = 'CONFIG';
    public const ERROR_LOG_MAX = 64;

    public static $extensionKey = 'fh_debug';
    public static $prefixFieldArray =
        [
            'file' => '',
            'line' => '#',
            'function' => '->'
        ];
    public static $csConvObj;
    public static $errorLogFile = 'fileadmin/phpDebugErrorLog.txt';
    public static $debugFile = '';
    public static $internalErrorLog = false;

    protected static $active = false;	// inactive without initialization
    protected static $bInitialization = false;
    protected static $internalError = false;
    protected static $bErrorWritten = false;
    protected static $useErrorLog = false;

    private static $username = '';
    private static $isUserAllowed = true;
    private static $extConf = [];
    private static $hndFile = 0;
    private static $hasBeenInitialized = false;
    private static $needsFileInit = true;
    private static $starttimeArray = [];
    private static $createFile = false;
    private static $hndProcessfile = false;
    private static $processCount = 0;
    private static $recursiveDepth = 3;
    private static $exceptionRecursiveDepth = 3;
    private static $traceDepth = 5;
    private static $appendDepth = 3;
    private static $html = true;
    private static $headerWritten = false;
    private static $instanceCount = 0;
    private static $errorLogFilename = '';
    private static $debugFilename = '';
    private static $typo3Mode = 'ALL';
    private static $currentTypo3Mode = 'FE';
    private static $startFiles = '';
    private static $partFiles = '';
    private static $excludeFiles = '';
    private static $ipAddress = '127.0.0.1';
    private static $debugBegin = false;
    private static $traceFields = 'file,line,function';
    private static $feUserNames = '';
    private static $debugFileMode = 'wb';
    private static $devLog = false;
    private static $devLogDebug = false;
    private static $sysLog = false;
    private static $sysLogExclude = '';
    private static $proxyForward = false;
    private static $title = 'debug file';
    private static $maxFileSize = 3.0;
    private static $maxFileSizeReached = false;
    private static $minFreeMemory = 0;
    private static $minFreeMemoryReached = false;
    private static $memoryAvailable = 1000000;
    private static $dateTime = 'l jS \of F Y h:i:s A';
    private static $config = [];
    private static $api;
    private static $id = '';
    private static $request;

    public function __construct(
        ServerRequestInterface $request,
        array $extConf,
        $currentTypo3Mode = 'FE'
    ) {
        static::$request = $request;
        static::$currentTypo3Mode = $currentTypo3Mode;
        static::$extConf = $extConf;
        static::$memoryAvailable = static::return_bytes(ini_get('memory_limit'));

        static::$instanceCount++;
        static::$csConvObj =  GeneralUtility::makeInstance(CharsetConverter::class);
        static::setErrorLogFile($extConf['ERROR_LOG'] ?? static::$errorLogFile);

        if ($extConf['USE_ERROR_LOG'] == '1') {
            static::setUseErrorLog(true);
        }

        if (isset($extConf['DEBUGFILE'])) {
            static::setDebugFile($extConf['DEBUGFILE']);
        }
        if (isset($extConf['DEBUGFILEMODE'])) {
            static::setDebugFileMode($extConf['DEBUGFILEMODE']);
        }
        if (isset($extConf['LEVEL'])) {
            static::setRecursiveDepth($extConf['LEVEL']);
        }
        if (isset($extConf['LEVEL_EXCEPTION'])) {
            static::setExceptionRecursiveDepth($extConf['LEVEL_EXCEPTION']);
        }
        if (isset($extConf['TRACEDEPTH'])) {
            static::setTraceDepth($extConf['TRACEDEPTH']);
        }
        if (isset($extConf['APPENDDEPTH'])) {
            static::setAppendDepth($extConf['APPENDDEPTH']);
        }
        if (isset($extConf['STARTFILES'])) {
            static::setStartFiles($extConf['STARTFILES']);
        }
        if (isset($extConf['PARTFILES'])) {
            static::setPartFiles($extConf['PARTFILES']);
        }
        if (isset($extConf['EXCLUDEFILES'])) {
            static::setExcludeFiles($extConf['EXCLUDEFILES']);
        }
        if (isset($extConf['IPADDRESS'])) {
            static::setIpAddress($extConf['IPADDRESS']);
        }
        if (isset($extConf['DEBUGBEGIN'])) {
            static::setDebugBegin($extConf['DEBUGBEGIN']);
        }
        if (isset($extConf['TRACEFIELDS'])) {
            static::setTraceFields($extConf['TRACEFIELDS']);
        }
        if (isset($extConf['FEUSERNAMES'])) {
            static::setFeUserNames($extConf['FEUSERNAMES']);
        }
        if (isset($extConf['DEVLOG'])) {
            static::setDevLog($extConf['DEVLOG']);
        }
        if (isset($extConf['DEVLOGDEBUG'])) {
            static::setDevLogDebug($extConf['DEVLOGDEBUG']);
        }
        if (isset($extConf['SYSLOG'])) {
            static::setSysLog($extConf['SYSLOG']);
        }
        if (isset($extConf['SYSLOG_EXCLUDE'])) {
            static::setSysLogExclude($extConf['SYSLOG_EXCLUDE']);
        }
        if (isset($extConf['HTML'])) {
            static::setHtml($extConf['HTML']);
        }
        if (isset($extConf['PROXY'])) {
            static::setProxyForward($extConf['PROXY']);
        }
        if (isset($extConf['TITLE'])) {
            static::setTitle($extConf['TITLE']);
        }
        if (isset($extConf['MAXFILESIZE'])) {
            static::setMaxFileSize($extConf['MAXFILESIZE']);
        }
        if (isset($extConf['MINFREEMEMORY'])) {
            static::setMinFreeMemory($extConf['MINFREEMEMORY']);
        }
        if (isset($extConf['DATETIME'])) {
            static::setDateTime($extConf['DATETIME']);
        }

        static::setTypo3Mode($extConf['TYPO3_MODE'] ?: 'OFF');
        static::setDeterminedId();

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            static::$api =
                GeneralUtility::makeInstance(
                    DebugApi::class,
                    $extConf
                );
        } else {
            static::$api =
                GeneralUtility::makeInstance(
                    OldDebugApi::class,
                    $extConf
                );
        }
    }

    public static function init(
        $ipAddress = ''
    ) {
        $result = true;
        $startFiles = static::getStartFiles();
        $initialized = static::hasBeenInitialized();

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

    public static function initFile(&$errorText)
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
                (
                    is_writable($filename) ||
                    is_writable($path_parts['dirname'])
                )
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
                } elseif (
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
                error_log(static::$extensionKey . ': ' . $errorText . PHP_EOL); // keep this
                error_log(static::$extensionKey . ': ' . $errorText . PHP_EOL, 3, static::getErrorLogFilename()); // keep this
                static::setActive(false); // no debug is necessary when the file cannot be written anyways
            }
        }
        return $result;
    }

    public static function setTypo3Mode(
        $value
    ): void {
        static::$typo3Mode = strtoupper($value);
    }

    public static function getTypo3Mode()
    {
        return static::$typo3Mode;
    }

    public static function setRecursiveDepth(
        $value
    ): void {
        static::$recursiveDepth = intval($value);
    }

    public static function getRecursiveDepth()
    {
        return static::$recursiveDepth;
    }

    public static function setExceptionRecursiveDepth(
        $value
    ): void {
        static::$exceptionRecursiveDepth = intval($value);
    }

    public static function getExceptionRecursiveDepth()
    {
        return static::$exceptionRecursiveDepth;
    }

    public static function setTraceDepth(
        $value
    ): void {
        static::$traceDepth = intval($value);
    }

    public static function getTraceDepth()
    {
        return static::$traceDepth;
    }

    public static function setAppendDepth(
        $value
    ): void {
        static::$appendDepth = intval($value);
    }

    public static function getAppendDepth()
    {
        return static::$appendDepth;
    }

    public static function setStartFiles(
        $value
    ): void {
        static::$startFiles = trim($value);
    }

    public static function getStartFiles()
    {
        return static::$startFiles;
    }

    public static function setPartFiles(
        $value
    ): void {
        static::$partFiles = trim($value);
    }

    public static function getPartFiles()
    {
        return static::$partFiles;
    }

    public static function setExcludeFiles(
        $value
    ): void {
        static::$excludeFiles = trim($value);
    }

    public static function getExcludeFiles()
    {
        return static::$excludeFiles;
    }

    public static function setIpAddress(
        $value
    ): void {
        static::$ipAddress = trim($value);
    }

    public static function getIpAddress()
    {
        return static::$ipAddress;
    }

    public static function setDebugBegin(
        $value
    ): void {
        static::$debugBegin = (bool) ($value);
    }

    public static function getDebugBegin()
    {
        return static::$debugBegin;
    }

    public static function setTraceFields(
        $value
    ): void {
        static::$traceFields = trim($value);
    }

    public static function getTraceFields()
    {
        return static::$traceFields;
    }

    public static function setFeUserNames(
        $value
    ): void {
        static::$feUserNames = trim($value);
    }

    public static function getFeUserNames()
    {
        return static::$feUserNames;
    }

    public static function setDebugFileMode(
        $value
    ): void {
        static::$debugFileMode = trim($value);
    }

    public static function getDebugFileMode()
    {
        return static::$debugFileMode;
    }

    public static function setDevLog(
        $value
    ): void {
        static::$devLog = (bool) $value;
    }

    public static function getDevLog()
    {
        return static::$devLog;
    }

    public static function setDevLogDebug(
        $value
    ): void {
        static::$devLogDebug = (bool) $value;
    }

    public static function getDevLogDebug()
    {
        return static::$devLogDebug;
    }

    public static function setSysLog(
        $value
    ): void {
        static::$sysLog = (bool) $value;
    }

    public static function getSysLog()
    {
        return static::$sysLog;
    }

    public static function setSysLogExclude(
        $value
    ): void {
        static::$sysLogExclude = $value;
    }

    public static function getSysLogExclude()
    {
        return static::$sysLogExclude;
    }

    public static function setHtml(
        $value
    ): void {
        static::$html = (bool) $value;
    }

    public static function getHtml()
    {
        return static::$html;
    }

    public static function setProxyForward(
        $value
    ): void {
        static::$proxyForward = (bool) $value;
    }

    public static function getProxyForward()
    {
        return static::$proxyForward;
    }

    public static function setTitle(
        $value
    ): void {
        static::$title = $value;
    }

    public static function getTitle()
    {
        return static::$title;
    }

    public static function getErrorLogFile()
    {
        return static::$errorLogFile;
    }

    public static function setErrorLogFile(
        $errorLogFile = ''
    ): void {
        if ($errorLogFile == '') {
            $errorLogFile = static::getErrorLogFile();
        } else {
            static::$errorLogFile = $errorLogFile;
        }

        $path = Environment::getPublicPath() . '/';

        static::$errorLogFilename = $path . $errorLogFile;
    }

    public static function getErrorLogFilename()
    {
        return static::$errorLogFilename;
    }

    public static function setUseErrorLog(
        $useErrorLog = true
    ): void {
        static::$useErrorLog = $useErrorLog;
    }

    public static function getUseErrorLog()
    {
        return static::$useErrorLog;
    }

    public static function dump($variable)
    {
        $cloner = new VarCloner();
        $cloner->setMaxString(static::ERROR_LOG_MAX);
        $dumper = new CliDumper();
        $output = '';
        $dumper->dump(
            $cloner->cloneVar($variable),
            function (string $line, int $depth) use (&$output): void {
                // A negative depth means "end of dump"
                if (
                    $depth >= 0 &&
                    strlen($output) < static::ERROR_LOG_MAX
                ) {
                    // Adds a two spaces indentation to the line
                    $output .= str_repeat('  ', $depth) . $line . PHP_EOL;
                }
            }
        );

        return $output;
    }

    public static function errorLog($variable, string $comment = ''): bool
    {
        if (!is_string($variable)) {
            $variable = static::dump($variable);
        }

        $result = error_log(
            ($comment ? $comment . ': ' : '') .
            substr($variable, 0, static::ERROR_LOG_MAX) . PHP_EOL,
            3,
            static::getErrorLogFilename()
        ); // keep this

        return $result;
    }

    public static function setDebugFile(
        $debugFile = ''
    ): void {
        if ($debugFile == '') {
            $debugFile = static::getDebugFile();
        } else {
            static::$debugFile = $debugFile;
        }

        $path = Environment::getPublicPath() . '/';

        static::setDebugFilename($path . $debugFile);
    }

    public static function getDebugFile()
    {
        return static::$debugFile;
    }

    public static function setDebugFilename($debugFilename): void
    {
        static::$debugFilename = $debugFilename;
    }

    public static function getDebugFilename()
    {
        return static::$debugFilename;
    }

    public static function setMaxFileSizeReached(
        $value
    ): void {
        static::$maxFileSizeReached = $value;
    }

    public static function getMaxFileSizeReached()
    {
        return static::$maxFileSizeReached;
    }

    public static function setMaxFileSize(
        $value
    ): void {
        static::$maxFileSize = (int) $value;
    }

    public static function getMaxFileSize()
    {
        return static::$maxFileSize;
    }

    public static function setMinFreeMemoryReached(
        $value
    ): void {
        static::$minFreeMemoryReached = $value;
    }

    public static function getMinFreeMemoryReached()
    {
        return static::$minFreeMemoryReached;
    }

    public static function setMinFreeMemory(
        $value
    ): void {
        static::$minFreeMemory = (int) $value;
    }

    public static function getMinFreeMemory()
    {
        return static::$minFreeMemory;
    }

    public static function setDateTime(
        $value
    ): void {
        static::$dateTime = $value;
    }

    public static function getDateTime()
    {
        return static::$dateTime;
    }

    public static function hasError()
    {
        $result = (static::$bErrorWritten);
        return $result;
    }

    public static function writeHeader(
        $cssFilename // filename with path
    ): void {
        $title = static::getTitle();

        if (
            static::$currentTypo3Mode == 'FE' &&
            !static::getAppendDepth()
        ) {
            if (
                isset($GLOBALS['TSFE']) &&
                is_object($GLOBALS['TSFE'])
            ) {
                $title .= ' id=' . $GLOBALS['TSFE']->id;
            } else {
                $title .= ' id: unknown';
            }
        }

        $out = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . $title . '</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<link href="' . $cssFilename . '" rel="stylesheet" media="screen" type="text/css"/>
</head>

<body>
';

        $errorOut = '';

        if (static::getUseErrorLog()) {
            $errorOut = '=>';
        }
        static::write($out, $errorOut, (static::getDebugFile() == ''));
    }

    public static function writeBodyEnd(): void
    {
        $out =
'</body></html>';

        $errorOut = '';

        if (static::getUseErrorLog()) {
            $errorOut = '<=';
        }
        static::write($out, $errorOut, (static::getDebugFile() == ''));
    }

    public static function readIpAddress()
    {
        $ipAddress = '';
        // Nothing to do without any reliable information
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (
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

    public static function verifyIpAddress(
        $ipAddress
    ) {
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

    public static function verifyFeusername(
        $username
    ) {
        $result = true;
        $feUserNames = static::getFeUserNames();

        if (
            static::$currentTypo3Mode == 'FE' &&
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

    public static function verifyTypo3Mode(
        $verifyMode
    ) {
        $typo3Mode = static::getTypo3Mode();
        $result =
            (
                $typo3Mode == $verifyMode ||
                $typo3Mode == 'ALL'
            );

        return $result;
    }

    public static function initIpAddress(
        &$ipIsAllowed
    ) {
        $ipAdress = static::readIpAddress();

        if (!$ipIsAllowed) {
            $ipIsAllowed = static::verifyIpAddress($ipAdress);
        }

        if ($ipIsAllowed) {
            $devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'];

            if ($ipAdress == '*') {
                $devIPmask = '*';
            } elseif ($ipAdress != '') {
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

    public static function getProcessFilename()
    {
        $path = Environment::getPublicPath() . '/';
        $result = $path . 'typo3temp/fh_debug.txt';

        return $result;
    }

    public static function getActive()
    {
        return static::$active;
    }

    public static function setActive($v): void
    {
        static::$active = $v;
        //  error_log ('setActive: $v = ' . $v . PHP_EOL, 3, static::getErrorLogFilename());
    }

    public static function setDeterminedId(): void
    {
        $id = '';

        if (static::$currentTypo3Mode == 'FE') {
            $id = 'unknown';

            if (
                isset($GLOBALS['TSFE']) &&
                is_object($GLOBALS['TSFE'])
            ) {
                if (!isset($GLOBALS['TSFE']->id)) {
                    $GLOBALS['TSFE']->determineId(static::$request);
                }

                if (isset($GLOBALS['TSFE']->id)) {
                    $id = $GLOBALS['TSFE']->id;
                }
            }
        }
        static::$id = $id;
    }

    public static function getId()
    {
        return static::$id;
    }


    public static function setIsInitialization(
        $bInitialization
    ): void {
        static::$bInitialization = $bInitialization;
    }

    public static function bIsInitialization()
    {
        return static::$bInitialization;
    }

    public static function setHasBeenInitialized(
        $hasBeenInitialized
    ): void {
        static::$hasBeenInitialized = $hasBeenInitialized;
    }

    public static function hasBeenInitialized()
    {
        return static::$hasBeenInitialized;
    }

    public static function truncateFile(): void
    {
        // TODO
        // 		if (static::$hndFile) {
        // 			static::$hndFile = ftruncate(static::$hndFile, 0);
        // 			static::writeTemporaryFile(0);
        // 			static::setHasBeenInitialized(false);
        // 		}
    }

    public static function setCreateFile(): void
    {

        static::$createFile = true;
    }

    public static function getCreateFile()
    {

        return static::$createFile;
    }

    public static function createInfoText()
    {
        $ipAddress = static::readIpAddress();
        $result = date(static::getDateTime()) . ', ' . $ipAddress;
        $id = static::getId();
        if (strlen($id)) {
            $result .= ' id: ' . $id;
        }
        return $result;
    }

    public static function debugBegin(): void
    {
        static::$internalErrorLog = true;

        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
                static::setActive(true);

                static::setDeterminedId();
                $infoText = static::createInfoText();
                static::debug(
                    'debugBegin (' . $infoText . ') BEGIN [--->',
                    'debugBegin',
                    ''
                );
            }
        }
// static::errorLog('debugBegin ');

        static::$internalErrorLog = false;
    }

    public static function debugEnd(): void
    {
        if (static::hasBeenInitialized() && !static::hasError()) {

            if (static::getDebugBegin()) {
                $infoText = static::createInfoText();
                static::debug(
                    'debugEnd (' . $infoText . ') END <---]',
                    'debugEnd',
                    ''
                );
                static::setActive(false);
            }
        }
// static::errorLog('debugEnd ');
    }

    public static function getExtConf()
    {
        $result = static::$extConf;

        return $result;
    }

    public static function getTraceFieldArray()
    {
        $result = GeneralUtility::trimExplode(',', static::getTraceFields());
        return $result;
    }

    public static function getTraceArray(
        $trail,
        $depth = 0,
        $offset = 0
    ) {
        $last = count($trail) - 1;

        if (
            !$depth
        ) {
            $depth = $last + 1;
            $offset = 0;
        } elseif (
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
                isset($theTrail['file']) &&
                $theTrail['file'] == '' ||
                isset($theTrail['line']) &&
                $theTrail['line'] == '' ||
                isset($theTrail['class']) &&
                str_contains($theTrail['class'], '\\FhDebug\\')
            ) {
                continue;
            }

            foreach ($traceFieldArray as $traceField) {
                if (isset($theTrail[$traceField])) {
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
            }

            $j++;
            if ($j > $depth) {
                break;
            }
        }

        return $traceArray;
    }

    public static function printTraceLine(
        array $traceArray,
        $html,
        $inverted = true
    ) {
        $result = '';
        $debugTrail = [];

        if (!empty($traceArray)) {
            if ($inverted) {
                $traceArray = array_reverse($traceArray);
            }
            foreach ($traceArray as $i => $trace) {
                $debugTrail[$i] = '';
                if ($html) {
                    $debugTrail[$i] .= '<tr>';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .= '<td>'; //  bgcolor="#E79F9F"
                        $debugTrail[$i] .=  static::$prefixFieldArray[$field] . $v;
                        $debugTrail[$i] .= '</td>';
                    }
                    $debugTrail[$i] .= '</tr>';
                } else {
                    $debugTrail[$i] .= '|';
                    foreach ($trace as $field => $v) {
                        $debugTrail[$i] .=  static::$prefixFieldArray[$field] . $v;
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

    public static function processUser(): void
    {
        if (
            static::$currentTypo3Mode == 'FE' &&
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

    public static function writeTemporaryFile($processCount): void
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

    public static function write(
        $out,
        $errorOut,
        $bPrintOnScreen
    ) {
        $result = true;

        if ($errorOut != '') {
            // keep the following line
            $result = static::errorLog($errorOut); // keep this
        }

        if (static::$hndFile) {
            fputs(static::$hndFile, $out);
        } elseif ($bPrintOnScreen) {
            echo $out;
        } else {
            $result = false;
        }

        return $result;
    }

    public static function readBackTrace()
    {
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

    public static function writeOut(
        $variable,
        $title,
        $recursiveDepth,
        $html,
        $traceArray = [],
        $showHeader = false
    ) {
        $type = '';
        $out = '';
        $errorOut = '';
        $backTrace = '';

        if ($showHeader) {
            $type = static::$api->getTypeView($variable);
        }

        $debugFile = static::getDebugFile();
        $debugFilename = static::getDebugFilename();

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
                    static::$api->printVariable(
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

            if ($html) {
                $out =
                    static::$api->printVariable(
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
        }

        $bWritten = static::write($out, $errorOut, ($debugFile == ''));

        if (
            !$bWritten &&
            !static::hasError()
        ) {
            $overwriteModeArray = ['x', 'x+', 'xb', 'x+b'];

            if (
                file_exists($debugFilename) &&
                in_array(static::getDebugFileMode(), $overwriteModeArray)
            ) {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not empty.</b>';
            } else {
                echo '<b>DEBUGFILE: "' . $debugFile . '" is not writable.</b>';
            }
            static::$bErrorWritten = true;
        }
        // static::errorLog($bWritten, 'writeOut $bWritten');

        return $bWritten;
    }

    public static function checkTrace($traceArray)
    {
        $result = true;
        $partFiles = static::getPartFiles();
        $excludeFiles = static::getExcludeFiles();
        $partFileCheck = true;

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

        return $result;
    }

    public static function getSubdirectory()
    {
        $result = '';
        $slashArray = preg_split('$/$', $_SERVER['SCRIPT_NAME'], -1, PREG_SPLIT_NO_EMPTY);
        if (
            is_array($slashArray) &&
            count($slashArray) > 1
        ) {
            array_pop($slashArray);
            $result = implode('/', $slashArray);
            $result .= '/';
        }
        if (static::$currentTypo3Mode == 'BE') {
            $position = strpos($result, 'typo3/');
            // Remove the 'typo3' part of the directory in order not to have a duplicate of it.
            $result = substr($result, 0, $position);
        }
        return $result;
    }

    public static function getHost()
    {
        $result = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

        return $result;
    }

    public static function getMemorySize($value) {
        // static::errorLog('getMemorySize', 'ANFANG');
        // existing variable with integer value so that the next line
        // does not add memory consumption when initiating $start variable
        $start = 1;
        $start = memory_get_usage();
        // static::errorLog('getMemorySize ' . $start, 'START');
        // json functions return less bytes consumptions than serialize
        $encoded = json_encode($value);
        // static::errorLog('getMemorySize ' . $encoded, 'encoded');
        if ($encoded !== false) {
            $tmp = json_decode($encoded);
        }
        unset($encoded);
        $result = memory_get_usage() - $start;
        // static::errorLog('getMemorySize' . $result, '$result');
        return $result;
    }

    protected static function return_bytes($val)
    {
        $val = trim($val);
        $num = (int) rtrim($val, 'KMG');
        $last = strtolower($val[strlen($val) - 1]);

        switch ($last) {
            // The 'G' modifier is available
            case 'g':
                $num = $num * 1024 * 1024 * 1024;
                break;
            case 'm':
                $num = $num * 1024 * 1024;
                break;
            case 'k':
                $num *= 1024;
                break;
        }

        return $num;
    }

    protected static function processFreeMemory($variable)
    {
        // static::errorLog('processFreeMemory: ', 'ANFANG');
        $result = true;
        // static::errorLog('processFreeMemory: ', 'Pos 1');

        $total = memory_get_usage(true);

    // static::errorLog('static::memoryAvailable ' . static:::$memoryAvailable, 'processFreeMemory');
        $memoryUsed = memory_get_peak_usage(false);
    // static::errorLog('memoryUsed ' . $memoryUsed, 'processFreeMemory');

        // static::errorLog('processFreeMemory: ' . $total, 'total');
        // $used = memory_get_usage(false);
        // static::errorLog('processFreeMemory: ' . $used, 'used');
        $free = static::$memoryAvailable - $memoryUsed;
    // static::errorLog('processFreeMemory: ' . $free, 'free');

        $required = static::getMemorySize($variable);
        // static::errorLog('processFreeMemory $required: ' . $required, 'Pos 1');
        $minFree = self::getMinFreeMemory() * (1024 * 1024);
        // static::errorLog('processFreeMemory $minFree: ' . $minFree, 'Pos 2');
        $errorText = 'Minimum free memory reached for the debug output file.';

        // avoid a memory exception
        if (
            $free - $required < $minFree &&
            $minFree > 0
        ) {
            self::setMinFreeMemoryReached(true);
            $errorText = 'Minimum free memory reached for the debug output file.';
            // static::errorLog('ERROR Text: ', $errorText);
            if (static::$hndFile) {
                static::writeOut(
                    $minFree . ' MByte',
                    static::$extensionKey . ': ' . $errorText,
                    0,
                    static::getHtml(),
                    [],
                    false
                );
            } else {
                // static::errorLog($minFree . ' MByte', $errorText);
            }
            $result = false;
        }
        // static::errorLog('processFreeMemory: ' . $result, 'ENDE');
        return $result;
    }

    public static function debug(
        $variable = '',
        $title = null,
        $group = null
    )
    {
        $force = false;
        $storeIsActive = static::getActive();
        // static::errorLog($title, '### debug $title');

        if (
            self::getMinFreeMemoryReached()
        ) {
            // error_log('### debug getMinFreeMemoryReached ' . PHP_EOL, 3, static::getErrorLogFilename());
            return false;
        }

        if (
            $group == 'F'
        ) { // force a debug output no matter which other options are active
            $force = true;
        }

        if (
            $title === null &&
            $group === null &&
            is_string($variable)
        ) {
            $isControlMode = true;
            $variable = strtoupper($variable);
            $parts = explode(':', $variable);
            $variable = $parts[0];
            switch ($variable) {
                case static::BEGIN:
                    static::debugBegin();
                    break;
                case static::CONFIG:
                    // TODO: $parts[1] enthält den Index auf die
                    //                     $config[$variant]
                    // Die gesamte Konfiguration muss in einem Array gespeichert werden..
                    // setConfigVariant und getConfigVariant. Damit muss außerdem ein
                    // Konfigurations-Array von Basis-Elementen (nicht alles erforderlich)
                    // und sein Index ausgefüllt werden. Index 0 ist der Default-Wert aus den
                    // Extension Manager Einstellungen
                    break;
                case static::END:
                    static::debugEnd();
                    break;
                case static::RESET:
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
            GeneralUtility::inList(static::$api->getIgnore(), $title) ||
            static::$internalError
        ) {
            // static::errorLog('', '### debug internal exit');
            return;
        }

        $isValidTrace = false;
        $charset = '';

        static::processUser();

        $debugSysLog = false;
        $debugDevLog = false;
        $excludeSysLog = false;

        if (
            static::getSysLog() &&
            isset($title) &&
            str_contains($title, 'sysLog from ' . static::$extensionKey)
        ) {
            $debugSysLog = true;

            if (
                is_array($variable) &&
                isset($variable['backTrace']) &&
                is_array($variable['backTrace']) &&
                isset($variable['backTrace']['args']) &&
                is_array($variable['backTrace']['args']) &&
                isset($variable['backTrace']['args'][0])
            ) {
                $sysLogTopic = $variable['backTrace']['args'][0];
                $expression = '/' . preg_quote(static::getSysLogExclude(), '/') . '/';
                preg_match($expression, $sysLogTopic, $matches);

                if (
                    !empty($matches) &&
                    !empty($matches[0])
                ) {
                    $debugSysLog = false;
                    $excludeSysLog = true;
                }
            }
        }

        if (
            static::getDevLog() &&
            isset($title) &&
            str_contains($title, 'devLog from ' . static::$extensionKey)
        ) {
            $debugDevLog = true;
        }

        // static::errorLog('### debug $storeIsActive = ' . $storeIsActive);

        if (
            (
                $storeIsActive ||
                $force ||
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
                    $classname = static::$api->getClass($variable);
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
                        static::errorLog($errorText, static::$extensionKey);  // keep this. It must be written directly to the PHP error_log file, because this debug extension must work from the beginning when TYPO3 might not have initialized basic objects
                        return false;
                    }
                }

                $resultFreeMemory = static::processFreeMemory($variable);
                if (!$resultFreeMemory) {
                    // static::errorLog($variable, 'no free memory available!');
                    return false;
                }

                // static::errorLog($variable, '### debug $variable');

                if (static::$headerWritten) {
                    $headerPostFix = '';
                    $headerValue = '';

                    $cssPath = '';
                    $extConf = static::getExtConf();
                    if (
                        ($position = strpos($extConf['CSSPATH'], 'EXT:' . static::$extensionKey)) !== false
                    ) {
                        $subdirectory = '';
                        if ($position > 0) {
                            $subdirectory = substr($extConf['CSSPATH'], 0, $position);
                        } else {
                            $subdirectory = static::getSubdirectory();
                        }

                        $relPath =
                            PathUtility::stripPathSitePrefix(
                                ExtensionManagementUtility::extPath(static::$extensionKey)
                            );
                        $cssPath = static::getHost() . '/' . $subdirectory . $relPath . 'Resources/Public/Css/';
                    } else {
                        $cssPath = $extConf['CSSPATH'];
                    }
                    static::writeHeader($cssPath . trim($extConf['CSSFILE']));
                    static::$headerWritten = false;

                    if (count(static::$starttimeArray)) {
                        $headerPostFix = static::$starttimeArray[1];
                        $headerValue = static::$starttimeArray[0];
                        static::$starttimeArray = [];
                    }

                    $appendText = ' - counter: ' . static::$processCount . ' ' . $headerPostFix;
                    switch (static::$currentTypo3Mode) {
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
                            $size = round(($fileInformation['size'] / (1024 * 1024)), 3);
                            $maxSize = self::getMaxFileSize();
                            if (
                                $size > $maxSize &&
                                $maxSize > 0
                            ) {
                                self::setMaxFileSizeReached(true);
                                static::writeOut(
                                    $size . ' MByte',
                                    static::$extensionKey . ': Maximum filesize reached for the debug output file.',
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
            // error_log('### debug ENDE $storeIsActive = ' . print_r($storeIsActive, true) . PHP_EOL, 3, static::getErrorLogFilename());

            if (!
                $isValidTrace ||
                !self::getMaxFileSizeReached()
            ) {
                static::setActive($storeIsActive);
            }
        }
        return true;
    }

    /**
    * Returns the internal debug messages as a string.
    *
    * @return string
    */
    public static function toString()
    {
        $errorLogFilename = '';
        $debugFilename = static::getDebugFilename();

        if (static::getUseErrorLog()) {
            $errorLogFilename = static::getErrorLogFilename();
            $result = static::$extensionKey . ': Debug messages have been written to the files "' . $debugFilename . '" and "' . $errorLogFilename . '"';
        } else {
            $result = static::$extensionKey . ': Debug messages have been written to the file "' . $debugFilename . '"';
        }

        return $result;
    }

    public static function close(): void
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

    public function __destruct()
    {
        static::close();
    }
}
