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

   $services = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::findService('connector', 'json');
   if ($services === FALSE) {
           // Issue an error
   } else {
           $connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('connector', 'json');
   }


On the first line, you get a list of all services that are of type
"connector" and subtype "json". If the result if false, it means no
appropriate services were found and you probably want to issue an
error message.

On the contrary you are assured that there's at least one valid
service and you can get an instance of it by calling
:code:`t3lib_div::makeInstanceService()`.

The next step is simply to call the appropriate method from the API –
with the right parameters – depending on which format you want to have
in return. For example::

   $parameters = array(
            'uri' => 'http://forge.typo3.org/projects/extension-external_import/issues.json',
            'encoding' => 'utf-8',
   );
   $data = $connector->fetchArray($parameters);


This will return a PHP array from the decoded JSON data. The
:code:`fetchRaw()` will return the JSON data as a string.

The :code:`fetchXML()` method returns a XML version of the PHP array
transformed using :code:`t3lib_div::array2xml_cs()`.

