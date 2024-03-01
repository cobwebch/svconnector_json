.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
-------------

The various "fetch" methods of the connector all take the same
parameters, described below.


.. _configuration-uri:

uri
^^^

Type
  string

Description
  URI of the JSON resource to read. This may be any of the following syntaxes:

  - absolute file path: :file:`/var/foo/web/fileadmin/import/bar.json`
    (within the TYPO3 root path or :code:`TYPO3_CONF_VARS[BE][lockRootPath]`)
  - file path relative to the TYPO3 root:
    :file:`fileadmin/import/foo.json`
  - file path using :code:`EXT:`:
    :file:`EXT:foo/Resources/Private/Data/bar.json`
  - fully qualified URL, e.g. :file:`http://www.example.com/foo.json`
  - FAL reference with storage ID and file identifier:
    :file:`FAL:2:/foo.json`
  - custom syntax: :file:`MYKEY:whatever_you_want`, see
    :ref:`Connector Services <svconnector:developers-utilities-reading-files>`


.. _configuration-encoding:

encoding
^^^^^^^^

Type
  string

Description
  Encoding of the data found in the file. This value must match any of
  the encoding values recognized by the PHP libray "mbstring". See
  https://www.php.net/manual/en/mbstring.supported-encodings.php


.. _configuration-headers:

headers
^^^^^^^

Type
  array

Description
  Key-value pairs of headers that should be sent along with the request.

Example
  Example headers for settings an alternate user agent and defining what reponse
  format to accept.

  .. code-block:: php

      'headers' => [
         'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:75.0) Gecko/20100101 Firefox/75.0',
         'Accept' => 'application/json',
      ]


.. _configuration-query-parameters:

queryParameters
^^^^^^^^^^^^^^^

Type
  array

Description
  Key-value pairs of query parameters that should be added to the URI. This will
  only when the URI is a fully qualified URL and not in any of the other possibilities
  described above.

Example
  Assuming that the `uri` parameter is "https://example.com", with the following quey parameters:

  .. code-block:: php

      'queryParameters' => [
         'foo' => 'bar',
      ]

  the full URI to be queried will be "https://example.com?foo=bar".


.. _configuration-paginator:

paginator
^^^^^^^^^

Type
  string

Description
  Many APIs present results that are paginated. Since these pagination mechanisms can be
  quite diverse, this extension cannot provide a solution that fits all situations.

  Out of the box, the JSON-Hydra pagination format is supported, with the use of the "hydra" keyword.

  For any other pagination mechanism, you will need to :ref:`develop your own Paginator class <developer-paginators>`.

  The results are aggregated as if all data had been fetched in a single call.

  .. important::
     This works only with :code:`fetchArray()` and :code:`fetchXMLl()`. Since :code:`fetchRaw`
     returns the original JSON data as a string, it only ever returns the result of the first page.

  .. warning::
     If you use pagination, you **need** to pass any other quey parameter from the `uri`
     with the `queryParameters` and not have them directly in the `uri`.

Example
  To enable pagination for a Hydra data source:

  .. code-block:: php

      'paginator' => 'hydra'

  To enable pagination for another type of data source with a custom Paginator:

  .. code-block:: php

      'paginator' => \MyVendorName\MyExtension\Paginator\FooPaginator::class
