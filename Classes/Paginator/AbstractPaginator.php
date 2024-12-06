<?php

declare(strict_types=1);

namespace Cobweb\SvconnectorJson\Paginator;

/**
 * Abstract class to inherit for any class that calculates pagination
 * for a multipage JSON request
 */
abstract class AbstractPaginator
{
    protected array $data = [];
    protected int $startPage;
    protected string $pagingParameter;

    public function __construct()
    {
        // Start page value depends on the structure being paginated, but is typically 0 or 1
        // Override this constructor if start page is not 1 for your particular paginator
        $this->startPage = 1;
        // Also define the name of the query parameter used for paging
        // Override this constructor if it is not "page"
        $this->pagingParameter = 'page';
    }

    public function getStartPage(): int
    {
        return $this->startPage;
    }

    public function getPagingParameter(): string
    {
        return $this->pagingParameter;
    }

    public function setData(array $data): AbstractPaginator
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Return the next page number to query based on the given data
     */
    abstract public function getNextPage(): int;

    /**
     * Aggregate the data returned by each query to the data source
     *
     * @param array $finalData
     * @return array
     */
    abstract public function aggregate(array $finalData): array;
}
