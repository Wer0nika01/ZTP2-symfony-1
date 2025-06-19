<?php

/** * Contact fixtures. */

namespace App\DataFixtures;

use App\Entity\Contact;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

/** * Class ContactFixtures.
 *
 * @psalm-suppress MissingConstructor */
class ContactFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /** * Load data. */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(50, 'contact', function () {
            $contact = new Contact();
            $contact->setFirstName($this->faker->firstName);
            $contact->setLastName($this->faker->lastName);

            if ($this->faker->boolean(80)) {
                $contact->setEmail($this->faker->unique()->safeEmail);
            }

            if ($this->faker->boolean(70)) {
                $contact->setPhone($this->faker->e164PhoneNumber);
            }

            if ($this->faker->boolean(60)) {
                $contact->setAddress($this->faker->address);
            }

            if ($this->faker->boolean()) {
                $contact->setCompany($this->faker->company);
            }

            if ($this->faker->boolean(40)) {
                $contact->setJobTitle($this->faker->jobTitle);
            }

            if ($this->faker->boolean(30)) {
                $contact->setNotes($this->faker->realText(mt_rand(100, 500)));
            }

            /** @var User $author */
            $author = $this->getRandomReference('user', User::class);
            $contact->setAuthor($author);

            $tagsCount = mt_rand(0, 4);
            for ($j = 0; $j < $tagsCount; ++$j) {
                /** @var Tag $tag */
                $tag = $this->getRandomReference('tag', Tag::class);
                $contact->addTag($tag);
            }

            return $contact;
        });

        $this->manager->flush();
    }

    /** * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies */
    public function getDependencies(): array
    {
        return [UserFixtures::class, TagFixtures::class];
    }
}
