.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Developer's manual
------------------

Reading JSON data using the JSON connector service is a simple
task. The first step is to get the proper service object:

.. code-block:: php

   $registry = GeneralUtility::makeInstance(\Cobweb\Svconnector\Registry\ConnectorRegistry::class);
   $connector = $registry->getServiceForType('json');

An additional step could be to check if the service is indeed available,
by calling :php:`$connector->isAvailable()`, although - in this particular
case - the JSON connector service is always available.

The next step is simply to call the appropriate method from the API –
with the right parameters – depending on which format you want to have
in return. For example::

   $parameters = [
            'uri' => 'http://forge.typo3.org/projects/extension-external_import/issues.json',
            'encoding' => 'utf-8',
   ];
   $data = $connector->fetchArray($parameters);


This will return a PHP array from the decoded JSON data. The
:code:`fetchRaw()` method will return the JSON data as a string.

The :code:`fetchXML()` method returns a XML version of the PHP array
transformed using :code:`\TYPO3\CMS\Core\Utility\GeneralUtility::array2xml`.

