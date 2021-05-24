<?php

return [
    'ctrl'      => [
        'title'         => 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang_backend.xlf:tx_bpnchat_domain_model_message',
        'label'         => 'sender',
        'label_userFunc' => \BPN\BpnChat\Backend\UserFunctions\MessageTitle::class . '->displayTitle',
        'tstamp'        => 'tstamp',
        'crdate'        => 'crdate',
        'delete'        => 'deleted',
        'default_sortby' => 'ORDER BY crdate desc, sender, ',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile'      => 'EXT:bpn_chat/ext_icon.png'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, title'
    ],
    'columns'   => [
        'hidden'    => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'  => [
                'type' => 'check'
            ]
        ],
        'sender'    => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang_backend.xlf:tx_bpnchat_domain_model_message.sender',
            'config'  => [
                'type'          => 'group',
                'internal_type' => 'db',
                'allowed'       => 'fe_users',
                'foreign_table' => 'fe_users',
                'size'          => 1,
                'minitems'      => 0,
                'maxitems'      => 1,
            ],
        ],
        'receivers'  => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang_backend.xlf:tx_bpnchat_domain_model_message.receiver',
            'config'  => [
                'type'          => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'internal_type' => 'db',
                'allowed'       => 'fe_users',
                'foreign_table' => 'fe_users',
                'size'          => 10,
                'minitems'      => 0
            ],
        ],
        'message'   => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang_backend.xlf:tx_bpnchat_domain_model_message.message',
            'config'  => [
                'type'           => 'text',
                'rows'           => 10,
                'cols'           => 80,
                'eval'           => 'trim',
                'enableRichtext' => 1
            ],
        ],
        'delivered' => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:bpn_chat/Resources/Private/Language/locallang_backend.xlf:tx_bpnchat_domain_model_message.delivered',
            'config'  => [
                'readOnly' => 1,
                'type'     => 'text',
                'eval'     => 'datetime'
            ]
        ],
        'crdate' => [
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
    ],
    'types'     => [
        '1' => ['showitem' => 'hidden,sender,receivers,message,delivered']
    ],
    'palettes'  => [
        '1' => ['showitem' => '']
    ]
];
