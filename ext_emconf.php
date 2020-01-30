<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form double opt-in',
    'description' => 'Adds a double opt-in finisher to forms',
    'category' => 'fe',
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wk@plan2.net',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'author_company' => 'plan2net GmbH',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'form' => '9.5.9-9.5.99'
        ]
    ]
];
