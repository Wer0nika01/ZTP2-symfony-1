<?php

/**
 * Event fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

/**
 * Class EventFixtures.
 *
 * @psalm-suppress MissingConstructor
 */
class EventFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress UnusedClosureParam
     */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(100, 'event', function (int $i) {
            $event = new Event();
            $event->setTitle($this->faker->sentence);
            $event->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $event->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $event->setComment($this->faker->realText(1024));
            $category = $this->getRandomReference('category', Category::class);
            $event->setCategory($category);

            /** @var Tag[] $tags */
            $tags = $this->getRandomReferenceList(
                'tag',
                Tag::class,
                $this->faker->numberBetween(0, 5)
            );
            foreach ($tags as $tag) {
                $event->addTag($tag);
            }

            /** @var User $author */
            $author = $this->getRandomReference('user', User::class);
            $event->setAuthor($author);

            /** @var int $statusValue */
            $statusValue = $this->faker->numberBetween(1, 4); // Assuming your enum values are 1, 2, 3, 4
            $event->setStatus(EventStatus::from($statusValue));

            return $event;
        });
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: CategoryFixtures::class}
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, TagFixtures::class, UserFixtures::class];
    }
}
