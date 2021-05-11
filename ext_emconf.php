<?php

$EM_CONF[$_EXTKEY] = [
    'title'          => 'BPN Chat',
    'description'    => 'Chat extension',
    'category'       => 'FE',
    'author'         => 'Sjoerd Zonneveld',
    'author_email'   => 'code@bitpatroon.nl',
    'state'          => 'stable',
    'author_company' => 'Bitpatroon',
    'version'        => '10.4',
    'constraints'    => [
        'depends' => [
            'typo3' => '10.4.0-10.9.9999',
        ],
    ],
];
