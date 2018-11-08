<?php

namespace Mtc\Import\Drivers;

/**
 * Class Xml
 * Engine that allows importing data from a xml file
 *
 * @package Mtc\Import
 */
class Xml extends Json
{
    /**
     * Xml constructor.
     *
     * @param string $data_file_path
     * @throws \Exception
     */
    public function __construct($data_file_path = '')
    {
        $this->loadDataFromFile($data_file_path);
        $this->data = simplexml_load_string($this->removeNamespaces($this->raw_import_data), 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->data = json_decode(json_encode($this->data));
    }

    /**
     * FUNCTION TO MUNG THE XML SO WE
     * DO NOT HAVE TO DEAL WITH NAMESPACE
     *
     * @param string $xml
     * @return string
     */
    public function removeNamespaces($xml)
    {
        $object = SimpleXML_Load_String($xml);
        if ($object === FALSE) {
            return $xml;
        }

        // GET NAMESPACES, IF ANY
        $namespaces = $object->getNamespaces(TRUE);
        if (empty($namespaces)) {
            return $xml;
        }

        // CHANGE ns: INTO ns_
        $namespace_keys = array_keys($namespaces);
        foreach ($namespace_keys as $key) {
            // A REGULAR EXPRESSION TO MUNG THE XML
            $regex
                = '#'               // REGEX DELIMITER
                . '('               // GROUP PATTERN 1
                . '\<'              // LOCATE A LEFT WICKET
                . '/?'              // MAYBE FOLLOWED BY A SLASH
                . preg_quote($key)  // THE NAMESPACE
                . ')'               // END GROUP PATTERN
                . '('               // GROUP PATTERN 2
                . ':{1}'            // A COLON (EXACTLY ONE)
                . ')'               // END GROUP PATTERN
                . '#'               // REGEX DELIMITER
            ;
            // INSERT THE UNDERSCORE INTO THE TAG NAME
            $replacement
                = '$1'          // BACKREFERENCE TO GROUP 1
                . '_'           // LITERAL UNDERSCORE IN PLACE OF GROUP 2
            ;
            // PERFORM THE REPLACEMENT
            $xml = preg_replace($regex, $replacement, $xml);
        }

        return $xml;
    }
}
