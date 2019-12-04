<?php

namespace robyj\csv2meta\Objects;

/**
 * Class MetadataElement
 * @package robyj\csv2meta\Objects
 */
class MetadataElement
{
    private $field_name;

    private $twig_field;

    private $attributes = [];

    private $children = [];

    private $value;

    private $multiValued = false;

    private $hasMultipleValues = false;

    /**
     * MetadataElement constructor.
     * @param string $field_name
     *   Field name from Yaml configuration.
     */
    public function __construct($field_name)
    {
        $this->field_name = $field_name;
    }

    /**
     * Set the twig field name.
     *
     * @param string $field_name
     */
    public function setTwigField($field_name)
    {
        $this->twig_field = $field_name;
    }

    /**
     * Return field name.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->field_name;
    }

    /**
     * Add an attribute.
     *
     * @param string $key
     *   Attribute name
     * @param string $value
     *   Attribute value.
     */
    public function addAttribute($key, $value)
    {
        if (!array_key_exists($key, $this->attributes)) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Do we have any children?
     *
     * @return bool
     */
    public function hasChildren()
    {
        return (count($this->children) > 0);
    }

    /**
     * Add a child field.
     *
     * @param \robyj\csv2meta\Objects\MetadataElement $object
     */
    public function addChild($object)
    {
        $this->children[] = $object;
    }

    /**
     * Get all children.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Remove all children.
     */
    public function clearChildren()
    {
        $this->children = [];
    }

    /**
     * Set the value of the field.
     *
     * @param string $val
     */
    public function setValue($val)
    {
        $this->value = $val;
        $this->hasMultipleValues = (strpos($this->value, ';') !== false);
    }

    /**
     * Make this a multivalued field.
     */
    public function setMultiValued()
    {
        $this->multiValued = true;
    }

    /**
     * Can this have multiple values?
     *
     * @return bool
     */
    public function isMultiValued()
    {
        return $this->multiValued;
    }

    /**
     * Does this have multiple values?
     *
     * If this can't have multiple values but does have them. It "should" be managed by a multivalued parent.
     *
     * @return bool
     */
    public function hasMultipleValues()
    {
        return $this->hasMultipleValues;
    }

    /**
     * Clear the value.
     */
    public function clearValue()
    {
        unset($this->value);
    }

    /**
     * Get the data in a twig template ready format.
     *
     * @return array
     */
    public function getDataArray()
    {
        $data = [];
        $childData = [];
        $explodeForChild = false;
        if (count($this->children) > 0) {
            foreach($this->children as $child) {
                if (!$explodeForChild && !$child->isMultivalued() && $child->hasMultipleValues()) {
                    // Now we need to make a copy of ourself for each child
                    $explodeForChild = true;
                    // Break out as we need to rebuild $childData anyways.
                    break;
                }
                $childData = array_merge($childData, $child->getDataArray());
            }
        }
        if ((!isset($this->value) || empty($this->value)) && count($childData) == 0) {
            return $data;
        }
        if ($explodeForChild) {
            // This complex, we assume the current parent has a single value (or none) and we instead
            // Generate multiple values using the multivalued child and all single valued children. If you have
            // more than one multivalued child. It splits them all out. ie 2 children with 2 values each nets 4 values.
            // Maybe just throw an Exception instead.
            $normalKids = [];
            $multiKids = [];
            foreach ($this->children as $child) {
                if ($child->isMultivalued() || !$child->hasMultipleValues()) {
                    // Collect the normal children first.
                    $normalKids = array_merge($normalKids, $child->getDataArray());
                } else {
                    $multiKids = array_merge($multiKids, $child->getDataArray());
                }
            }
            $newChild = [];
            foreach ($multiKids as $key => $kid_values) {
                foreach ($kid_values as $kid_value) {
                    $newChild[] = array_merge([$key => $kid_value], $normalKids);
                }
            }
            $data[$this->twig_field] = [];
            foreach ($newChild as $child) {
                $data[$this->twig_field][] = ['value' => $this->value];
                $connect = &$data[$this->twig_field][count($data[$this->twig_field]) - 1];
                $connect = $this->addAttributes($connect);
                $connect = array_merge($connect, $child);
            }
        } elseif ($this->multiValued || $this->hasMultipleValues) {
            $values = explode(';', $this->value);
            $data[$this->twig_field] = [];
            foreach ($values as $value) {
                $data[$this->twig_field][] = ['value' => trim($value)];
                $connect = &$data[$this->twig_field][count($data[$this->twig_field]) - 1];
                $connect = $this->addAttributes($connect);
                $connect = $this->addChildren($connect, $childData);
            }
        } else {
            $data[$this->twig_field] = [
                'value' => $this->value,
            ];
            $connect = &$data[$this->twig_field];
            $connect = $this->addAttributes($connect);
            $connect = $this->addChildren($connect, $childData);
        }

        return $data;
    }

    /**
     * Do appending of common attributes for multiple nodes.
     *
     * @param array $parent
     *   The parent array.
     * @return array
     *   The altered parent array.
     */
    private function addAttributes(array $parent)
    {
        if (count($this->attributes) > 0) {
            foreach ($this->attributes as $key => $value) {
                $parent[$key] = $value;
            }
        }
        return $parent;
    }

    /**
     * Do appending of children for multiple values.
     *
     * @param array $parent
     *   The parent array.
     * @param array|null $childData
     *   The children data array.
     * @return array
     *   The altered parent array.
     */
    private function addChildren(array $parent, $childData)
    {
        if (is_array($childData) && count($childData) > 0) {
            foreach ($childData as $k => $v) {
                $parent[$k] = $v;
            }
        }
        return $parent;
    }
}
