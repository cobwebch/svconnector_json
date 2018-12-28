<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
		$_EXTKEY,
        // Service type
        'connector',
        // Service key
        'tx_svconnectorjson_sv1',
		[
			'title' => 'JSON connector',
			'description' => 'Connector service to get JSON Data',

			'subtype' => 'json',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'className' => \Cobweb\SvconnectorJson\Service\ConnectorJson::class
        ]
);
