<?php

declare(strict_types=1);

namespace Cobweb\SvconnectorJson\Functional\Tests;

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

use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\SvconnectorJson\Service\ConnectorJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for the JSON Connector service.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 */
class ConnectorJsonTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/svconnector',
        'typo3conf/ext/svconnector_json',
    ];

    protected ConnectorJson $subject;

    /**
     * Sets up the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        try {
            $this->subject = GeneralUtility::makeInstance(ConnectorJson::class);
        } catch (\Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    /**
     * Provides references to JSON files to read and expected output.
     */
    public static function sourceDataProvider(): array
    {
        return [
            'UTF-8 data' => [
                'parameters' => [
                    'uri' => 'EXT:svconnector_json/Tests/Functional/Fixtures/data_utf8.json',
                ],
                'result' => [
                    'items' => [
                        [
                            'name' => 'Porte interdùm lacîna c\'est euismod.',
                        ],
                    ],
                ],
            ],
            'ISO-8859-1 data' => [
                'parameters' => [
                    'uri' => 'EXT:svconnector_json/Tests/Functional/Fixtures/data_latin1.json',
                    'encoding' => 'iso-8859-1',
                ],
                'result' => [
                    'items' => [
                        [
                            'name' => 'Porte interdùm lacîna c\'est euismod.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Reads test JSON files and checks the resulting content against an expected structure.
     *
     * @param array $parameters List of connector parameters
     * @param array $result Expected array structure
     * @throws \Exception
     */
    #[Test] #[DataProvider('sourceDataProvider')]
    public function readingJsonFileIntoArray(array $parameters, array $result): void
    {
        $this->subject->setParameters($parameters);
        $data = $this->subject->fetchArray();
        self::assertSame($result, $data);
    }

    /**
     * @test
     */
    #[Test]
    public function readingUnknownFileThrowsException(): void
    {
        $this->expectException(SourceErrorException::class);
        $this->subject->setParameters(
            [
                'filename' => 'foobar.xml',
            ]
        );
        $this->subject->fetchArray();
    }

    public static function wrongConfigurationProvider(): array
    {
        return [
            'Missing "uri" parameter' => [
                'parameters' => [
                    'encoding' => 'UTF-8',
                ],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @throws \Exception
     */
    #[Test] #[DataProvider('wrongConfigurationProvider')]
    public function wrongConfigurationThrowsException(array $parameters): void
    {
        $this->expectException(SourceErrorException::class);
        $this->subject->setParameters($parameters);
        $this->subject->fetchArray();
    }
}
