<?php
/**
 * Serialiser.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser
 * @desc An interface for PHP objects serialisers.
 *
 * Right now, we have three implementing classes, serialising
 * PHP objects in XML, JSON and RDF.
 */
interface BBC_Serialiser_Interface
{
    /**
     * Serialises an object.
     *
     * A serialiser checks for a 'getFeedMapping' method on the object
     * it tries to serialise. This methods returns an associative
     * array detailing how the object should be mapped when including 
     * it in a feed. See BBC_Serialiser_Serialisable.
     *
     * @public
     * @param object $object
     * @return string
     */
    public function serialise($object);
}
