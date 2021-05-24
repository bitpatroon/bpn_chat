<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

ExtensionUtility::registerPlugin('bpn_chat', 'chat', 'BPN Chat');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['bpnchat_chat'] = 'layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['bpnchat_chat'] = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'bpnchat_chat',
    'FILE:EXT:bpn_chat/Configuration/FlexForm/flexform.xml'
);
