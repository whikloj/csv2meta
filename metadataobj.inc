<?php

/**
 * @metadataobj.inc
 *
 * Class to convert a xpath(ish) path and value to a metadata field
 * Technically it could be any metadata format, its based on the YAML
 * glue file and the values used in the CSV file. Also makes use of 
 * a field attribute added to the element.
 */
class MetadataObject {

  public function render($mXML, $prefix, $root, $field, $value, $attrib, $valuedelimit ) {
    //get document root to start
    $parent = $mXML->documentElement;
    // now, we traverse the XML, scoping down the path for the field
    $fieldpath = explode("/",$field);
  
    // check the children of the parent node for a match with the current part of the field path
    $children = $parent->childNodes;
    $nsfield = $prefix.":".$fieldpath[0];
    $pathfound = false;
    for($count=0; $count < count($fieldpath); $count++) {
      $children = $parent->childNodes;
      $nsfield = $prefix.":".$fieldpath[$count];
      $pathfound = false;
      // check the children of the parent node for a match with the current part of the field path
      foreach ($children as $child) {
        if (!strcmp($child->tagName,$nsfield)) {
          //found match in tree
          $pathfound = true;
          $parent = $child;
          break;
        }
      }
      // if its the end node AND a node of the same name exists,
      // roll back the parent to the parents parent.
      if ($count == count($fieldpath)-1) {
        if ($pathfound == true) {
          if ($parent !== $mXML->documentElement)
            $parent = $parent->parentNode;
        }
        // if its multiple values, explode and add each one
        $values = explode($valuedelimit,$value);
        if (count($values) > 1) {
          foreach ($values as $tmpvalue) {
            $xmlfield = $mXML->createElement( $nsfield, $tmpvalue );
            $attribarr = explode("=",$attrib);
            $xmlfield->setAttribute($attribarr[0],$attribarr[1]);
            $parent->appendChild( $xmlfield );
          }
        }
        else {
          // single value, just add it
          $xmlfield = $mXML->createElement($nsfield, $value);
          $attribarr = explode("=",$attrib);
          $xmlfield->setAttribute($attribarr[0],$attribarr[1]);
          $parent->appendChild( $xmlfield );
        }
      }
      else {
        // not the end node. if the node doesnt exist, add it
        if ($pathfound == false) {
          // fragment is still on path
          $xmlfield = $mXML->createElement($nsfield);
          $parent = $parent->appendChild($xmlfield);
        }
      }
    }
  }
}

/**
 * @MODSMetadataObject
 *
 * child of the MetadataObject class, used to create MODS metadata
 */
class MODSMetadataObject extends MetadataObject {

  const mValueDelimiter = "--";

  function init($mXML) {
    // some settings that help with output formatting
    $mXML->preserveWhiteSpace = false;
    $mXML->formatOutput = true;
    //create root & ns attributes
    $root = $mXML->createElement("mods:mods");
    $root->setAttribute("xmlns:mods","http://www.loc.gov/mods/v3");
    $root->setAttribute("xmlns:xlink","http://www.w3.org/1999/xlink");
    $root->setAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
    $root->setAttribute("version","3.4");
    $root->setAttribute("xsi:schemaLocation",
                        "http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-0.xsd");
    $mXML->appendChild($root);
  }

  function render($xml, $field, $value, $attrib) {
    parent::render($xml, "mods", "mods", $field, $value, $attrib, self::mValueDelimiter);
  }
}

?>
