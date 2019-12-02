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

    public function __construct($field_name)
    {
        $this->field_name = $field_name;
    }

    public function setTwigField($field_name)
    {
        $this->twig_field = $field_name;
    }

    public function addAttribute($key, $value)
    {
        if (!array_key_exists($key, $this->attributes)) {
            $this->attributes[$key] = $value;
        }
    }

    public function addChild($object)
    {
        $this->children[] = $object;
    }

    public function setValue($val)
    {
        $this->value = $val;
    }

    public function setMultiValued()
    {
        $this->multiValued = true;
    }

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
        if (count($this->children) > 0) {
            foreach($this->children as $child) {
                $childData = array_merge($childData, $child->getDataArray());
            }
        }
        if ((!isset($this->value) || empty($this->value)) && count($childData) == 0) {
            return $data;
        }
        if ($this->multiValued && strpos($this->value, ';') !== false) {
            $values = explode(';', $this->value);
            $data[$this->twig_field] = [];
            foreach ($values as $value) {
                $data[$this->twig_field][] = ['value' => $value];
                $connect = &$data[$this->twig_field][count($data[$this->twig_field])-1];
                $connect = $this->addAttribChildren($connect, $childData);
            }
        } else {
            # Both of these are only run once, so they can share the addAttribChildren call.
            if ($this->multiValued) {
                $data[$this->twig_field] = [
                    ['value' => $this->value],
                ];
                $connect = &$data[$this->twig_field][0];
            } else {
                $data[$this->twig_field] = [
                    'value' => $this->value,
                ];
                $connect = &$data[$this->twig_field];
            }
            $connect = $this->addAttribChildren($connect, $childData);
        }

        return $data;
    }

    /**
     * Do appending of common attributes and children for multiple nodes.
     * @param array $parent
     *   The parent array.
     * @param array|null $childData
     *   The children data array.
     * @return array
     *   The altered parent array.
     */
    private function addAttribChildren(array $parent, $childData)
    {
        if (count($this->attributes) > 0) {
            foreach ($this->attributes as $key => $value) {
                $parent[$key] = $value;
            }
        }
        if (is_array($childData) && count($childData) > 0) {
            foreach ($childData as $k => $v) {
                $parent[$k] = $v;
            }
            #$parent = array_merge_recursive($parent, $childData);
        }
        return $parent;
    }
}
