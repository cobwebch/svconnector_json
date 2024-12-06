<?php

declare(strict_types=1);

namespace Cobweb\SvconnectorJson\Service;

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

use Cobweb\Svconnector\Attribute\AsConnectorService;
use Cobweb\Svconnector\Event\ProcessArrayDataEvent;
use Cobweb\Svconnector\Event\ProcessRawDataEvent;
use Cobweb\Svconnector\Event\ProcessResponseEvent;
use Cobweb\Svconnector\Event\ProcessXmlDataEvent;
use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\Svconnector\Service\ConnectorBase;
use Cobweb\Svconnector\Utility\FileUtility;
use Cobweb\SvconnectorJson\Paginator\AbstractPaginator;
use Cobweb\SvconnectorJson\Paginator\HydraPaginator;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service that reads JSON data for the "svconnector_json" extension.
 */
#[AsConnectorService(type: 'json', name: 'JSON connector')]
class ConnectorJson extends ConnectorBase
{
    protected string $extensionKey = 'svconnector_json';

    /**
     * Verifies that the connection is functional
     * In the case of this service, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return bool TRUE if the service is available
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Checks the connector configuration and returns notices, warnings or errors, if any.
     *
     * @param array $parameters Connector call parameters
     * @return array
     */
    public function checkConfiguration(array $parameters = []): array
    {
        $result = parent::checkConfiguration(...func_get_args());
        // The "uri" parameter is mandatory
        if (empty($this->parameters['uri'])) {
            $result[ContextualFeedbackSeverity::ERROR->value][] = $this->sL(
                'LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:no_json_defined'
            );
        }
        // The "headers" parameter is expected to be an array
        if (isset($this->parameters['headers']) && !is_array($this->parameters['headers'])) {
            $result[ContextualFeedbackSeverity::WARNING->value][] = $this->sL(
                'LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:headers_must_be_array'
            );
        }
        // The "queryParameters" parameter is expected to be an array
        if (isset($this->parameters['queryParameters']) && !is_array($this->parameters['queryParameters'])) {
            $result[ContextualFeedbackSeverity::WARNING->value][] = $this->sL(
                'LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:query_parameters_must_be_array'
            );
        }
        return $result;
    }

