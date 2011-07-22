<?php
/**
 * SimpleXmlSerialiser.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser_SimpleXmlSerialiser
 * @desc A class serialising PHP objects in XML
 */
class BBC_Serialiser_SimpleXmlSerialiser implements BBC_Serialiser_Interface
{
    /**
     * Serialises an object in XML
     *
     * @public
     * @param object $object
     * @return string
     */
    public function serialise($object)
    {
        if ($object instanceof BBC_Serialiser_Serialisable) {
            $mapping = $object->getFeedMapping();
            if (array_key_exists('entity', $mapping)) {
                $xml = new SimpleXMLElement('<' . $mapping['entity'] . '/>');
                $this->_writeXmlResource($object, $xml);
                return $xml->asXML();
           }
        }
    }

    protected function _writeXmlResource($object, &$xml, $stop = false)
    {
        if ($object instanceof BBC_Serialiser_Serialisable) {
            $mapping = $object->getFeedMapping();
            foreach ($mapping as $key => $value) {
                if ($key == 'entity' || $key == 'urimap') continue;
                if (substr($key, -1) == '=') $key = substr($key, 0, -1);
                if (substr($key, -1) == '!') {
                    $key = substr($key, 0, -1);
                    $stop_at_next = true;
                } else {
                    $stop_at_next = false;
                }
                if (preg_match('/^(?:@?\[((\w+):)?((\/|\w)+)\]|@?((\w+):)?((\/|\w)+))$/', $key, $matches)) {
                    if (count($matches) == 5) {
                        $xmlKey = $matches[3];
                    } elseif (count($matches) == 9) {
                        $xmlKey = $matches[7];
                    }
                    if (preg_match('/^(\w+)\/(\w+)$/', $xmlKey, $m)) {
                        $xmlKey = $m[1];
                        $pluralKey = $m[2];
                    } else {
                        $pluralKey = $xmlKey;
                    }
                    if ($key[0] == '@' && !is_object($value)) {
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                if (!is_object($v) && $v != '') {
                                    $xml[$xmlKey] = $v;
                                    // Horribly hacky, but otherwise 
                                    // data gets overridden at each iteration
                                    $xmlKey = 'sub' . $xmlKey;
                                }
                            }
                        } elseif ($this->_xmlValue($value) != '') {
                            $xml[$xmlKey] = $this->_xmlValue($value);
                        }
                    } else {
                        $this->_writeXmlProperty($stop_at_next, $stop, $xmlKey, $pluralKey, $value, $xml);
                    }
                }
            }
        }
    }

    protected function _writeXmlProperty($stop_at_next, $stop, $key, $pluralKey, $value, &$xml)
    {
        if (is_object($value)) {
            if (get_class($value) == 'stdClass') {
                foreach (get_object_vars($value) as $k => $v) {
                    $this->_writeXmlProperty($stop_at_next, $stop, $pluralKey, $pluralKey, $v, $xml);
                }
            } elseif (get_class($value) == 'Zend_Date') {
-                $v = $value->get(Zend_Date::ISO_8601);
-                $xml->{$key}[] = $v;

            } elseif (!$stop && $value instanceof BBC_Serialiser_Serialisable) {
                $mapping = $value->getFeedMapping();
                if (array_key_exists('entity', $mapping)) {
                    // collapsing
                    if ($mapping['entity'] == $key && $xml->getName() == $key) {
                        $this->_writeXmlResource($value, $xml, $stop_at_next);
                    } elseif ($mapping['entity'] == $key) {
                        $this->_writeXmlResource($value, $xml->{$key}[], $stop_at_next);
                    } elseif ($xml->getName() == $key) {
                        $this->_writeXmlResource($value, $xml->{$mapping['entity']}[], $stop_at_next);
                    } else {
                        $this->_writeXmlResource($value, $xml->{$key}->{$mapping['entity']}[], $stop_at_next);
                    }
                }
            }
        } elseif (is_array($value)) {
            foreach ($value as $v) {
                if (isset($pluralKey)) {
                    $this->_writeXmlProperty($stop_at_next, $stop, $pluralKey, $pluralKey, $v, $xml);
                } else {
                    $this->_writeXmlProperty($stop_at_next, $stop, $key, $pluralKey, $v, $xml);
                }
            }
        } else {
            $v = $this->_xmlValue($value);
            if ($v != '') {
                // collapsing
                if ($xml->getName() == $key) {
                    $xml[] = $v;
                } else {
                    $xml->{$key}[] = $v;
                }
            }
        }
    }

    protected function _xmlValue($v)
    {
        if (is_bool($v) && $v) {
            return '1';
        } elseif (is_bool($v) && !$v) {
            return '0';
        } else {
            return $v;
        }
    }
}
