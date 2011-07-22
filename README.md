# BBC Serialiser

## Summary

Makes it easy to create JSON, XML and RDF feeds out of a set of PHP objects.

## Introduction

This library is meant to make it easy to generate feeds in a PHP MVC application
(typically Zend). A declarative mapping is specified in the models, and will
be used to automatically generate feeds. It will ensure your feeds are 
are consistent across your application and that it is really easy to add new ones
(no more custom data view templates for each new feed).

## How it works

It is able to serialise objects implementing the Serialisable interface. 
It calls the getFeedMapping method on the object it is trying to serialise, 
which will return an associative array detailing how and what should be rendered in a feed. 
It can cascade to other objects if they also implement Serialisable.

## Feed mapping syntax

The feed mapping returned by the 'getFeedMapping' method can have the following elements:

### urimap

The 'urimap' element is only used by the RDF serialiser. It holds mapping between keywords and URIs (each time the serialiser will encounter a keyword, it will replace it by the corresponding URI), and the URI of the object itself.

Example:

    array('urimap' => array(
        'this' => 'http://moustaki.org/foaf.rdf#moustaki',
        'person' => 'http://xmlns.com/foaf/0.1/Person',
    ));

This snippet means that the object we are trying to serialise is identified by the URI 'http://moustaki.org/foaf.rdf#moustaki', and that whenever we encounter 'person' when serialising an attribute of the object, we should substitute it with the URI 'http://xmlns.com/foaf/0.1/Person'. If 'this' is set to null, the serialiser will output an RDF blank node.

If no 'urimap' is available, the RDF serialisation will be empty.

### entity

The 'entity' element is only used by the XML serialiser. It holds the name of the top-level element in which the feed for this object will be encapsulated.

Example:

    array('entity' => 'person');

This snippet means that when serialising in XML, the top-level XML element will be <person>...</person>.

If no 'entity' element is available, the XML serialisation will be empty.

### mappings

The mappings from object attributes to pairs of (property, objects) in RDF, and tags in XML or JSON is expressed through key/value pairs.

Example:

    array('namespace:key' => 'value');

This snippet means that we serialise 'value' in the 'namespace:key' tag. In XML and JSON namespaces are stripped out.

A key can also point to an object, which the serialiser will also try to serialise.

Example:

    array('namespace:key' => $object);

This snippet means that we serialise the $object object within the 'namespace:key' tag.

A number of clues can be given when writing the key, indicating to the serialiser how it should handle it.

* '@namespace:key' => 'value' - in XML, 'value' will be serialised as an XML attribute;
* '[namespace:key/keys]' => array(...) - in JSON, the array will be serialised as a JSON array. When serialising in JSON or XML, the plural key should be chosen over the singular one.
* 'namespace:key=' => 'value' - in RDF, the 'urimap' should not be used by the serialiser. Here, the value will be the literal 'value'.
* 'namespace:key!' => $object - we stop serialising any new objects after $object has been serialised.
