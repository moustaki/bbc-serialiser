<?php
/**
 * Serialisable.php
 * 
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser_Serialisable
 * @desc An interface for serialisable PHP objects
 */
interface BBC_Serialiser_Serialisable
{
    /**
     * Returns an associative array detailing how the
     * object should be serialised when including it in a feed.
     *
     * This mapping has the following elements:
     *
     * 'urimap' - Only used by the RDF serialiser, this holds two things:
     * mappings between keywords and URIs, and the URI of the object itself.
     * For example:
     * {code}
     * 'urimap' => array(
     *    'this'   => 'http://moustaki.org/foaf.rdf#moustaki',
     *    'person' => 'http://xmlns.com/foaf/0.1/Person',
     *  )
     * {code}
     * means that the object we're trying to serialise is identified
     * by the URI 'http://moustaki.org/foaf.rdf#moustaki', and that
     * whenever we encounter 'person' when serialising an attribute of the object, we
     * should substitute it with 'http://xmlns.com/foaf/0.1/Person'.
     * If 'this' is set to null, the serialiser will output an RDF blank node.
     *
     * 'entity' - Only used by the XML serialisers, denotes the
     * top-level element in which the feed for this object will be encapsulated.
     * For example:
     * {code}
     * 'entity' => 'person'
     * {code}
     * means that when serialising in XML, the feed will look like <person>...</person>.
     *
     * Then, the actual mapping from object attributes to tags in the RDF, XML or JSON is expressed through:
     * 'namespace:key' => 'value', which serialises 'value' in the 'namespace:key' tag. In XML and JSON
     * namespaces are stripped out. 
     *
     * The key can also be written in a way that will indicates to the serialiser how it should serialise it. For example:
     *   '@namespace:key' => 'value' means that, in XML, 'value' should be serialised as an XML attribute. 
     *   '[namespace:key/keys]' => array(...) means that, in JSON, the array should be serialised as a JSON array. When
     *       serialising in JSON or XML, the plural key (after the '/', 'keys' here) will be chosen over the singular one.
     *   'namespace:key=' => 'value' means that, in RDF, we don't use the urimap array - the value will be the literal 'value'.
     *   'namespace:key!' => $object means that we stop serialising any new objects after $object has been serialised 
     *
     * To see examples of the different mapping constructs and the corresponding serialised objects, see the unit tests
     * for the RDF, JSON and XML serialisers.
     *
     * @public
     * @return array
     */
    public function getFeedMapping();
}
