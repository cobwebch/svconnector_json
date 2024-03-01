<?php

declare(strict_types=1);

namespace Cobweb\SvconnectorJson\Paginator;

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

/**
 * Paginate through a Hydra JSON result set
 *
 * See: http://www.hydra-cg.com/spec/latest/core/#example-20-a-hydra-partialcollectionview-splits-a-collection-into-multiple-views
 */
final class HydraPaginator extends AbstractPaginator
{
    protected const HYDRA_VIEW = 'hydra:view';
    protected const HYDRA_NEXT = 'hydra:next';
    protected const HYDRA_MEMBER = 'hydra:member';

    protected $message = '';
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getNextPage(): int
    {
        $nextPage = $this->data[self::HYDRA_VIEW][self::HYDRA_NEXT] ?? '';
        $this->message = 'foo';//serialize($this->data);
        // If there's no next page, return 1 (first page)
        if ($nextPage === '') {
            return $this->startPage;
        }
        $queryParts = parse_url($nextPage, PHP_URL_QUERY);
        // If the URL is somehow malformed, return 1
        if (!$queryParts) {
            return $this->startPage;
        }
        // Return the page number found in the "page" variable
        parse_str($queryParts, $variables);
        return (int)($variables[$this->pagingParameter] ?? $this->startPage);
    }

    public function aggregate(array $finalData): array
    {
        // Nothing to aggregate if there was a single page of results, just return it
        if (count($finalData) === 1) {
            return $finalData[0];
        }

        // Aggregate all items in the "hydra:member" dimension
        $members = [];
        foreach ($finalData as $dataChunk) {
            $members = array_merge($members, $dataChunk[self::HYDRA_MEMBER] ?? []);
        }
        $aggregatedData = $finalData[0];
        $aggregatedData[self::HYDRA_MEMBER] = $members;
        return $aggregatedData;
    }
}