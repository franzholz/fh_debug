# TYPO3 extension fh_debug

## What is does

Use this extension to generate debug output files for the PHP code of TYPO3 and TYPO3 extensions in the Front End or Back End if they have PHP debug statements.
Consider to also install **debug_mysql_db** if you want to debug the generated SQL queries or track down the PHP errors in the table **sys_log** or the Developer traces written to the TYPO3 function **devLog**.

The debug output is written into a HTML debug output file. All the configuration is done in the Extension Manager for fh_debug. You can design the output by the CSS file fhdebug.css.
If you have a lot of debug output then you should put debug('B') (formerly debugBegin()) and debug('E') (formerly debugEnd) PHP commands around the PHP debug commands in order to have fewer debug output lines in the file. These commands will activate and deactivate the debug output.

### force output

Since version 0.8.3:
If a debug('B') is required, but maybe not active, then you can use the third parameter (group) 'F' to force an output. This is a spacial case to produce the output no matter if debugBegin is set to be mandatory or not.

### example:

```
debug('B');
$a = 'myString';
debug ($a, '$a at position 1');
debug('E');
```

No debug output will be shown on the screen. Otherwise you must deactivate the debug output in the Install Tool. 

```
$a = 'myString2';
debug ($a, '$a at position 1', 'F');
```

The debug output will always be shown.


## Configuration
Just enter any invalid IP address:

> [SYS][devIPmask] = 1.1.1.1

The Extension Manager configuration of fh_debug will be added to the IP address of the Install Tool.
IPADDRESS = 34.22.11.12
Your current IP address is shown in the Extension Manager view of fh_debug below the field IPADDRESS. If your provides has an ip version 6 activated, then you must enter it in the IPv6 format.

### example:

> Enter your current IP address 11.12.13.14, if you want to debug this client's actions.


### LocalConfiguration.php:

Use the fh_debug error handler in order to get debug messages of all exceptions.
Add these lines into your file `LocalConfiguration.php` under typo3conf.

```
'SYS' => array(
   'displayErrors' => '2',
    'errorHandler' => 'JambageCom\\FhDebug\\Hooks\\ErrorHandler',
),
```

You can show more debug info and a backtrace with the TYPO3 error message "Oops, an error occurred!". This is activated by default:
OOPS_AN_ERROR_OCCURRED = 1
This will also add a detailed debug output to the debug file.


To get the debug output for "Oops, an error occurred!" you must make this configuration in the Install Tool or `LocalConfiguration.php`:

[SYS][productionExceptionHandler] = JambageCom\FhDebug\Hooks\CoreProductionExceptionHandler

Remove this settings before you deinstall fh_debug. Otherwise you will get this PHP error entry:

PHP Fatal error:  Uncaught Error: Class 'JambageCom\FhDebug\Hooks\CoreProductionExceptionHandler' not found in /var/www/html/typo3_src-9.5.8/typo3/sysext/core/Classes/Utility/GeneralUtility.php:3667

The default setting is:
[SYS][productionExceptionHandler] = TYPO3\CMS\Core\Error\ProductionExceptionHandler

### example:
```
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fh_debug')) {
    require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Utility/DebugFunctions.php');  // use t3lib_extMgm::extPath in TYPO3 4.5
    // some configuration:
    \JambageCom\Fhdebug\Utility\DebugFunctions::setErrorLogFile(''); // this is necessary if you use the error_log file
    // if you use the debug HTML file:
    \JambageCom\Fhdebug\Utility\DebugFunctions::setDebugFile('fileadmin/debug.html');
    
    \JambageCom\Fhdebug\Utility\DebugFunctions::setDebugBegin(FALSE);       
    \JambageCom\Fhdebug\Utility\DebugFunctions::setRecursiveDepth('12'); 
    \JambageCom\Fhdebug\Utility\DebugFunctions::setTraceDepth('12'); 
    \JambageCom\Fhdebug\Utility\DebugFunctions::setAppendDepth('0'); 
    \JambageCom\Fhdebug\Utility\DebugFunctions::setTypo3Mode('ALL'); 
    \JambageCom\Fhdebug\Utility\DebugFunctions::setActive(TRUE); 
    \JambageCom\Fhdebug\Utility\DebugFunctions::initFile();
}

\JambageCom\Fhdebug\Utility\DebugFunctions::debug($_EXTCONF, '$_EXTCONF');
```

If you use the file **ext_localconf.php** or some of the at first executed TYPO3 core files, then the extension fh_debug has not been initialized yet. Therefore you must use the full namespace class to initialize and to call the class of fh_debug.



Class 'JambageCom\Fhdebug\Utility\DebugFunctions' not found
in /var/www/html/typo3_src/typo3/sysext/core/Resources/PHP/GlobalDebugFunctions.php line 15

This means that your debug output shall be generated before the extension fh_debug has been initialized by TYPO3.
You must do your own initialization by these commands:


### example:
```
define('FH_DEBUG_EXT', 'fh_debug');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fh_debug') . 'Classes/Utility/DebugFunctions.php');
\JambageCom\Fhdebug\Utility\DebugFunctions::init();
\JambageCom\Fhdebug\Utility\DebugFunctions::setErrorLogFile('');
\JambageCom\Fhdebug\Utility\DebugFunctions::setDebugFile('fileadmin/debug.html');

debug ($tmp, 'variable before fh_debug has been started yet.');
```



## debug begin and end

There are 2 control commands available to begin and to end the generation of debug output:
debug('B') and debug('E'), formerly (before TYPO3 9.5) debugBegin and debugEnd .


### TYPO3 9.5, 10.4, 11.5:
Since TYPO3 9 you must overwrite the TYPO3 Core file sysext/core/Resources/PHP/GlobalDebugFunctions.php by the file fh_debug/Patches/TYPO3/sysext/core/Resources/PHP/GlobalDebugFunctions.php. They will provide the former debugBegin and debugEnd method names and allow fh_debug to work at all. And it contains the necessary PHP code to call fh_debug if it is activated in the Extension Manager.

Replacement for debugBegin and debugEnd:
Since fh_debug 0.8.0 a workaround has been introduced because since TYPO3 9 needed global functions have been removed.

### example:
```
debug('B'); // begin debugging
debug($myVariable, 'my variable');
debug('E'); // end debugging
```

### example before version 0.8.0:
```
debugBegin();
debug($myVariable, 'my variabled');
debugEnd();
```

## Error

If fh_debug does not work, then there is probably the case where fh_debug has not been activated yet.
You can use PHP error logging as an alternativ.


### example PHP error_log :
```
error_log('mymethod Position 2 $variableName: ' .  print_r($variableName, true) . PHP_EOL, 3, '/var/www/html/fileadmin/phpDebugErrorLog.txt');
```

Use you own path as the last parameter of the above method error_log

## Trouble shooting

If you do not get anything shown in the browser url https://example.com/fileadmin/debug.html, then make sure that this file debug.html really exists on the file system. If not, then create an empty file debug.html in the folder fileadmin and give Apache write access to it.

Check the configuration in the extension manager.
 IP addresses of the client browser 
Put in an asterisk * . Then every client IP address will produce a debug output.

## Improvements

Please make an entry directly on the TYPO3 Core bug tracker at <br>
[add a control function for debugging](https://forge.typo3.org/issues/23899) <br>
[enhanced debug methods](https://forge.typo3.org/issues/86220)

Global functions can only be implemented in the TYPO3 core.

## ToDO

Use cweagans/composer-patches .



