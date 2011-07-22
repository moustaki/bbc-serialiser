<?php
if ( !class_exists('BBC_Serialiser_JsonSerialiser') ) {
    include 'BBC/Serialiser/JsonSerialiser.php';
}
if ( !class_exists('MockModel') ) {
    include 'BBC/MockModel.php';
}

class JsonSerialiserTest extends PHPUnit_Framework_TestCase
{
    protected $_object;
    protected $_serialiser;

    public function setUp()
    {
        $this->_object = new MockModel();
        $this->_serialiser = new BBC_Serialiser_JsonSerialiser();
    }

    public function testEmptyMappingJsonSerialisation()
    {
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('[]', $json);
    }

    public function testNoMappingJsonSerialisation()
    {
        $object = new StdClass();
        $json = $this->_serialiser->serialise($object);
        $this->assertEquals(null, $json);
    }

    public function testSimpleJsonSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'po:property' => 'value',
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value"}', $json);

        $this->_object->setFeedMapping(array(
            'po:property' => false,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":false}', $json);

        $this->_object->setFeedMapping(array(
            'po:property' => true,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":true}', $json);

        $this->_object->setFeedMapping(array(
            'po:property' => null,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('[]', $json);

         $this->_object->setFeedMapping(array(
            'property' => 'value',
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value"}', $json);
    }

    public function testLiteralDateJsonSerialisation()
    {
        $date = new Zend_Date('2010-08-07T12:00:00Z', Zend_date::ISO_8601);
        $this->_object->setFeedMapping(array(
            'po:property' => $date,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"2010-08-07T12:00:00+00:00"}', $json);
    }

    public function testDropEqualsJsonSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'po:property=' => 'value',
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value"}', $json);
    }

    public function testCutJsonSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity' => 'thing2',
            'po:label' => 'Label',
            'po:property' => $this->_object,
        ));
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property!' => $otherObject,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $expectedJson = '{"property":{"label":"Label"}}';
        $this->assertEquals($expectedJson, $json);

         $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '[po:property/properties]!' => $otherObject,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedJson, $json);

        $otherObject->setFeedMapping(array(
            'po:label' => 'Label',
            '[po:array]' => array(new StdClass(), new StdClass()),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedJson, $json);
    }

    public function testArrayJsonSerialisation()
    {
        $this->_object->setFeedMapping(array(
            '[po:property]' => array('value1', 'value2'),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":["value1","value2"]}', $json);

        $this->_object->setFeedMapping(array(
            'po:label' => 'Label',
            '[po:property]' => array(),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"label":"Label"}', $json);
    }

    public function testPluralKeyJsonSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'                   => 'thing',
            '[po:property/properties]' => array('value1', 'value2'),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"properties":["value1","value2"]}', $json);

        // In that case, we don't pluralise, but rather adopt the sub* structure
        // to not override values in the JSON hash
        $this->_object->setFeedMapping(array(
            'entity'                   => 'thing',
            'po:property/properties'   => array('value1', 'value2'),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value1","subproperty":"value2"}', $json);

        $this->_object->setFeedMapping(array(
            'entity'                   => 'thing',
            '[po:property/properties]' => 'value2',
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value2"}', $json);
    }

    public function testStdClassJsonSerialisation()
    {
        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'entity'        => 'thing',
            '[po:property]' => $stdClass,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":["value1","value2"]}', $json);
    }

    public function testChainedJsonSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            '[po:otherProperty]' => array(1, 2, 3, 4),
        ));
        $this->_object->setFeedMapping(array(
            'po:property' => $otherObject, 
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":{"otherProperty":[1,2,3,4]}}', $json);

        $this->_object->setFeedMapping(array(
            '[po:property/properties]' => array($otherObject),
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"properties":[{"otherProperty":[1,2,3,4]}]}', $json);
    }

    public function testStdClassChainedJsonSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            '[po:otherProperty]' => array(1, 2, 3, 4),
        ));
        $stdClass = new StdClass();
        $stdClass->p1 = $otherObject;
        $this->_object->setFeedMapping(array(
            'po:property' => $stdClass,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":{"otherProperty":[1,2,3,4]}}', $json);
    }

    public function testStdClassStdClassJsonSerialisation()
    {
        $stdClass2 = new StdClass();
        $stdClass2->p2 = "value";
        $stdClass1 = new StdClass();
        $stdClass1->p1 = $stdClass2;
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => $stdClass1,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":"value"}', $json);
    }

    public function testCollapsedJsonSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'po:property' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'po:property' => $otherObject,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":{"property":"value"}}', $json);

        $otherObject->setFeedMapping(array(
            'entity'           => 'property',
            'po:otherProperty' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'entity' => 'thing',
            'po:property' => $otherObject,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":{"otherProperty":"value"}}', $json);

        $this->_object->setFeedMapping(array(
            'entity' => 'foo',
            'po:foo' => $otherObject,
        ));
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"foo":{"otherProperty":"value"}}', $json);

        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity'           => 'property',
            'po:property' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'entity' => 'thing',
            'po:property' => $otherObject,
        )); 
        $json = $this->_serialiser->serialise($this->_object);
        $this->assertEquals('{"property":{"property":"value"}}', $json);
    }
}
