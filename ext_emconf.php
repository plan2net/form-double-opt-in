<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form double opt-in',
    'description' => 'Adds a double opt-in finisher to forms',
    'category' => 'fe',
    'author' => 'plan2net GmbH, Team Wonderland',
    'author_email' => 'wonderland@plan2.net',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'author_company' => 'plan2net GmbH',
    'version' => '1.2.0',
    'constraints' => [
        'depends' => [
            'form' => '9.5.9-9.5.99'
        ]
    ]
];
