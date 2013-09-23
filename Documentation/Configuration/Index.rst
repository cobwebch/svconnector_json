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

+----------------+---------------+-----------------------------------------------------------------------+
| Parameter      | Data type     | Description                                                           |
+================+===============+=======================================================================+
| uri            | string        | URI of the JSON resource. May be local or remote.                     |
|                |               |                                                                       |
|                |               | **Examples:**                                                         |
|                |               |                                                                       |
|                |               | http://forge.typo3.org/projects/extension-external_import/issues.json |
|                |               |                                                                       |
|                |               | EXT:myext/res/some.json                                               |
|                |               |                                                                       |
|                |               | fileadmin/imports/some.json                                           |
+----------------+---------------+-----------------------------------------------------------------------+
| encoding       | string        | Encoding of the data found in the file. This value must match any of  |
|                |               | the encoding values or their synonyms found in class                  |
|                |               | :code:`\TYPO3\CMS\Core\Charset\CharsetConverter`.                     |
|                |               | Note that this means pretty much all the usual encodings.             |
|                |               | If unsure look at array                                               |
|                |               | :code:`\TYPO3\CMS\Core\Charset\CharsetConverter::synonyms`.           |
+----------------+---------------+-----------------------------------------------------------------------+
| useragent      | string        | User agent to fake. This is sometimes necessary to bypass access      |
|                |               | restrictions on some sites. Don't include the "User-Agent:" part of   |
|                |               | the header.                                                           |
|                |               |                                                                       |
|                |               | **Example:**                                                          |
|                |               |                                                                       |
|                |               | Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.13)   |
|                |               | Gecko/20101203 Firefox/3.6.13                                         |
+----------------+---------------+-----------------------------------------------------------------------+
| accept         | string        | Type of content accepted. This is sometimes necessary to really get   |
|                |               | the data in JSON format. Don't include the "Accept:" part of          |
|                |               | the header.                                                           |
|                |               |                                                                       |
|                |               | **Example:**                                                          |
|                |               |                                                                       |
|                |               | application/json                                                      |
+----------------+---------------+-----------------------------------------------------------------------+

.. note::

   When using this connector with **external_import**, please mind that the JSON data
   may not fit the structure expected by **external_import**. Indeed this extension
   expects data of type array to be purely two-dimensional, i.e. an indexed list of
   associative sub-arrays.

   Use a hook like :code:`processArray` in **svconnector_json** to transform the
   data's structure before feeding it into **external_import**.
