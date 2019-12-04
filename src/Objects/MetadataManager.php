<?php

namespace robyj\csv2meta\Objects;

/**
 * MetadataManager coordinates all the metadata fields.
 *
 * @package robyj\csv2meta\Objects
 */
class MetadataManager
{

    private $fields = [];

    /**
     * Add a field.
     *
     * @param string $name
     *   Field name.
     * @param \robyj\csv2meta\Objects\MetadataElement $object
     *   The field object.
     */
    public function addField($name, MetadataElement $object)
    {
        $this->fields[$name] = $object;
    }

    /**
     * List all field names.
     *
     * @return array
     */
    public function listFields()
    {
        return array_keys($this->fields);
    }

    /**
     * Does the field exist.
     *
     * @param string $name
     *   Name of the field.
     * @return bool
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }

    /**
     * Get a field.
     *
     * @param string $name
     *   The field name.
     * @return \robyj\csv2meta\Objects\MetadataElement|null
     *   The element or null if doesn't exist.
     */
    public function getField($name)
    {
        if (array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }
        return null;
    }

    /**
     * Get all the fields.
     *
     * @return array
     */
    public function getAllFields()
    {
        return $this->fields;
    }

    /**
     * Get all the metadata.
     *
     * @return array
     *   The metadata for all fields.
     */
    public function getMetadataArray()
    {
        $dataArray = [];
        foreach ($this->fields as $field_name => &$field) {
            $this->resolveChildren($field);
            $dataArray = array_merge_recursive($dataArray, $field->getDataArray());
        }
        return $dataArray;
    }

    /**
     * Update children to ensure we have the up-to-date value before serializing.
     * @param \robyj\csv2meta\Objects\MetadataElement $object
     *   The element to check for children.
     */
    private function resolveChildren(&$object)
    {
        if ($object->hasChildren()) {
            $newChildren = [];
            foreach ($object->getChildren() as $child) {
                if (isset($this->fields[$child->getFieldName()])) {
                    $newChildren[] = $this->fields[$child->getFieldName()];
                } else {
                    $newChildren[] = $child;
                }
            }
            $object->clearChildren();
            foreach ($newChildren as $new_child) {
                $object->addChild($new_child);
            }
        }
    }
}