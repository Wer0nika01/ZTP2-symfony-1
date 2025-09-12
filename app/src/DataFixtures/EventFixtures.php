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

        $this->createMany(100, 'event', function () {
            $event = new Event();
            $event->setTitle($this->faker->sentence(mt_rand(2, 5)));

            $event->setDescription($this->faker->realText(mt_rand(200, 1000)));

            $startDateTimeString = $this->faker->dateTimeBetween('now', '+90 days')->format('Y-m-d H:i:s');
            $event->setStartTime(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startDateTimeString));

            if ($this->faker->boolean(70)) {
                $tempStart = \DateTime::createFromFormat('Y-m-d H:i:s', $startDateTimeString);
                $endDateTimeString = $this->faker->dateTimeBetween($tempStart, $tempStart->modify('+1 day'))->format('Y-m-d H:i:s');
                $event->setEndTime(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $endDateTimeString));
            }

            if ($this->faker->boolean(60)) {
                $event->setLocation($this->faker->address);
            }

            $event->setIsAllDay($this->faker->boolean(20));

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

            $statusValue = $this->faker->numberBetween(1, 3);
            $event->setStatus(EventStatus::from($statusValue));

            return $event;
        });

        $this->manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: CategoryFixtures::class, 1: TagFixtures::class, 2: UserFixtures::class}
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, TagFixtures::class, UserFixtures::class];
    }
}
