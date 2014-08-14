<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'Subscribe',
	array(
		'Subscription' => 'component',
		
	),
	array(
		'Subscription' => 'component',
		
	)
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'Subscriptions',
	array(
		'Subscription' => 'list,delete',

	),
	array(
		'Subscription' => 'list,delete',

	)
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	$_EXTKEY,
	'Timeline',
	array(
		'Subscription' => 'timeline,reset',

	),
	array(
		'Subscription' => 'timeline,reset',

	)
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Tx_Notify_Command_NotificationCommandController';

?>