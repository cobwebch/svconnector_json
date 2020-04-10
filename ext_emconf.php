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
        'version' => '2.3.0',
        'state' => 'stable',
        'uploadfolder' => 0,
        'createDirs' => '',
        'clearcacheonload' => 0,
        'author' => 'Prakash A Bhat, Francois Suter (Cobweb)',
        'author_email' => 'typo3@cobweb.ch',
        'author_company' => '',
        'constraints' =>
                [
                        'depends' =>
                                [
                                        'typo3' => '8.7.0-10.4.99',
                                        'svconnector' => '3.4.0-0.0.0',
                                ],
                        'conflicts' =>
                                [
                                ],
                        'suggests' =>
                                [
                                ],
                ],
];

