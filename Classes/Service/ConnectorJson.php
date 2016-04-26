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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service that reads JSON data for the "svconnector_json" extension.
 *
 * @author Prakash A Bhat (Cobweb) <typo3@cobweb.ch>
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnectorjson
 */
class ConnectorJson extends ConnectorBase
{
    public $prefixId = 'tx_svconnectorjson_sv1';        // Same as class name
    public $scriptRelPath = 'sv1/class.tx_svconnectorjson_sv1.php';    // Path to this script relative to the extension dir.
    public $extKey = 'svconnector_json';    // The extension key.
    protected $extConf; // Extension configuration

    /**
     * Verifies that the connection is functional
     * In the case of this service, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return boolean TRUE if the service is available
     */
    public function init()
    {
        parent::init();
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
        return true;
    }

    /**
     * This method calls the query method and returns the result as is,
     * i.e. the json data, but without any additional work performed on it
     *
     * @param array $parameters Parameters for the call
     * @return mixed Server response
     */
    public function fetchRaw($parameters)
    {
        $result = $this->query($parameters);
        if (TYPO3_DLOG || $this->extConf['debug']) {
            GeneralUtility::devLog('RAW JSON data', $this->extKey, -1, array($result));
        }
        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
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
     */
    public function fetchXML($parameters)
    {

        $xml = $this->fetchArray($parameters);
        $xml = GeneralUtility::array2xml_cs($xml);

        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
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
     */
    public function fetchArray($parameters)
    {
        // Get the data from the file
        $result = $this->query($parameters);
        $result = json_decode($result, true);

        if (TYPO3_DLOG || $this->extConf['debug']) {
            GeneralUtility::devLog('Structured data', $this->extKey, -1, $result);
        }

        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $result = $processor->processArray($result, $this);
            }
        }
        return $result;
    }

    /**
     * This method reads the content of the JSON DATA defined in the parameters
     * and returns it as an array
     *
     * NOTE:    this method does not implement the "processParameters" hook,
     *          as it does not make sense in this case
     *
     * @param array $parameters Parameters for the call
     * @throws SourceErrorException
     * @return array Content of the json
     */
    protected function query($parameters)
    {

        // Check if the json's URI is defined
        if (empty($parameters['uri'])) {
            $message = $this->sL('LLL:EXT:' . $this->extKey . '/Resources/Private/Language/locallang.xlf:no_json_defined');
            if (TYPO3_DLOG || $this->extConf['debug']) {
                GeneralUtility::devLog($message, $this->extKey, 3);
            }
            throw new SourceErrorException(
                    $message,
                    1299257883
            );
        } else {
            $report = array();
            // Define the headers
            $headers = false;
            if (isset($parameters['useragent'])) {
                $headers = array('User-Agent: ' . $parameters['useragent']);
            }
            if (isset($parameters['accept'])) {
                if (is_array($headers)) {
                    $headers[] = 'Accept: ' . $parameters['accept'];
                } else {
                    $headers = array('Accept: ' . $parameters['accept']);
                }
            }

            if (TYPO3_DLOG || $this->extConf['debug']) {
                GeneralUtility::devLog('Call parameters and headers', $this->extKey, -1,
                        array('params' => $parameters, 'headers' => $headers));
            }

            $data = GeneralUtility::getUrl(
                    $parameters['uri'],
                    0,
                    $headers,
                    $report
            );
            if (!empty($report['message'])) {
                $message = sprintf(
                        $this->sL('LLL:EXT:' . $this->extKey . '/Resources/Private/Language/locallang.xlf:json_not_fetched'),
                        $parameters['uri'],
                        $report['message']
                );
                if (TYPO3_DLOG || $this->extConf['debug']) {
                    GeneralUtility::devLog($message, $this->extKey, 3, $report);
                }
                throw new SourceErrorException(
                        $message,
                        1299257894
                );
            }
            // Check if the current charset is the same as the file encoding
            // Don't do the check if no encoding was defined
            // TODO: add automatic encoding detection by the reading the encoding attribute in the JSON header
            if (empty($parameters['encoding'])) {
                $encoding = '';
                $isSameCharset = true;
            } else {
                // Standardize charset name and compare
                $encoding = $this->getCharsetConverter()->parse_charset($parameters['encoding']);
                $isSameCharset = $this->getCharset() == $encoding;
            }
            // If the charset is not the same, convert data
            if (!$isSameCharset) {
                $data = $this->getCharsetConverter()->conv($data, $encoding, $this->getCharset());
            }
        }

        // Process the result if any hook is registered
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $data = $processor->processResponse($data, $this);
            }
        }

        // Return the result
        return $data;
    }
}
