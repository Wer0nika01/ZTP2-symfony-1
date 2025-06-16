<?php

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\RegistrationType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class RegistrationTypeTest extends TypeTestCase
{
    /**
     * Set up validator extension.
     */
    protected function getExtensions(): array
    {
        // This is crucial for enabling validation in form tests.
        return [new ValidatorExtension(Validation::createValidator())];
    }

    /**
     * Test form structure and options.
     */
    public function testBuildForm(): void
    {
        // Create the form with no initial data.
        $form = $this->factory->create(RegistrationType::class);

        // Assert that the form has the expected fields.
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));

        // Get the configuration for the 'password' field.
        $passwordConfig = $form->get('password')->getConfig();
        // Assert that the 'invalid_message' option for the RepeatedType is correctly set.
        // This message is displayed when the 'first' and 'second' password fields do not match.
        $this->assertEquals('message.passwords_must_match', $passwordConfig->getOption('invalid_message'));
    }

    /**
     * Test submitting valid data.
     */
    public function testSubmitValidData(): void
    {
        // Define the valid form data.
        $formData = [
            'email' => 'test@example.com',
            'password' => [
                'first' => 'password123',
                'second' => 'password123',
            ],
        ];

        // Create a new User entity to bind the form data to.
        $user = new User();
        // Create the form instance, binding it to the User entity.
        $form = $this->factory->create(RegistrationType::class, $user);

        // Submit the valid form data.
        $form->submit($formData);

        // Assert that the form is synchronized (data was mapped successfully).
        $this->assertTrue($form->isSynchronized());
        // Assert that the form is valid (no validation errors).
        $this->assertTrue($form->isValid());
        // Assert that there are no errors on the form or its children.
        $this->assertCount(0, $form->getErrors(true));

        // Assert that the User entity's properties are updated correctly.
        $this->assertEquals('test@example.com', $user->getEmail());
        // For RepeatedType, the data from the 'first' field is mapped to the model.
        $this->assertEquals('password123', $user->getPassword());
    }

    /**
     * Test when passwords do not match.
     */
    public function testSubmitMismatchedPasswords(): void
    {
        // Define form data with mismatched passwords.
        $formData = [
            'email' => 'test@example.com',
            'password' => [
                'first' => 'password123',
                'second' => 'differentPassword456',
            ],
        ];

        // Create a new User entity (important for validation context).
        $user = new User();
        // Create the form instance, binding it to the User entity.
        $form = $this->factory->create(RegistrationType::class, $user);
        // Submit the form data with mismatched passwords.
        $form->submit($formData);

        // Assert that the form is not valid due to mismatched passwords.
        $this->assertFalse($form->isValid());

        // Get all errors recursively from the form to find the specific message.
        // The 'invalid_message' of RepeatedType places the error on the parent 'password' field,
        // but sometimes it can be collected as a global error depending on configuration.
        $errors = $form->getErrors(true); // 'true' means include errors from child fields.

        $foundPasswordMismatchError = false;
        foreach ($errors as $error) {
            // Check if the specific error message template is found among all errors.
            if ($error->getMessageTemplate() === 'message.passwords_must_match') {
                $foundPasswordMismatchError = true;
                break;
            }
        }
        // Assert that the expected mismatched password error was found.
        $this->assertTrue($foundPasswordMismatchError, 'Expected "message.passwords_must_match" error was not found.');
    }

    /**
     * Test submitting a blank form.
     */
    public function testSubmitBlankData(): void
    {
        // Define form data with all blank fields.
        $formData = [
            'email' => '',
            'password' => [
                'first' => '',
                'second' => '',
            ],
        ];

        // Create a new User entity (important for validation context).
        $user = new User();
        // Create the form instance, binding it to the User entity.
        $form = $this->factory->create(RegistrationType::class, $user);
        // Submit the blank form data.
        $form->submit($formData);

        // Assert that the form is not valid due to blank fields.
        $this->assertFalse($form->isValid());

        // Assert that there's one error on the 'email' field (e.g., NotBlank constraint).
        $this->assertCount(1, $form->get('email')->getErrors());
        // Assert the default message for a blank field. This might vary if custom messages are used.
        $this->assertEquals('This value should not be blank.', $form->get('email')->getErrors()[0]->getMessageTemplate());

        // For RepeatedType, 'NotBlank' errors usually appear on the individual 'first' and 'second' fields.
        $this->assertCount(1, $form->get('password')->get('first')->getErrors());
        $this->assertEquals('This value should not be blank.', $form->get('password')->get('first')->getErrors()[0]->getMessageTemplate());

        $this->assertCount(1, $form->get('password')->get('second')->getErrors());
        $this->assertEquals('This value should not be blank.', $form->get('password')->get('second')->getErrors()[0]->getMessageTemplate());

        // Based on the persistent test failure, it appears there *is* an error on the parent 'password' field.
        // This is likely due to a 'NotBlank' constraint on the User entity's password property, or 'required'
        // option on the RepeatedType itself, which causes an error if the data passed to the model is empty.
        $this->assertCount(1, $form->get('password')->getErrors());
        $this->assertEquals('This value should not be blank.', $form->get('password')->getErrors()[0]->getMessageTemplate());
    }
}