    /**
     * This method calls the query method and returns the result as is,
     * i.e. the json data, but without any additional work performed on it
     *
     * @param array $parameters Parameters for the call
     * @return mixed Server response
     * @throws \Exception
     */
    public function fetchRaw(array $parameters = [])
    {
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchRaw(...func_get_args());

        $result = $this->query();
        $this->logger->info(
            'RAW JSON data',
            [$result]
        );
        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processRaw hook is deprecated. Use the ProcessRawDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $result = $processor->processRaw($result, $this);
            }
        }
        /** @var ProcessRawDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessRawDataEvent($result, $this)
        );
        return $event->getData();
    }

    /**
     * This method calls the query and returns the results from the response as an XML structure
     *
     * @param array $parameters Parameters for the call
     * @return string XML structure
     * @throws \Exception
     */
    public function fetchXML(array $parameters = []): string
    {
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchXML(...func_get_args());

        $xml = $this->fetchArray();
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . "\n" . GeneralUtility::array2xml($xml);

        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processXML hook is deprecated. Use the ProcessXmlDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $xml = $processor->processXML($xml, $this);
            }
        }
        /** @var ProcessXmlDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessXmlDataEvent($xml, $this)
        );

        return $event->getData();
    }

    /**
     * Fetch the JSON data and return it as an array
     *
     * @param array $parameters Parameters for the call
     * @return array PHP array
     * @throws \Exception
     */
    public function fetchArray(array $parameters = []): array
    {
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchArray(...func_get_args());

        // Get the data from the source
        $result = $this->query();
        $result = json_decode((string)$result, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($result)) {
            throw new \InvalidArgumentException(
                'JSON structure could not be decoded',
                1671383061
            );
        }

        $paginator = $this->getPaginator();
        if ($paginator === null) {
            $data = $result;
        } else {
            $currentPage = $paginator->getStartPage();
            $pagingParameter = $paginator->getPagingParameter();
            $hasNextPage = true;
            // Assemble a list of all results, including the first one
            $allResults = [$result];
            do {
                $paginator->setData($result);
                $nextPage = $paginator->getNextPage();
                if ($nextPage > $currentPage) {
                    $mergedQueyParameters = array_merge(
                        $this->parameters['queryParameters'] ?? [],
                        [
                            $pagingParameter => $nextPage,
                        ]
                    );
                    $currentParameters = $this->parameters;
                    $currentParameters['queryParameters'] = $mergedQueyParameters;
                    $result = $this->query($currentParameters);
                    $result = json_decode((string)$result, true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($result)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'JSON structure could not be decoded, page %d',
                                $nextPage
                            ),
                            1709283781
                        );
                    }
                    $allResults[] = $result;
                    $currentPage = $nextPage;
                } else {
                    $hasNextPage = false;
                }
            } while ($hasNextPage);
            // Aggregate the results, if the query was paginated
            $data = $paginator->aggregate($allResults);
        }

        // Log the data
        $this->logger->info(
            'Structured data',
            $data
        );

        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processArray hook is deprecated. Use the ProcessArrayDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $data = $processor->processArray($data, $this);
            }
        }
        /** @var ProcessArrayDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessArrayDataEvent($data, $this)
        );
        return $event->getData();
    }

    /**
     * Return the JSON data (as a string) fetched with the given parameters
     *
     * @param array $parameters Parameters for the call
     * @return mixed JSON content (string)
     * @throws \Exception
     */
    protected function query(array $parameters = [])
    {
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::query(...func_get_args());

        // Check the configuration
        $problems = $this->checkConfiguration();
        // Log all issues and raise error if any
        $this->logConfigurationCheck($problems);
        if (count($problems[ContextualFeedbackSeverity::ERROR->value]) > 0) {
            $message = '';
            foreach ($problems[ContextualFeedbackSeverity::ERROR->value] as $problem) {
                if ($message !== '') {
                    $message .= "\n";
                }
                $message .= $problem;
            }
            $this->raiseError(
                $message,
                1299257883,
                [],
                SourceErrorException::class
            );
        }

        // Define the headers
        $headers = null;
        if (is_array($this->parameters['headers'] ?? null) && count($this->parameters['headers']) > 0) {
            $headers = $this->parameters['headers'];
        }

        $this->logger->info(
            'Call parameters',
            $this->parameters
        );

        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $uri = $this->parameters['uri'];
        if (isset($this->parameters['queryParameters'])) {
            $uri = sprintf('%s?%s', $uri, http_build_query($this->parameters['queryParameters']));
        }
        $data = $fileUtility->getFileContent(
            $uri,
            $headers,
            $this->parameters['method'] ?? 'GET'
        );
        if ($data === false) {
            $message = sprintf(
                $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:json_not_fetched'),
                $uri,
                $fileUtility->getError()
            );
            $this->raiseError(
                $message,
                1299257894,
                [],
                SourceErrorException::class
            );
        }
        // Check if the current charset is the same as the file encoding
        // Don't do the check if no encoding was defined
        // TODO: add automatic encoding detection by reading the encoding attribute in the JSON header
        if (empty($this->parameters['encoding'])) {
            $encoding = '';
            $isSameCharset = true;
        } else {
            // Standardize charset name and compare
            $encoding = $this->parameters['encoding'];
            $isSameCharset = $this->getCharset() === $encoding;
        }
        // If the charset is not the same, convert data
        if (!$isSameCharset) {
            $data = $this->getCharsetConverter()->conv($data, $encoding, $this->getCharset());
        }

        // Process the result if any hook is registered
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processResponse hook is deprecated. Use the ProcessResponseEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $data = $processor->processResponse($data, $this);
            }
        }
        /** @var ProcessResponseEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessResponseEvent($data, $this)
        );

        // Return the result
        return $event->getResponse();
    }

    /**
     * Return a paginator object, if defined
     */
    protected function getPaginator(): ?AbstractPaginator
    {
        $paginatorSetting = $this->parameters['paginator'] ?? '';
        if ($paginatorSetting === '') {
            return null;
        }
        // Consider predefined paginators
        if ($paginatorSetting === 'hydra') {
            $paginatorClass = HydraPaginator::class;
        } else {
            $paginatorClass = $paginatorSetting;
        }
        $paginator = GeneralUtility::makeInstance($paginatorClass);
        if (!$paginator instanceof AbstractPaginator) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class %s does not extend class %s',
                    $paginator,
                    AbstractPaginator::class
                ),
                1709280188
            );
        }
        return $paginator;
    }
}
