<?php
/**
 * EasyRdfSerialiser.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser_EasyRdfSerialiser
 * @desc A class serialising PHP objects using EasyRdf
 */
class BBC_Serialiser_EasyRdfSerialiser implements BBC_Serialiser_Interface
{
    /**
     * To be overriden - EasyRdf serialisation format to use
     * @var string
     */
    protected $_serialisation_format;
    /**
     * An RDF Graph
     * @var EasyRdf_Graph
     */
    protected $_graph;
    /**
     * Namespaces
     * @var array
     */
    protected $_namespaces = array(
        'rdf'       => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs'      => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl'       => 'http://www.w3.org/2002/07/owl#',
        'foaf'      => 'http://xmlns.com/foaf/0.1/',
        'sioc'      => 'http://rdfs.org/sioc/ns#',
        'po'        => 'http://purl.org/ontology/po/',
        'pod'       => 'http://purl.org/ontology/pod/',
        'mo'        => 'http://purl.org/ontology/mo/',
        'skos'      => 'http://www.w3.org/2008/05/skos#',
        'time'      => 'http://www.w3.org/2006/time#',
        'dc'        => 'http://purl.org/dc/elements/1.1/',
        'dcterms'   => 'http://purl.org/dc/terms/',
        'gr'        => 'http://purl.org/goodrelations/v1#',
        'ov'        => 'http://open.vocab.org/terms/',
        'wgs84_pos' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
        'timeline'  => 'http://purl.org/NET/c4dm/timeline.owl#',
        'event'     => 'http://purl.org/NET/c4dm/event.owl#',
    );
    /**
     * Serialises an object in RDF/XML
     *
     * @public
     * @param object $object
     * @return string
     */
    public function serialise($object)
    {
        if (isset($this->_serialisation_format)) {
            if ($object instanceof BBC_Serialiser_Serialisable && $object->getFeedMapping() != array()) {
                $this->_initialiseNamespaces();
                $this->_initialiseGraph();
                $this->_processResource($object);
                return $this->_graph->serialise($this->_serialisation_format);
            }
        }
    }

    protected function _initialiseNamespaces()
    {
        foreach ($this->_namespaces as $short => $long) {
            EasyRdf_Namespace::set($short, $long);
        }
    }

    protected function _initialiseGraph()
    {
        $this->_graph = new EasyRdf_Graph();
    }

    protected function _processResource($object, $stop = false)
    {
        $mapping = $object->getFeedMapping();
        if (array_key_exists('urimap', $mapping) && array_key_exists('this', $mapping['urimap'])) {
            $urimap = $mapping['urimap'];
            $uri = $urimap['this'];
            if (!isset($uri)) $uri = $this->_graph->newBNode();
            foreach($mapping as $key => $value) {
                 if (substr($key, -1) == '=') {
                    $key = substr($key, 0, -1);
                    $use_urimap = false;
                } else {
                    $use_urimap = true;
                }
                if (substr($key, -1) == '!') {
                    $key = substr($key, 0, -1);
                    $stop_at_next = true;
                } else {
                    $stop_at_next = false;
                }
                if (preg_match('/^(?:@?\[(\w+):((\/|\w)+)\]|@?(\w+):((\/|\w)+))$/', $key, $matches)) {
                    if (count($matches) == 4) {
                        $key = $matches[1] . ':' . $matches[2];
                    } elseif (count($matches) == 7) {
                        $key = $matches[4] . ':' . $matches[5];
                    }
                    if (preg_match('/^(\w+):(\w+)\/(\w+)$/', $key, $m)) {
                        $key = $m[1] . ':' . $m[2];
                    }
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $this->_processProperty($stop_at_next, $stop, $use_urimap, $urimap, $uri, $key, $v);
                        }
                    } else {
                        $this->_processProperty($stop_at_next, $stop, $use_urimap, $urimap, $uri, $key, $value);
                    }
                }
            }
            return $uri;
        }
    }

    protected function _processProperty($stop_at_next, $stop, $use_urimap, $urimap, $uri, $key, $value)
    {
        if (is_object($value)) {
            if (get_class($value) == 'Zend_Uri_Http') {
                $this->_graph->addResource($uri, $key, $value->getUri());
            } elseif (get_class($value) == 'Zend_Date') {
                $this->_graph->add($uri, $key, array('type' => 'literal', 'datatype' => 'xsd:dateTime', 'value' => $value->get(Zend_Date::ISO_8601)));
            } elseif (get_class($value) == 'stdClass') {
                foreach (get_object_vars($value) as $k => $v) {
                    $this->_processProperty($stop_at_next, $stop, $use_urimap, $urimap, $uri, $key, $v);
                }
            } elseif (!$stop && $value instanceof BBC_Serialiser_Serialisable) {
                $object = $this->_processResource($value, $stop_at_next);
                if (isset($object)) {
                    $this->_graph->addResource($uri, $key, $object);
                }
            }
        } elseif (!is_object($value) && $value != '') {
            if ($use_urimap && is_string($value) && array_key_exists($value, $urimap)) {
                $this->_graph->addResource($uri, $key, $urimap[$value]);
            } else {
                $this->_graph->add($uri, $key, $value);
            }
        }
    }
}
