<?php
if ( !class_exists('BBC_Serialiser_SimpleXmlSerialiser') ) {
    include 'BBC/Serialiser/SimpleXmlSerialiser.php';
}
if ( !class_exists('MockModel') ) {
    include 'BBC/MockModel.php';
}

class SimpleXmlSerialiserTest extends PHPUnit_Framework_TestCase
{
    protected $_object;
    protected $_serialiser;

    public function setUp()
    {
        $this->_object = new MockModel();
        $this->_serialiser = new BBC_Serialiser_SimpleXmlSerialiser();
    }

    public function testEmptyMappingXmlSerialisation()
    {
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals(null, $xml);
    }

    public function testNoMappingXmlSerialisation()
    {
        $object = new StdClass();
        $xml = $this->_serialiser->serialise($object);
        $this->assertEquals(null, $xml);
    }

    public function testNoEntityXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'po:property' => 'value',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals(null, $xml);
    }

    public function testSimpleXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => 'value',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'property' => 'value',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);
    }

    public function testBooleanXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => true,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>1</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => false,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>0</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '@po:property' => true,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing property=\"1\"/>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '@po:property' => false,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing property=\"0\"/>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testLiteralDateSimpleXmlSerialisation()
    {
        $date = new Zend_Date('2010-08-07T12:00:00Z', Zend_date::ISO_8601);
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => $date,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<thing><property>2010-08-07T12:00:00+00:00</property></thing>\n", $xml);
    }

    public function testDropEqualsSimpleXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property=' => 'value',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testCutSimpleXmlSerialisation()
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
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property><thing2><label>Label</label></thing2></property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

         $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '[po:property/properties]!' => $otherObject,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);
    }

    public function testSimpleAttributeXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '@po:property' => 'value',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing property=\"value\"/>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testArrayXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '[po:property]' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value1</property><property>value2</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);

        $object2 = new MockModel();
        $object2->setFeedMapping(array(
            'entity'  => 'category',
            'po:title' => 'Drama',
        ));
        $object3 = new MockModel();
        $object3->setFeedMapping(array(
            'entity' => 'category',
            'po:title' => 'Music',
        ));
        $this->_object->setFeedMapping(array(
            'entity'      => 'categories',
            '[po:category/categories]' => array($object2, $object3),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<categories><category><title>Drama</title></category><category><title>Music</title></category></categories>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testPluralKeyXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '[po:property/properties]' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        // Not very intuitive xml, but the object is an array, so we pluralise
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><properties>value1</properties><properties>value2</properties></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property/properties' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);

        $object1 = new MockModel();
        $object1->setFeedMapping(array(
            'entity'  => 'thing2',
            'po:test' => 'value',
        ));
        $object2 = new MockModel();
        $object2->setFeedMapping(array(
            'entity'  => 'thing3',
            'po:test' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '[po:property/properties]' => array($object1, $object2),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><properties><thing2><test>value</test></thing2><thing3><test>value</test></thing3></properties></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property/properties' => array($object1, $object2),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);

        // Only one value, so we take the singular
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property/properties' => 'value2',
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value2</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testArrayAttributeXmlSerialisation()
    {
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '@[po:property]' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing property=\"value1\" subproperty=\"value2\"/>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            '@po:property' => array('value1', 'value2'),
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);
    }

    public function testStdClassXmlSerialisation()
    {
        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'entity'        => 'thing',
            'po:property' => $stdClass,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value1</property><property>value2</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'entity'        => 'thing',
            '[po:property]' => $stdClass,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $this->assertEquals($expectedXml, $xml);
    }

    public function testStdClassAttributeXmlSerialisation()
    {
        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'entity'        => 'thing',
            '@po:property' => $stdClass,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        // Attributes are only picked up if the values are literals - no objects in there!
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value1</property><property>value2</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    
        $stdClass = new StdClass();
        $stdClass->p1 = "value1";
        $stdClass->p2 = "value2";
        $this->_object->setFeedMapping(array(
            'entity'        => 'thing',
            '@[po:property]' => $stdClass,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        // Attributes are only picked up if the values are literals - no objects in there!
        $this->assertEquals($expectedXml, $xml);
    }

    public function testChainedXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity'             => 'otherThing',
            '[po:otherProperty]' => array(1, 2),
        ));
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => $otherObject,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n" . 
            "<thing><property><otherThing><otherProperty>1</otherProperty><otherProperty>2</otherProperty></otherThing></property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testStdClassChainedXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity'             => 'otherThing',
            '[po:otherProperty]' => array(1, 2),
        ));
        $stdClass = new StdClass();
        $stdClass->p1 = $otherObject;
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => $stdClass,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml= "<?xml version=\"1.0\"?>\n<thing><property><otherThing><otherProperty>1</otherProperty><otherProperty>2</otherProperty></otherThing></property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testStdClassStdClassXmlSerialisation()
    {
        $stdClass2 = new StdClass();
        $stdClass2->p2 = "value";
        $stdClass1 = new StdClass();
        $stdClass1->p1 = $stdClass2;
        $this->_object->setFeedMapping(array(
            'entity'      => 'thing',
            'po:property' => $stdClass1,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }

    public function testCollapsedXmlSerialisation()
    {
        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity'           => 'property',
            'po:otherProperty' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'entity' => 'thing',
            'po:property' => $otherObject,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property><otherProperty>value</otherProperty></property></thing>\n";
        $this->assertEquals($expectedXml, $xml);

        $this->_object->setFeedMapping(array(
            'entity' => 'foo',
            'po:foo' => $otherObject,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<foo><property><otherProperty>value</otherProperty></property></foo>\n";
        $this->assertEquals($expectedXml, $xml);

        $otherObject = new MockModel();
        $otherObject->setFeedMapping(array(
            'entity'           => 'property',
            'po:property' => 'value',
        ));
        $this->_object->setFeedMapping(array(
            'entity' => 'thing',
            'po:property' => $otherObject,
        ));
        $xml = $this->_serialiser->serialise($this->_object);
        $expectedXml = "<?xml version=\"1.0\"?>\n<thing><property>value</property></thing>\n";
        $this->assertEquals($expectedXml, $xml);
    }
}
