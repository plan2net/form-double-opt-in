<?php

declare(strict_types=1);

$ll = 'LLL:EXT:form_double_opt_in/Resources/Private/Language/locallang.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin',
        'label' => 'email',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'email,confirmation_hash',
        'iconfile' => 'EXT:form_double_opt_in/Resources/Public/Icons/PluginDoubleOptIn.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => 'email, mailing_date, confirmation_hash, confirmation_date, confirmed'
        ]
    ],
    'columns' => [
        'email' => [
            'exclude' => 1,
            'label' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin.email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'readOnly' => true
            ]
        ],
        'mailing_date' => [
            'exclude' => 1,
            'label' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin.mailing_date',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'datetime',
                'checkbox' => false,
                'readOnly' => true,
                'renderType' => 'inputDateTime'
            ]
        ],
        'confirmed' => [
            'exclude' => 1,
            'label' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin.confirmed',
            'config' => [
                'type' => 'check',
                'readOnly' => 1
            ]
        ],
        'confirmation_hash' => [
            'exclude' => 1,
            'label' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin.confirmation_hash',
            'config' => [
                'type' => 'input',
                'size' => 16,
                'readOnly' => 1
            ]
        ],
        'confirmation_date' => [
            'exclude' => 1,
            'label' => $ll . 'tx_formdoubleoptin_domain_model_formdoubleoptin.confirmation_date',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'datetime',
                'checkbox' => false,
                'readOnly' => true,
                'renderType' => 'inputDateTime'
            ]
        ],
        'form_values' => [
            'exclude' => 1,
            'label' => '',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'receiver_information' => [
            'exclude' => 1,
            'label' => '',
            'config' => [
                'type' => 'passthrough'
            ]
        ]
    ]
];
