<?php

declare(strict_types=1);

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

use Cobweb\SvconnectorJson\Paginator\HydraPaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class HydraPaginatorTest extends UnitTestCase
{
    protected HydraPaginator $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new HydraPaginator();
    }

    /**
     * @test
     */
    public function getStartPageReturns1(): void
    {
        self::assertSame(
            1,
            $this->subject->getStartPage()
        );
    }

    /**
     * @test
     */
    public function getPagingParameterReturnsPage(): void
    {
        self::assertSame(
            'page',
            $this->subject->getPagingParameter()
        );
    }

    public static function nextPageProvider(): array
    {
        return [
            'no next page' => [
                'data' => [
                    'hydra:view' => [
                        'hydra:first' => 'https://foo.com/bar?page=1',
                        'hydra:last' => 'https://foo.com/bar?page=1',
                    ],
                ],
                'expected' => 1,
            ],
            'no query parameters for next page' => [
                'data' => [
                    'hydra:view' => [
                        'hydra:first' => 'https://foo.com/bar/1',
                        'hydra:next' => 'https://foo.com/bar/2',
                        'hydra:last' => 'https://foo.com/bar/5',
                    ],
                ],
                'expected' => 1,
            ],
            'no page parameter among query parameters' => [
                'data' => [
                    'hydra:view' => [
                        'hydra:first' => 'https://foo.com/bar?hey=1',
                        'hydra:next' => 'https://foo.com/bar?hey=2',
                        'hydra:last' => 'https://foo.com/bar?hey=5',
                    ],
                ],
                'expected' => 1,
            ],
            'valid next page' => [
                'data' => [
                    'hydra:view' => [
                        'hydra:first' => 'https://foo.com/bar?page=1',
                        'hydra:next' => 'https://foo.com/bar?page=2',
                        'hydra:last' => 'https://foo.com/bar?page=5',
                    ],
                ],
                'expected' => 2,
            ],
        ];
    }

    #[Test] #[DataProvider('nextPageProvider')]
    public function getNextPageReturnsPageNumber(array $data, int $expected): void
    {
        $this->subject->setData($data);
        self::assertSame(
            $expected,
            $this->subject->getNextPage()
        );
    }

    public static function membersProvider(): array
    {
        $hydraView = [
            'hydra:first' => 'https://foo.com/bar?page=1',
            'hydra:next' => 'https://foo.com/bar?page=2',
            'hydra:last' => 'https://foo.com/bar?page=5',
        ];
        $hydraSearch = [
            'hydra:template' => 'https://foo.com/bar{?}',
            'hydra:mapping' => [],
        ];
        return [
            'single page' => [
                'incomingData' => [
                    0 => [
                        'hydra:member' => [
                            [
                                'title' => 'Eyes wide shut',
                            ],
                        ],
                        'hydra:view' => $hydraView,
                        'hydra:totalItems' => 1,
                        'hydra:search' => $hydraSearch,
                    ],
                ],
                'aggregatedData' => [
                    'hydra:member' => [
                        [
                            'title' => 'Eyes wide shut',
                        ],
                    ],
                    'hydra:view' => $hydraView,
                    'hydra:totalItems' => 1,
                    'hydra:search' => $hydraSearch,
                ],
            ],
            'two pages' => [
                'incomingData' => [
                    0 => [
                        'hydra:member' => [
                            [
                                'title' => 'Eyes wide shut',
                            ],
                        ],
                        'hydra:view' => $hydraView,
                        'hydra:totalItems' => 2,
                        'hydra:search' => $hydraSearch,
                    ],
                    1 => [
                        'hydra:member' => [
                            [
                                'title' => 'A clockwork orange',
                            ],
                        ],
                        'hydra:view' => $hydraView,
                        'hydra:totalItems' => 2,
                        'hydra:search' => $hydraSearch,
                    ],
                ],
                'aggregatedData' => [
                    'hydra:member' => [
                        [
                            'title' => 'Eyes wide shut',
                        ],
                        [
                            'title' => 'A clockwork orange',
                        ],
                    ],
                    'hydra:view' => $hydraView,
                    'hydra:totalItems' => 2,
                    'hydra:search' => $hydraSearch,
                ],
            ],
        ];
    }

    #[Test] #[DataProvider('membersProvider')]
    public function aggregateReturnsCompiledMembers(array $incomingData, array $aggregatedData): void
    {
        self::assertSame(
            $aggregatedData,
            $this->subject->aggregate($incomingData)
        );
    }
}
