<?php
/**
 * RdfXmlSerialiser.php
 *
 * @copyright Copyright (c) BBC (http://www.bbc.co.uk)
 * @filesource
 */
/**
 * BBC_Serialiser_RdfXmlSerialiser
 * @desc A class serialising PHP objects in RDF/XML
 */
require_once 'EasyRdf/Serialiser/RdfXml.php';

class BBC_Serialiser_RdfXmlSerialiser extends BBC_Serialiser_EasyRdfSerialiser
{
    protected $_serialisation_format = 'rdfxml';
}
