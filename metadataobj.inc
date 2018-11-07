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
    
    // retrieve a list of existing elements in the DOM
    $nodelist = $mXML->getElementsByTagName($prefix.":".$root);
    if ($nodelist->length > 0) {
      $parent = $nodelist->item(0);
      // explode the path before we traverse it
      $fieldpath = explode( "/",$field );
      // if the attribute exists, explode that into an array
      $attribarr = None;
      if (strlen($attrib) > 0 )
        $attribarr = explode("=",$attrib);
      for($count=0; $count < count($fieldpath); $count++) {
        $tmpfield = $fieldpath[$count];
        $nsfield = $prefix.":".$tmpfield;
        $node = $mXML->getElementsByTagName($nsfield);
        if ($node->length == 0) {
          // last token in the path?
          if ($count < count($fieldpath)-1) {
            $xmlfield = $mXML->createElement($nsfield);
            $parent = $parent->appendChild( $xmlfield );
          }
          else {
            /* check if the value is actually more than one value, explode
               and append each value
            */
            $values = explode($valuedelimit,$value);
            for ($valcnt=0; $valcnt < count($values); $valcnt++) {
              $tmpvalue = $values[$valcnt];
              $xmlfield = $mXML->createElement( $nsfield, $tmpvalue );
              if (count($attribarr) > 1)
                $xmlfield->setAttribute($attribarr[0],$attribarr[1]);
              $parent->appendChild( $xmlfield );
            }
          }
        }
        else {
          // last item in the path?
          if ($count < count($fieldpath)-1) {
            $parent = $node->item(0);
          }
          else {
            // end of the branch, so append the values together
            // check if its actually multiple values, explode if true 
            $values = explode($valuedelimit,$value);
            foreach ($values as $tmpvalue) {
              $xmlfield = $mXML->createElement( $nsfield, $tmpvalue );
              if (count($attribarr) > 1)
                $xmlfield->setAttribute($attribarr[0],$atttribarr[1]);
              $parent->appendChild( $xmlfield );
            }
          }
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
