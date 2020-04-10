.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
-------------

The various "fetch" methods of the connector all take the same
parameters:

+----------------+---------------+-------------------------------------------------------------------------------+
| Parameter      | Data type     | Description                                                                   |
+================+===============+===============================================================================+
| uri            | string        | URI of the JSON resource to read. This may be any of the following syntaxes:  |
|                |               |                                                                               |
|                |               | - absolute file path: :file:`/var/foo/web/fileadmin/import/bar.json`          |
|                |               |   (within the TYPO3 root path or :code:`TYPO3_CONF_VARS[BE][lockRootPath]`)   |
|                |               | - file path relative to the TYPO3 root:                                       |
|                |               |   :file:`fileadmin/import/foo.json`                                           |
|                |               | - file path using :code:`EXT:`:                                               |
|                |               |   :file:`EXT:foo/Resources/Private/Data/bar.json`                             |
|                |               | - fully qualified URL, e.g. :file:`http://www.example.com/foo.json`           |
|                |               | - FAL reference with storage ID and file identifier:                          |
|                |               |   :file:`FAL:2:/foo.json`                                                     |
|                |               | - custom syntax: :file:`MYKEY:whatever_you_want`, see                         |
|                |               |   :ref:`Connector Services <svconnector:developers-utilities-reading-files>`  |
+----------------+---------------+-------------------------------------------------------------------------------+
| encoding       | string        | Encoding of the data found in the file. This value must match any of          |
|                |               | the encoding values recognized by the PHP libray "mbstring". See              |
|                |               | https://www.php.net/manual/en/mbstring.supported-encodings.php                |
+----------------+---------------+-------------------------------------------------------------------------------+
| useragent      | string        | User agent to fake. This is sometimes necessary to bypass access              |
|                |               | restrictions on some sites. Don't include the "User-Agent:" part of           |
|                |               | the header.                                                                   |
|                |               |                                                                               |
|                |               | **Example:**                                                                  |
|                |               |                                                                               |
|                |               | Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.13)           |
|                |               | Gecko/20101203 Firefox/3.6.13                                                 |
+----------------+---------------+-------------------------------------------------------------------------------+
| accept         | string        | Type of content accepted. This is sometimes necessary to really get           |
|                |               | the data in JSON format. Don't include the "Accept:" part of                  |
|                |               | the header.                                                                   |
|                |               |                                                                               |
|                |               | **Example:**                                                                  |
|                |               |                                                                               |
|                |               | application/json                                                              |
+----------------+---------------+-------------------------------------------------------------------------------+

.. note::

   When using this connector with **external_import**, please mind that the JSON data
   may not fit the structure expected by **external_import**. Indeed this extension
   expects data of type array to be purely two-dimensional, i.e. an indexed list of
   associative sub-arrays.

   Use a hook like :code:`processArray` in **svconnector_json** to transform the
   data's structure before feeding it into **external_import**.
