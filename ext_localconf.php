<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

call_user_func(
    function () {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'BpnChat',
            'Chat',
            [\BPN\BpnChat\Controller\ChatController::class => 'index,addMessage,chat'],
            [\BPN\BpnChat\Controller\ChatController::class => 'index,addMessage,chat']
        );

        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_bpnchat'] = \BPN\BpnChat\Start::class.'::process';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bpn_chat/Configuration/TypoScript/constants.typoscript">'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bpn_chat/Configuration/TypoScript/setup.typoscript">'
        );
    }
);
