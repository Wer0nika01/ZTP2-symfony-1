<?php

/**
 * Change password type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Form\Type\ChangePasswordType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

/**
 * Class Change password type Test.
 */
class ChangePasswordTypeTest extends TypeTestCase
{
    /**
     * Tests that the form is built with the correct fields and options.
     */
    public function testFormFieldsAndOptions(): void
    {
        $form = $this->factory->create(ChangePasswordType::class);

        $this->assertTrue($form->has('plainPassword'));

        $fieldConfig = $form->get('plainPassword')->getConfig();
        $options = $fieldConfig->getOptions();

        $this->assertEquals('label.new_password', $options['label']);
        $this->assertFalse($options['mapped'], 'The "mapped" option should be false.');
        $this->assertEquals(['autocomplete' => 'new-password'], $options['attr']);

        $constraints = $options['constraints'];
        $this->assertCount(2, $constraints, 'There should be exactly two constraints.');

        $this->assertInstanceOf(NotBlank::class, $constraints[0]);
        $this->assertEquals('message.enter_password', $constraints[0]->message);

        $this->assertInstanceOf(Length::class, $constraints[1]);
        $this->assertEquals(6, $constraints[1]->min);
        $this->assertEquals('message.password_too_short', $constraints[1]->minMessage);
        $this->assertEquals(4096, $constraints[1]->max);
    }

    /**
     * Tests the form with valid submitted data.
     */
    public function testSubmitValidData(): void
    {
        $formData = [
            'plainPassword' => 'a-secure-and-valid-password',
        ];

        $form = $this->factory->create(ChangePasswordType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertCount(0, $form->getErrors(true));
    }

    /**
     * Tests the form when the submitted password is too short.
     */
    public function testSubmitPasswordTooShort(): void
    {
        $formData = [
            'plainPassword' => 'short',
        ];

        $form = $this->factory->create(ChangePasswordType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid(), 'Form should be invalid when password is too short.');

        $errors = $form->get('plainPassword')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('message.password_too_short', $errors[0]->getMessageTemplate());
    }

    /**
     * Tests the form when the submitted password is blank.
     */
    public function testSubmitPasswordIsBlank(): void
    {
        $formData = [
            'plainPassword' => '',
        ];

        $form = $this->factory->create(ChangePasswordType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid(), 'Form should be invalid when password is blank.');

        $errors = $form->get('plainPassword')->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('message.enter_password', $errors[0]->getMessageTemplate());
    }

    /**
     *  Provides the necessary extensions for the form test case.
     *  The ValidatorExtension is required to test forms with validation constraints.
     *
     * @return ValidatorExtension[]
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }
}
