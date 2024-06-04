<?php

$EM_CONF['form_double_opt_in'] = [
    'title' => 'Form double opt-in',
    'description' => 'Adds a double opt-in finisher to forms',
    'category' => 'fe',
    'author' => 'plan2net GmbH, Team Wonderland',
    'author_email' => 'wonderland@plan2.net',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'author_company' => 'plan2net GmbH',
    'version' => '1.2.1',
    'constraints' => [
        'depends' => [
            'form' => '9.5.9-12.4.99'
        ]
    ]
];
