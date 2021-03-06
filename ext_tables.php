<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Subscribe',
	'Notify: Subscription component'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Subscriptions',
	'Notify: Manage subscriptions'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	'Timeline',
	'Notify: Timeline of updates'
);

$templatePaths = array(
	'templateRootPath' => 'EXT:notify/Resources/Private/Templates/',
	'partialRootPath' => 'EXT:notify/Resources/Private/Partials/',
	'layoutRootPath' => 'EXT:notify/Resources/Private/Layouts/',
);
$flexFormFile = 'EXT:notify/Configuration/FlexForm.xml';

$TCA['tt_content']['types']['list']['subtypes_addlist']['notify_subscribe'] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_addlist']['notify_subscriptions'] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_addlist']['notify_timeline'] = 'pi_flexform';
Tx_Flux_Core::registerFluidFlexFormPlugin('notify', 'notify_subscribe', $flexFormFile, array(), 'SubscribeComponentConfiguration', $templatePaths);
Tx_Flux_Core::registerFluidFlexFormPlugin('notify', 'notify_subscriptions', $flexFormFile, array(), 'SubscriptionsConfiguration', $templatePaths);
Tx_Flux_Core::registerFluidFlexFormPlugin('notify', 'notify_timeline', $flexFormFile, array(), 'TimelineConfiguration', $templatePaths);

unset($templatePaths, $flexFormFile);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Notification and subscription');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_notify_domain_model_subscription', 'EXT:notify/Resources/Private/Language/locallang_csh_tx_notify_domain_model_subscription.xml');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_notify_domain_model_subscription');
$TCA['tx_notify_domain_model_subscription'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:notify/Resources/Private/Language/locallang_db.xml:tx_notify_domain_model_subscription',
		'label' => 'subscriber',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'requestUpdate' => 'mode,source_table',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Subscription.php',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_notify_domain_model_subscription.gif'
	),
);


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Notify_Scheduler_NotificationSchedulerTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:notify/Resources/Private/Language/locallang_db.xml:task.notification.name',
	'description'      => 'LLL:EXT:notify/Resources/Private/Language/locallang_db.xml:task.notification.description',
	'additionalFields' => 'Tx_Notify_Scheduler_SharedFieldProvider'
);

?>