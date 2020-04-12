<?php
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

use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\Svconnector\Service\ConnectorBase;
use Cobweb\Svconnector\Utility\FileUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service that reads JSON data for the "svconnector_json" extension.
 *
 * @author Francois Suter (Idéative) <typo3@ideative.ch>
 * @package TYPO3
 * @subpackage tx_svconnectorjson
 */
class ConnectorJson extends ConnectorBase
{
    public $prefixId = 'tx_svconnectorjson_sv1';        // Same as class name
    public $extensionKey = 'svconnector_json';    // The extension key.

    /**
     * Verifies that the connection is functional
     * In the case of this service, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return boolean TRUE if the service is available
     */
    public function init(): bool
    {
        parent::init();
        return true;
    }

    /**
     * Checks the connector configuration and returns notices, warnings or errors, if any.
     *
     * @param array $parameters Connector call parameters
     * @return array
     */
    public function checkConfiguration($parameters): array
    {
        $result = parent::checkConfiguration($parameters);
        // The "uri" parameter is mandatory
        if (empty($parameters['uri'])) {
            $result[AbstractMessage::ERROR][] = $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:no_json_defined');
        }
        // Issue error on deprecated parameters
        if (isset($parameters['useragent'])) {
            $result[AbstractMessage::ERROR][] = $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:user_agent_removed');
        }
        if (isset($parameters['accept'])) {
            $result[AbstractMessage::ERROR][] = $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:accept_removed');
        }
        // The "headers" parameter is expected to be an array
        if (isset($parameters['headers']) && !is_array($parameters['headers'])) {
            $result[AbstractMessage::WARNING][] = $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:headers_must_be_array');
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
    public function fetchRaw($parameters)
    {
        $result = $this->query($parameters);
        $this->logger->info(
                'RAW JSON data',
                [$result]
        );
        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $result = $processor->processRaw($result, $this);
            }
        }

        return $result;
    }

    /**
     * This method calls the query and returns the results from the response as an XML structure
     *
     * @param array $parameters Parameters for the call
     * @return string XML structure
     * @throws \Exception
     */
    public function fetchXML($parameters): string
    {

        $xml = $this->fetchArray($parameters);
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . "\n" . GeneralUtility::array2xml($xml);

        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $xml = $processor->processXML($xml, $this);
            }
        }

        return $xml;
    }

    /**
     * This method calls the query and returns the results from the response as a PHP array
     *
     * @param array $parameters Parameters for the call
     * @return array PHP array
     * @throws \Exception
     */
    public function fetchArray($parameters): array
    {
        // Get the data from the file
        $result = $this->query($parameters);
        $result = json_decode($result, true);
        $this->logger->info(
                'Structured data',
                $result
        );

        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $result = $processor->processArray($result, $this);
            }
        }
        return $result;
    }

    /**
     * Reads the content of the JSON DATA defined in the parameters and returns it as an array.
     *
     * NOTE: This method does not implement the "processParameters" hook, as it does not make sense in this case.
     *
     * @param array $parameters Parameters for the call
     * @return mixed Content of the json
     * @throws \Exception
     */
    protected function query($parameters)
    {
        // Check the configuration
        $problems = $this->checkConfiguration($parameters);
        // Log all issues and raise error if any
        $this->logConfigurationCheck($problems);
        if (count($problems[AbstractMessage::ERROR]) > 0) {
            $message = '';
            foreach ($problems[AbstractMessage::ERROR] as $problem) {
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
        if (isset($parameters['headers']) && is_array($parameters['headers']) && count($parameters['headers']) > 0) {
            foreach ($parameters['headers'] as $key => $header) {
                $headers[] = $key . ': ' . $header;
            }
        }

        $this->logger->info(
                'Call parameters and headers',
                ['params' => $parameters, 'headers' => $headers]
        );

        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $data = $fileUtility->getFileContent($parameters['uri'], $headers);
        if ($data === false) {
            $message = sprintf(
                    $this->sL('LLL:EXT:svconnector_json/Resources/Private/Language/locallang.xlf:json_not_fetched'),
                    $parameters['uri'],
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
        if (empty($parameters['encoding'])) {
            $encoding = '';
            $isSameCharset = true;
        } else {
            // Standardize charset name and compare
            $encoding = $parameters['encoding'];
            $isSameCharset = $this->getCharset() === $encoding;
        }
        // If the charset is not the same, convert data
        if (!$isSameCharset) {
            $data = $this->getCharsetConverter()->conv($data, $encoding, $this->getCharset());
        }

        // Process the result if any hook is registered
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $data = $processor->processResponse($data, $this);
            }
        }

        // Return the result
        return $data;
    }
}
