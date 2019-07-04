Use this extension to generate debug output files for the PHP code of TYPO3 and TYPO3 extensions in the Front End or Back End if they have PHP debug statements.
Consider to also install **debug_mysql_db** if you want to debug the generated SQL queries or track down the PHP errors in the table **sys_log** or the Developer traces written to the TYPO3 function **devLog**.

Under TYPO3 9 you must overwrite the TYPO3 Core file sysext/core/Resources/PHP/GlobalDebugFunctions.php by the file TYPO3-9.5/sysext/core/Resources/PHP/GlobalDebugFunctions.php under the Patches folder. 

The debug output is written into a HTML debug output file. All the configuration is done in the Extension Manager for fh_debug. You can design the output by the CSS file fhdebug.css.
If you have a lot of debug output then you should put debugBegin and debugEnd commands around the PHP debug commands in order to have fewer debug output in the file. This will activate and deactivate the debug output.

###### example:

```
debugBegin();
$a = 'myString';
debug ($a, '$a at position 1');
debugEnd();
```

No debug output will be shown on the screen. Otherwise you must deactivate the debug output in the Install Tool. Just enter any invalid IP address:

> [SYS][devIPmask] = 1.1.1.1

The Extension Manager configuration of fh_debug will be added to the IP address of the Install Tool.
IPADDRESS = 34.22.11.12
Your current IP address is shown in the Extension Manager view of fh_debug below the field IPADDRESS.

###### example:

> Enter your current IP address 11.12.13.14, if you want to debug this client's actions.

You can show more debug info and a backtrace with the TYPO3 error message "Oops, an error occurred!". This is activated by default:
OOPS_AN_ERROR_OCCURRED = 1
This will also add a detailed debug output to the debug file.


To get the debug output for "Oops, an error occured" you must make this configuration in the Install Tool:

[SYS][productionExceptionHandler] = JambageCom\FhDebug\Hooks\CoreProductionExceptionHandler


