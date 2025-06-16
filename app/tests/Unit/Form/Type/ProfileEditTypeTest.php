<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\ProfileEditType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class ProfileEditTypeTest extends TypeTestCase
{
    /**
     * Set up validator extension.
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();
        return [new ValidatorExtension($validator)];
    }

    /**
     * Test that the form is built with the correct fields and options.
     */
    public function testBuildForm(): void
    {
        $form = $this->factory->create(ProfileEditType::class);

        // Assert that all fields exist
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));

        // Check email field configuration
        $emailConfig = $form->get('email')->getConfig();
        $this->assertEquals('label.email', $emailConfig->getOption('label'));
        $this->assertTrue($emailConfig->getOption('required'));
        $this->assertCount(2, $emailConfig->getOption('constraints'));
        $this->assertInstanceOf(NotBlank::class, $emailConfig->getOption('constraints')[0]);
        $this->assertInstanceOf(Email::class, $emailConfig->getOption('constraints')[1]);

        // Check firstName field configuration
        $firstNameConfig = $form->get('firstName')->getConfig();
        $this->assertEquals('label.first_name', $firstNameConfig->getOption('label'));
        $this->assertFalse($firstNameConfig->getOption('required'));
        $this->assertCount(1, $firstNameConfig->getOption('constraints'));
        $this->assertInstanceOf(Length::class, $firstNameConfig->getOption('constraints')[0]);
    }

    /**
     * Test form submission with valid data.
     */
    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // The form expects a User object as its initial data
        $model = new User();
        $form = $this->factory->create(ProfileEditType::class, $model);

        $form->submit($formData);

        // Assert the form is synchronized and valid
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));

        // Assert the data was correctly mapped to the User object
        $this->assertEquals('test@example.com', $model->getEmail());
        $this->assertEquals('John', $model->getFirstName());
        $this->assertEquals('Doe', $model->getLastName());
    }

    /**
     * Test form submission with an invalid email address.
     */
    public function testSubmitInvalidEmail(): void
    {
        $formData = [
            'email' => 'not-a-valid-email', // Invalid email format
            'firstName' => 'Jane',
            'lastName' => 'Doe',
        ];

        $form = $this->factory->create(ProfileEditType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('email')->getErrors());
        $this->assertEquals('message.invalid_email', $form->get('email')->getErrors()[0]->getMessageTemplate());
    }

    /**
     * Test form submission with a blank required field (email).
     */
    public function testSubmitBlankEmail(): void
    {
        $formData = ['email' => ''];

        $form = $this->factory->create(ProfileEditType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('email')->getErrors());
        $this->assertEquals('message.email_not_blank', $form->get('email')->getErrors()[0]->getMessageTemplate());
    }

    /**
     * Test form submission with a name that is too long.
     */
    public function testSubmitFirstNameTooLong(): void
    {
        $longString = str_repeat('a', 256); // Exceeds max length of 255
        $formData = [
            'email' => 'test@example.com',
            'firstName' => $longString,
        ];

        $form = $this->factory->create(ProfileEditType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('firstName')->getErrors());
    }
}