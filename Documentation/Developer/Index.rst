.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: /Includes.rst.txt


.. _developer:

Developer's manual
------------------


.. _developer-using-connector:

Using the connector
^^^^^^^^^^^^^^^^^^^

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


.. _developer-paginators:

Custom paginators
^^^^^^^^^^^^^^^^^

As explained in the :ref:`Configuration chapter <configuration-paginator>`, only
the JSON-Hydra pagination mechanism is covered out of the box by this extension.
For any other mechanism, you need to develop your own paginator. This is done by
extending the :php:`\Cobweb\SvconnectorJson\Paginator\AbstractPaginator` class.

The following things need to be defined (or known):

Start page
  Member variable :code:`$startPage` defines the number of the page at the start
  of the pagination. By default, it is :code:`1`. In case it should be :code:`0`
  (or some other value), you will need to override the constructor from the abstract
  class and set a different value for :code:`$startPage`.

Paging parameter
  Member variable :code:`$pagingParameter` defines the name of the query parameter
  passed for pagination. By default, it is :code:`page`. If this is not the case for you,
  you will need to override the constructor from the abstract
  class and set a different value for :code:`$pagingParameter`.

Data
  Member variable :code:`$data` contains the data from the current page of results,
  as an array (i.e. it has gone through :code:`json_decode()`. Nothing to do here,
  it is loaded into the paginator for each page call and at your disposal for
  determining the next page.

getNextPage()
  This method is at the heart of the pagination mechanism. Based on the data from the current
  result set, it needs to send back the number of the next page to call. It is expected
  to fall back on the start page, if the next page cannot be defined.

aggregate()
  Once all pages of data have been fetched, the results must be aggregated. Again, this
  will be very different from one data source to another, and is thus implemented as a
  method of the custom paginator. The expected result of the call to :code:`aggregate()`
  is a data structure as if all data had been fetched in a single call.

The existing :php:`\Cobweb\SvconnectorJson\Paginator\HydraPaginator` is obviously a good
reference for how this all works.
