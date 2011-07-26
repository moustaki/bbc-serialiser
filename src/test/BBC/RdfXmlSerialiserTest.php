<?php
if ( !class_exists('BBC_Serialiser_RdfXmlSerialiser') ) {
    include 'BBC/Serialiser/RdfXmlSerialiser.php';
}
if ( !class_exists('RdfSerialiserTestCase') ) {
    include 'BBC/RdfSerialiserTestCase.php';
}
if ( !class_exists('MockModel') ) {
    include 'BBC/MockModel.php';
}

class RdfXmlSerialiserTest extends RdfSerialiserTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_object = new MockModel();
        $this->_serialiser = new BBC_Serialiser_RdfXmlSerialiser();
    }

    public function testEmptyMappingRdfXmlSerialisation()
    {
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertEquals(null, $rdf);
    }

    public function testNoMappingRdfXmlSerialisation()
    {
        $object = new StdClass();
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertEquals(null, $rdf);
    }

    public function testNoUriRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'po:property' => 'value',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array());

        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'key' => 'value',
            ),
            'po:property' => 'value',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array());
    }

    public function testSimpleRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com?foo=bar&key=value',
            ),
            'po:property' => 'value',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com?foo=bar&key=value', 'po:property', 'value'),
        ));
    }

    public function testSimpleUriRdfXmlSerialisation()
    {
        $uri = Zend_Uri::factory('http://example.com/avalue?a=b&c=d');
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property' => $uri,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'http://example.com/avalue?a=b&c=d'),
        ));
    }

    public function testBlankNodeRdfXmlSerialisation()
    {
        $uri = Zend_Uri::factory('http://example.com/avalue');
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => null,
            ),
            'po:property' => $uri,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('_:genid1', 'po:property', 'http://example.com/avalue'),
        ));
    }

    public function testLiteralEscapingRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property' => 'value1 & value2',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'value1 & value2'),
        ));
    }

    public function testLiteralDateRdfXmlSerialisation()
    {
        $date = new Zend_Date('2010-08-07T12:00:00Z', Zend_date::ISO_8601);
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property' => $date,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', '2010-08-07T12:00:00+00:00'),
        ));
        $this->assertEquals('xsd:dateTime', $this->_graph->resource('http://example.com')->get('po:property')->getDatatype());
    }

    public function testLiteralDurationRdfXmlSerialisation()
    {
        $duration = new Zend_Measure_Time(60, Zend_Measure_Time::MINUTE);
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property' => $duration,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'PT3600S'),
        ));
        $this->assertEquals('xsd:duration', $this->_graph->resource('http://example.com')->get('po:property')->getDatatype());
    }

    public function testUriMapRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
                'value' => 'http://example.com/value',
            ),
            'po:property' => 'value',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'http://example.com/value'),
        ));
    }

    public function testBypassUriMapRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
                'value' => 'http://example.com/value',
            ),
            'po:property=' => 'value',
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'value'),
        ));
    }

    public function testCutRdfXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example2.com',
            ),
            'po:label'    => 'Label',
            'po:property' => $this->_object,
        ));
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property!' => $otherObject, 
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'http://example2.com'),
            array('http://example2.com', 'po:label', 'Label'),
        ));

        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            '[po:property/properties]!' => array($otherObject),
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'http://example2.com'),
            array('http://example2.com', 'po:label', 'Label'),
        ));
    }

    public function testArrayRdfXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property' => array(1, 2),
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', '1'),
            array('http://example.com', 'po:property', '2'),
        ));

        # Plurals don't have any effects on RDF/XML serialisations
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            'po:property/properties' => array(1, 2),
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', '1'),
            array('http://example.com', 'po:property', '2'),
        ));

        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
            ),
            '[po:property/properties]' => array(1, 2),
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', '1'),
            array('http://example.com', 'po:property', '2'),
        ));
    }

    public function testStdClassRdfXmlSerialisation()
    {
        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://example.com',
                'value1' => 'http://example.com/value1',
            ),
            'po:property' => $stdClass,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://example.com', 'po:property', 'http://example.com/value1'),
            array('http://example.com', 'po:property', 'value2'),
        ));
    }    

    public function testChainedRdfXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://ex.com/2',
            ),
            'po:otherProperty' => array(1, 2),
        ));
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://ex.com/1',
            ),
            'po:property' => $otherObject,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://ex.com/1', 'po:property', 'http://ex.com/2'),
            array('http://ex.com/2', 'po:otherProperty', 1),
            array('http://ex.com/2', 'po:otherProperty', 2),
        ));
    }

    public function testStdClassChainedRdfXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
             'urimap' => array(
                'this' => 'http://ex.com/2',
            ),
            'po:otherProperty' => array(1, 2),
        ));
        $stdClass = new StdClass();
        $stdClass->p1 = $otherObject;
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://ex.com/1',
            ),
            'po:property' => $stdClass,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://ex.com/1', 'po:property', 'http://ex.com/2'),
            array('http://ex.com/2', 'po:otherProperty', 1),
            array('http://ex.com/2', 'po:otherProperty', 2),
        ));
    }

    public function testStdClassStdClassRdfXmlSerialisation()
    {
        $stdClass2 = new StdClass();
        $stdClass2->p2 = "value";
        $stdClass1 = new StdClass();
        $stdClass1->p1 = $stdClass2;
        $this->_object->setFeedMapping(array(
            'urimap' => array(
                'this' => 'http://ex.com/1',
            ),
            'po:property' => $stdClass1,
        ));
        $rdf = $this->_serialiser->serialise($this->_object);
        $this->assertTriples($rdf, array(
            array('http://ex.com/1', 'po:property', 'value'),
        ));
    }
}
