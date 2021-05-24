<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

call_user_func(
    function () {
        // registration part
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'BpnChat',
            'Chat',
            [
                \BPN\BpnChat\Controller\ChatController::class => implode(
                        ',',
                        [
                            'index,addMessage,chat',
                        ]
                    ),
            ],
            [
                \BPN\BpnChat\Controller\ChatController::class => implode(
                        ',',
                        [
                            'index,addMessage,chat',
                        ]
                    ),
            ]
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bpn_chat/Configuration/TypoScript/constants.typoscript">'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bpn_chat/Configuration/TypoScript/setup.typoscript">'
        );
    }
);
