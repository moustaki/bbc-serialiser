<?php

class MockModel implements BBC_Serialiser_Serialisable
{
    protected $_feedMapping = array();

    public function setFeedMapping($feedMapping) {
        $this->_feedMapping = $feedMapping;
    }

    public function getFeedMapping() {
        return $this->_feedMapping;
    }
}
