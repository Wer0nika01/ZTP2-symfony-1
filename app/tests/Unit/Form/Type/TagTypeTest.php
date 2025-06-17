<?php

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

        // Check if the form has the 'name' field
        $this->assertTrue($form->has('name'));

        // Get the 'name' field
        $nameField = $form->get('name');

        // Check if the 'name' field is of TextType
        $this->assertEquals(TextType::class, $nameField->getConfig()->getType()->getInnerType()::class);

        // Check if the 'name' field has the correct label
        $this->assertEquals('label.name', $nameField->getConfig()->getOption('label'));
    }

    /**
     * Test configureOptions.
     */
    public function testConfigureOptions(): void
    {
        // Create an instance of the form type
        $formType = new TagType();

        // Use a mock Tag entity for the data_class option
        $tag = new Tag();
        $form = $this->factory->create(TagType::class, $tag);

        // Assert that the form's data class is correctly configured
        $this->assertEquals(Tag::class, $form->getConfig()->getDataClass());
    }
}
