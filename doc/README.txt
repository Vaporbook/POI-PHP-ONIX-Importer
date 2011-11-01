
POI Dawg

 \ ______/ V`-,
  }        /~~
 /_)^ --,r'
|b      |b


PHP ONIX Importer
(c) Book Glutton, Inc. 2009-2011

License: GPL
Contact: Aaron Miller <aaron at bookglutton.com>
Last Modified: October 4, 2011

REQUIREMENTS
Posix-compliant system, Mongo DB, PHP 5.3.5 CLI with mongo, xml, zip and xslt modules

Originally used in BookGlutton.com's production ebook import system.

Designed to be a simple, fast, extensible way to convert very large ONIX 2.1 sources with arbitrary data structures into manageable, queryable data without a lot of application overhead.

FEATURES
*Short tags are mapped to human-readable long tags.
*ONIX product records are converted into SimpleXMLElements, which can be extended into custom objects.
*Allows arbitrary XML structures as input, and stores them as Mongo object structures which can then be queried, indexed or serialized as JSON.

TESTED SOURCES

This has been tested with Random House's full catalog .ZOT feed (zip compressed ONIX 2.1), with over 218,000 records successfully converted and imported into a MongoDB store.

Also tested with Penguin-sourced ONIX, also successful at importing.

LOG

2011-10-31 - Added ISBN insert and query via web API. Fixed issue with creation of new records, added test form interface.



