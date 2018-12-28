<?php

namespace Cobweb\SvconnectorJson\Unit\Tests;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\Svconnector\Domain\Repository\ConnectorRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the JSON Connector service.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnector_json
 */
class ConnectorJsonTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/svconnector',
            'typo3conf/ext/svconnector_json',
    ];

    /**
     * @var \Cobweb\SvconnectorJson\Service\ConnectorJson
     */
    protected $subject;

    /**
     * Sets up the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        try {
            /** @var ConnectorRepository $connectorRepository */
            $connectorRepository = GeneralUtility::makeInstance(ConnectorRepository::class);
            $this->subject = $connectorRepository->findServiceByKey('tx_svconnectorjson_sv1');
        } catch (\Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    /**
     * Provides references to JSON files to read and expected output.
     *
     * @return array
     */
    public function sourceDataProvider(): array
    {
        $data = [
                'UTF-8 data' => [
                        'parameters' => [
                                'uri' => 'EXT:svconnector_json/Tests/Functional/Fixtures/data_utf8.json'
                        ],
                        'result' => [
                                'items' => [
                                        [
                                                'name' => 'Porte interdùm lacîna c\'est euismod.'
                                        ]
                                ]
                        ]
                ],
                'ISO-8859-1 data' => [
                        'parameters' => [
                                'uri' => 'EXT:svconnector_json/Tests/Functional/Fixtures/data_latin1.json',
                                'encoding' => 'iso-8859-1'
                        ],
                        'result' => [
                                'items' => [
                                        [
                                                'name' => 'Porte interdùm lacîna c\'est euismod.'
                                        ]
                                ]
                        ]
                ]
        ];
        return $data;
    }

    /**
     * Reads test JSON files and checks the resulting content against an expected structure.
     *
     * @param array $parameters List of connector parameters
     * @param array $result Expected array structure
     * @test
     * @dataProvider sourceDataProvider
     * @throws \Exception
     */
    public function readingJsonFileIntoArray($parameters, $result)
    {
        $data = $this->subject->fetchArray($parameters);
        self::assertSame($result, $data);
    }

    /**
     * @test
     * @expectedException \Cobweb\Svconnector\Exception\SourceErrorException
     */
    public function readingUnknownFileThrowsException()
    {
        $this->subject->fetchArray(
                [
                        'filename' => 'foobar.xml'
                ]
        );
    }
}
