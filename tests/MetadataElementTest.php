<?php

namespace robyj\csv2meta\Tests\Objects;

use PHPUnit\Framework\TestCase;
use robyj\csv2meta\Objects\MetadataElement;

class MetadataElementTest extends TestCase
{

    public function testSingleValueElements()
    {
        $obj = new MetadataElement('test_field');
        $obj->setTwigField('twig_field');
        $obj->setValue('example value');

        $this->assertEquals('test_field', $obj->getFieldName());
        $this->assertFalse($obj->hasChildren());
        $this->assertFalse($obj->hasMultipleValues());
        $data = $obj->getDataArray();
        $expected = [
            'twig_field' => [
                'value' => 'example value',
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testSingleValueWithMultiple()
    {
        $obj = new MetadataElement('test_field');
        $obj->setTwigField('twig_field');
        $obj->setValue('example value;and another');

        $this->assertEquals('test_field', $obj->getFieldName());
        $this->assertFalse($obj->hasChildren());
        $this->assertTrue($obj->hasMultipleValues());
        $data = $obj->getDataArray();
        $expected = [
            'twig_field' => [
                ['value' => 'example value'],
                ['value' => 'and another'],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testSingleMultiValueElement()
    {
        $obj = new MetadataElement('test_field');
        $obj->setTwigField('twig_field');
        $obj->setValue('example value');
        $obj->setMultiValued();

        $this->assertEquals('test_field', $obj->getFieldName());
        $this->assertFalse($obj->hasChildren());
        $this->assertFalse($obj->hasMultipleValues());
        $data = $obj->getDataArray();
        $expected = [
            'twig_field' => [
                ['value' => 'example value'],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testMultipleValueWithMultiple()
    {
        $obj = new MetadataElement('test_field');
        $obj->setTwigField('twig_field');
        $obj->setValue('example value;and another');
        $obj->setMultiValued();

        $this->assertEquals('test_field', $obj->getFieldName());
        $this->assertFalse($obj->hasChildren());
        $this->assertTrue($obj->hasMultipleValues());
        $data = $obj->getDataArray();
        $expected = [
            'twig_field' => [
                ['value' => 'example value'],
                ['value' => 'and another'],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testSingleWithSingleChild()
    {
        $child = new MetadataElement('test_field');
        $child->setTwigField('twig_field');
        $child->setValue('example value');

        $obj2 = new MetadataElement('test_parent');
        $obj2->setTwigField('parent_twig');
        $obj2->setValue("Mom knows best");
        $obj2->addChild($child);

        $this->assertEquals('test_parent', $obj2->getFieldName());
        $this->assertTrue($obj2->hasChildren());
        $this->assertFalse($obj2->hasMultipleValues());
        $data = $obj2->getDataArray();
        $expected = [
            'parent_twig' => [
                'value' => 'Mom knows best',
                'twig_field' => [
                    'value' => 'example value',
                ],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testSingleWithMultiChild()
    {
        $child = new MetadataElement('test_field');
        $child->setTwigField('twig_field');
        $child->setValue('example value');
        $child->setMultiValued();

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->addChild($child);

        $this->assertEquals('test_parent', $parent->getFieldName());
        $this->assertTrue($parent->hasChildren());
        $this->assertFalse($parent->hasMultipleValues());
        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                'value' => 'Mom knows best',
                'twig_field' => [
                    ['value' => 'example value'],
                ],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testMultipleWithSingleChild()
    {
        $child = new MetadataElement('test_field');
        $child->setTwigField('twig_field');
        $child->setValue('example value');

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->setMultiValued();
        $parent->addChild($child);

        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                [
                    'value' => 'Mom knows best',
                    'twig_field' => [
                        'value' => 'example value',
                    ],
                ],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    public function testMultipleWithMultipleChildren()
    {
        $child = new MetadataElement('test_field');
        $child->setTwigField('twig_field');
        $child->setValue('example value');
        $child->setMultiValued();

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->setMultiValued();
        $parent->addChild($child);

        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                [
                    'value' => 'Mom knows best',
                    'twig_field' => [
                        ['value' => 'example value'],
                    ],
                ],
            ]
        ];
        $this->assertArrayEquals($expected, $data);
    }

    /**
     * For instance multiple Cities in a hierarchicalGeographic.
     */
    public function testMultipleWithSingleMultiValued()
    {
        $child = new MetadataElement('test_field');
        $child->setTwigField('twig_field');
        $child->setValue('example value;and another');

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->setMultiValued();
        $parent->addChild($child);

        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                [
                    'value' => 'Mom knows best',
                    'twig_field' => [
                        'value' => 'example value',
                    ],
                ],
                [
                    'value' => 'Mom knows best',
                    'twig_field' => [
                        'value' => 'and another',
                    ],
                ],
            ],
        ];
        $this->assertArrayEquals($expected, $data);
    }

    /**
     * For instance multiple Cities and a Province in a hierarchicalGeographic.
     */
    public function testMultipleWithSingleMultiValued2()
    {
        $child = new MetadataElement('multi_test_child');
        $child->setTwigField('multi_value_child');
        $child->setValue('example value;and another');

        $child2 = new MetadataElement('other_test_child');
        $child2->setTwigField('single_value_child');
        $child2->setValue('standard value');

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->setMultiValued();
        $parent->addChild($child);
        $parent->addChild($child2);

        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                [
                    'value' => 'Mom knows best',
                    'multi_value_child' => [
                        'value' => 'example value',
                    ],
                    'single_value_child' => [
                        'value' => 'standard value',
                    ],
                ],
                [
                    'value' => 'Mom knows best',
                    'multi_value_child' => [
                        'value' => 'and another',
                    ],
                    'single_value_child' => [
                        'value' => 'standard value',
                    ],
                ],
            ],
        ];
        $this->assertArrayEquals($expected, $data);
    }

    /**
     * This is the unique case of multiple multi-valued children. Possibly should throw an Exception instead of
     * this processing.
     */
    public function testMultipleWithMultipleMultiValued2()
    {
        $child = new MetadataElement('multi_test_child');
        $child->setTwigField('multi_value_child');
        $child->setValue('example value;and another');

        $child2 = new MetadataElement('other_test_child');
        $child2->setTwigField('multi2_value_child');
        $child2->setValue('standard value;more standards');

        $parent = new MetadataElement('test_parent');
        $parent->setTwigField('parent_twig');
        $parent->setValue("Mom knows best");
        $parent->setMultiValued();
        $parent->addChild($child);
        $parent->addChild($child2);

        $data = $parent->getDataArray();
        $expected = [
            'parent_twig' => [
                [
                    'value' => 'Mom knows best',
                    'multi_value_child' => [
                        'value' => 'example value',
                    ],
                ],
                [
                    'value' => 'Mom knows best',
                    'multi_value_child' => [
                        'value' => 'and another',
                    ],
                ],
                [
                    'value' => 'Mom knows best',
                    'multi2_value_child' => [
                        'value' => 'standard value',
                    ],
                ],
                [
                    'value' => 'Mom knows best',
                    'multi2_value_child' => [
                        'value' => 'more standards',
                    ],
                ],
            ],
        ];
        $this->assertArrayEquals($expected, $data);
    }

    private function assertArrayEquals(array $expected, array $testing)
    {
        $this->assertCount(count($expected), $testing);
        $this->assertEquals(0, strcmp(serialize($expected), serialize($testing)));
    }
}