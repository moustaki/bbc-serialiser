<?php
/**
 * BaseComponentTestCase.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BaseComponentTestCase
 * @desc A PHPUnit test case for testing the content of serialised
 * RDF documents.
 */
require_once 'EasyRdf/Parser/RdfXml.php';

class RdfSerialiserTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @desc An array of namespaces, used for shorthand RDF triple
     * notations in our tests.
     * @var array
     */
    protected $_namespaces = array(
        'foaf' => 'http://xmlns.com/foaf/0.1/',
        'dc'   => 'http://purl.org/dc/elements/1.1/',
        'cc'   => 'http://web.resource.org/cc/',
        'xsd'  => 'http://www.w3.org/2001/XMLSchema#',
        'rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'rss'  => 'http://purl.org/rss/1.0/',
        'mo'   => 'http://purl.org/ontology/mo/',
        'po'   => 'http://purl.org/ontology/po/',
    );

    /**
     * @desc A mapping of blank nodes used in the tests and blank nodes extracted from the document,
     * which id might change at each run of the test.
     * @var array
     */
    protected $_bnodes = array();

    /**
     * @desc A list of all blank nodes extracted from the document which we have mapped to
     * a blank node identifier used in our tests.
     * @var array
     */
    protected $_mapped = array();

    /**
     * @desc An EasyRDF graph
     * @var EasyRdf_Graph
     */
    protected $_graph;

    public function setUp()
    {
        $this->_graph = new EasyRdf_Graph();
        $this->_bnodes = array();
        $this->_mapped = array();

        EasyRdf_Namespace::reset();
        foreach ($this->_namespaces as $short => $long) {
            EasyRdf_Namespace::set($short, $long);
        }
    }

    /**
     * Given an EasyRdf_Graph, returns an array holding the triples with shortened URIs.
     * By simplifying the data structure, it makes it easier to write tests.
     *
     * @return array
     */
    public function getTriples()
    {
        $triples = array();
        foreach($this->_graph->resources() as $resource) {
            foreach($resource->properties() as $property) {
                foreach($resource->all($property) as $value) {
                    $triples[] = array(strval($resource), $property, strval($value));
                }
            }
        }
        return $triples;
    }

    /**
     * Asserts that the string provided holds the same triples as provided.
     * This assertion can be used as follows:
     * <code>
     * $this->assertTriples($rdfxmlstring,  array('/programmes/a-z', 'rdfs:seeAlso', '/programmes/a-z/by/a'));
     * <code>
     * 
     * @todo Check that the bnode matching is working properly
     *
     * @protected
     * @param string $data
     * @param object $expected
     * @param string $format
     */
    public function assertTriples($data, $expected, $format = 'rdfxml')
    {
        $this->_graph->parse($data, $format);
        $triples = $this->getTriples();
        $this->assertEquals(sizeof($triples), sizeof($expected), "The number of extracted triples (" . sizeof($triples) . ") does not match the number of expected triples (" . sizeof($expected) . "): " . print_r($triples, true));
        foreach ($expected as $t) {
            $this->assertTrue($this->tripleIn($t, $triples), "The triple (" . $t[0] . ", " . $t[1] . ", " . $t[2] . ") was not found in the extracted triples: " . print_r($triples, true));
        }
    }

    /**
     * Returns true if the triple is in a set of triples.
     * It handles blank nodes. As the id of blank nodes extracted from the document
     * might change each time we run the test, we allow for
     * any blank node identifier to be used in the tests, and we maintain a mapping.
     *
     * @protected
     * @param array $t
     * @param array $triples
     */
    protected function tripleIn($t, $triples)
    {
        if (substr($t[0], 0, 2) == '_:') {
            if (array_key_exists($t[0], $this->_bnodes)) {
                $t[0] = $this->_bnodes[$t[0]];
            } else {
                $t0 = $t[0];
                $t[0] = null;
            }
        }
        if (substr($t[1], 0, 2) == '_:') {
            if (array_key_exists($t[1], $this->_bnodes)) {
                $t[1] = $this->_bnodes[$t[1]];
            } else {
                $t1 = $t[1];
                $t[1] = null;
            }
        }
        if (substr($t[2], 0, 2) == '_:') {
            if (array_key_exists($t[2], $this->_bnodes)) {
                $t[2] = $this->_bnodes[$t[2]];
            } else {
                $t2 = $t[2];
                $t[2] = null;
            }
        }
        foreach ($triples as $triple) {
            if (
                (
                    ($t[0] != null && $triple[0] == $t[0]) || 
                    ($t[0] == null && !in_array($triple[0], $this->_mapped))
                ) &&
                (
                    ($t[1] != null && $triple[1] == $t[1]) || 
                    ($t[1] == null && !in_array($triple[1], $this->_mapped))
                ) &&
                (
                    ($t[2] != null && $triple[2] == $t[2]) || 
                    ($t[2] == null && !in_array($triple[2], $this->_mapped))
                )
               ) {
                if ($t[0] == null) {
                    $this->_bnodes[$t0] = $triple[0];
                    $this->_mapped[] = $triple[0];
                }
                if ($t[1] == null) {
                    $this->_bnodes[$t1] = $triple[1];
                    $this->_mapped[] = $triple[1];
                }
                if ($t[2] == null) {
                    $this->_bnodes[$t2] = $triple[2];
                    $this->_mapped[] = $triple[2];
                }
                return true;
            }
        }
        return false;
    }
}
