<?php
/**
 * Created by PhpStorm.
 * User: whikloj
 * Date: 2019-11-29
 * Time: 15:12
 */

namespace robyj\csv2meta\Objects;


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

    public function getDataArray()
    {
        $data = [];
        if (!isset($this->value) || empty($this->value)) {
            return $data;
        }
        if ($this->multiValued) {
            $data[$this->twig_field] = [
                [ 'value' => $this->value],
            ];
            $connect = &$data[$this->twig_field][0];
        } else {
            $data[$this->twig_field] = [
                'value' => $this->value,
            ];
            $connect = &$data[$this->twig_field];
        }

        if (count($this->attributes) > 0) {
            foreach ($this->attributes as $key => $value) {
                $connect[$key] = $value;
            }
        }
        if (count($this->children) > 0) {
            foreach($this->children as $child) {
                $childData = $child->getDataArray();
                $connect = array_merge($connect, $childData);
            }
        }
        return $data;
    }
}