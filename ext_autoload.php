<?php

$extensionPath = t3lib_extMgm::extPath('fh_debug');
return array(
	'JambageCom\\FhDebug\\Utility\\DebugFunctions' => $extensionPath . 'Classes/Utility/DebugFunctions.php',
	'JambageCom\\FhDebug\\Hooks\\CoreHooks' => $extensionPath . 'Classes/Hooks/CoreHooks.php',
	'JambageCom\\FhDebug\\Hooks\\PatchemHooks' => $extensionPath . 'Classes/Hooks/PatchemHooks.php',
	'JambageCom\\FhDebug\\Listener\\ExtensionManagementListener' => $extensionPath . 'Classes/Listener/ExtensionManagementListener.php',
);
