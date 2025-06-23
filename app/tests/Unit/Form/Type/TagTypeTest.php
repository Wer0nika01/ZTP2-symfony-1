<?php

/**
 * Tag type test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\Tag;
use App\Form\Type\TagType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Class TagTypeTest.
 */
class TagTypeTest extends TypeTestCase
{
    /**
     * Test buildForm.
     */
    public function testBuildForm(): void
    {
        $form = $this->factory->create(TagType::class);

        $this->assertTrue($form->has('name'));

        $nameField = $form->get('name');

        $this->assertEquals(TextType::class, $nameField->getConfig()->getType()->getInnerType()::class);

        $this->assertEquals('label.name', $nameField->getConfig()->getOption('label'));
    }

    /**
     * Test configureOptions.
     */
    public function testConfigureOptions(): void
    {

        $tag = new Tag();
        $form = $this->factory->create(TagType::class, $tag);

        $this->assertEquals(Tag::class, $form->getConfig()->getDataClass());
    }
}
