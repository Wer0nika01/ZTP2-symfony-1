<?php

/**
 * Tag fixtures
 */

namespace App\DataFixtures;

use App\Entity\Tag;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class EventFixtures.
 *
 * @psalm-suppress MissingConstructor
 */

class TagFixtures extends AbstractBaseFixtures
{
    /**
     * Constructor
     *
     * @param SluggerInterface $slugger
     */
    public function __construct(private readonly SluggerInterface $slugger) {}

    /**
     * Load data
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

        $this->createMany(20, 'tag', function (int $i) {
            $tag = new Tag();
            $tag->setTitle($this->faker->unique()->word);
            $tag->setSlug($this->slugger->slug($tag->getTitle())->lower());
            $tag->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $tag->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );

            return $tag;
        });
    }
}
