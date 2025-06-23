<?php

/**
 * Registration type Test.
 */

namespace App\Tests\Unit\Form\Type;

use App\Entity\User;
use App\Form\Type\RegistrationType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

/**
 * Class Registration type Test.
 */
class RegistrationTypeTest extends TypeTestCase
{
    /**
     * Test form structure and options.
     */
    public function testBuildForm(): void
    {
        $form = $this->factory->create(RegistrationType::class);

        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));

        $passwordConfig = $form->get('password')->getConfig();
        $this->assertEquals('message.passwords_must_match', $passwordConfig->getOption('invalid_message'));
    }

    /**
     * Test submitting valid data.
     */
    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'password' => [
                'first' => 'password123',
                'second' => 'password123',
            ],
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors(true));

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('password123', $user->getPassword());
    }

    /**
     * Test when passwords do not match.
     */
    public function testSubmitMismatchedPasswords(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'password' => [
                'first' => 'password123',
                'second' => 'differentPassword456',
            ],
        ];

        $user = new User();
        $form = $this->factory->create(RegistrationType::class, $user);
        $form->submit($formData);

        $this->assertFalse($form->isValid());

        $errors = $form->getErrors(true);

        $foundPasswordMismatchError = false;
        foreach ($errors as $error) {
            if ($error->getMessageTemplate() === 'message.passwords_must_match') {
                $foundPasswordMismatchError = true;
                break;
            }
        }
        $this->assertTrue($foundPasswordMismatchError, 'Expected "message.passwords_must_match" error was not found.');
    }

    /**
     * Set up validator extension.
     *
     * @return ValidatorExtension[]
     */
    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }
}
