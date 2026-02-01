<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "svconnector_json".
 *
 * Auto generated 05-04-2017 18:01
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Connector service - JSON',
    'description' => 'Connector service for JSON data',
    'category' => 'services',
    'version' => '5.0.0',
    'state' => 'stable',
    'clearcacheonload' => 0,
    'author' => 'Francois Suter (Idéative)',
    'author_email' => 'typo3@ideative.ch',
    'author_company' => '',
    'constraints' =>
        [
            'depends' =>
                [
                    'php' => '8.2.0-8.5.99',
                    'typo3' => '13.4.0-14.1.99',
                    'svconnector' => '6.0.0-0.0.0',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];

