<?php
/**
 * JsonSerialiser.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser_JsonSerialiser
 * @desc A class serialising PHP objects in JSON
 */
class BBC_Serialiser_JsonSerialiser implements BBC_Serialiser_Interface
{
    /**
     * Serialises an object in JSON
     *
     * @public
     * @param object $object
     * @return string
     */
    public function serialise($object)
    {
        if ($object instanceof BBC_Serialiser_Serialisable) {
            $mapping = $object->getFeedMapping();
            $data = $this->_writeJsonResource($object);
            return Zend_Json::encode($data);
        }
    }

    protected function _writeJsonResource($object, $stop = false)
    {
        $data = array();
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
                    $jsonKey = $matches[3];
                    $interpretsValueAsJsonArray = True;
                } elseif (count($matches) == 9) {
                    $jsonKey = $matches[7];
                    $interpretsValueAsJsonArray = False;
                }
                if (preg_match('/^(\w+)\/(\w+)$/', $jsonKey, $m)) {
                        $jsonKey = $m[1];
                        $pluralKey = $m[2];
                    } else {
                        $pluralKey = $jsonKey;
                    }
                $data = array_merge($data, $this->_writeJsonProperty($stop_at_next, $stop, $jsonKey, $pluralKey, $value, $interpretsValueAsJsonArray));
            }
        }
        return $data;
    }

    protected function _writeJsonProperty($stop_at_next, $stop, $key, $pluralKey, $value, $interpretsValueAsJsonArray)
    {
        $data = array();
        if (is_array($value)) {
            if (!empty($value)) {
                if ($interpretsValueAsJsonArray) {
                    $toMerge = $this->_writeJsonProperty($stop_at_next, $stop, $key, $pluralKey, $value, False);
                    if (count($toMerge) > 0) {
                        $data = array_merge($data, array($pluralKey => array_values($toMerge)));
                    }
                } else {
                    foreach ($value as $v) {
                        $toMerge = $this->_writeJsonProperty($stop_at_next, $stop, $key, $pluralKey, $v, False);
                        $data = array_merge($data, $toMerge);
                        // Horribly hacky, but otherwise 
                        // data gets overridden at each iteration
                        // If you're concerned, go for an array ([key] => value)
                        if (count($toMerge) > 0) {
                            $key = 'sub' . $key;
                        }
                    }
                }
            }
        } elseif (is_object($value)) {
            if (get_class($value) == 'stdClass') {
                $arrayValue = array();
                foreach (get_object_vars($value) as $k => $v) {
                    $arrayValue[] = $v;
                }
                $data = array_merge($data, $this->_writeJsonProperty($stop_at_next, $stop, $key, $pluralKey, $arrayValue, $interpretsValueAsJsonArray));
            } elseif (get_class($value) == 'Zend_Date') {
                $v = $value->get(Zend_Date::ISO_8601);
                $data[$key] = $v;
            } elseif (!$stop && $value instanceof BBC_Serialiser_Serialisable) {
                $obj = $this->_writeJsonResource($value, $stop_at_next);
                $data = array_merge($data, array($key => $obj));
            }
        } elseif ($value !== '' && $value !== null) {
            $data[$key] = $value;
        }
        return $data;
    }
}
