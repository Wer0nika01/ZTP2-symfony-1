<?php

/** * Tag fixtures. */

namespace App\DataFixtures;

use App\Entity\Tag;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\String\Slugger\SluggerInterface;

/** * Class TagFixtures. */
class TagFixtures extends AbstractBaseFixtures
{
    /** * Constructor.
     *
     * @param SluggerInterface $slugger */
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    /** * Load data. */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(20, 'tag', function () {
            $tag = new Tag();
            $tag->setName($this->faker->unique()->word);
            $tag->setSlug($this->slugger->slug($tag->getName())->lower());
            $tag->setCreatedAt(
                DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $tag->setUpdatedAt(
                DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );

            return $tag;
        });
        $this->manager->flush();
    }
}
