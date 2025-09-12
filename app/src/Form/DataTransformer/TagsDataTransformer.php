<?php

/**
 * Tags data transformer.
 */

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Service\TagServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class TagsDataTransformer.
 *
 * @implements DataTransformerInterface<mixed, mixed>
 */
class TagsDataTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     *
     * @param TagServiceInterface $tagService Tag service
     */
    public function __construct(private readonly TagServiceInterface $tagService)
    {
    }

    /**
     * Transforms an array of tags to a string of tag names.
     *
     * @param Collection<int, Tag> $value Tags entity collection
     *
     * @return string Result
     */
    public function transform(mixed $value): string
    {
        if (!$value instanceof Collection || $value->isEmpty()) {
            return '';
        }

        $tagNames = [];

        foreach ($value as $tag) {
            $tagNames[] = $tag->getName();
        }

        return implode(', ', $tagNames);
    }

    /**
     * Transforms a string of tag names into an array of Tag entities.
     *
     * @param mixed $value String of tag names
     *
     * @return Collection<int, Tag> Result
     */
    public function reverseTransform(mixed $value): Collection
    {
        if (null === $value || '' === $value) {
            /* @var ArrayCollection<int, Tag> */
            return new ArrayCollection();
        }

        $tagNames = explode(',', (string) $value);

        /* @var ArrayCollection<int, Tag> $tags */
        $tags = new ArrayCollection();

        foreach ($tagNames as $tagName) {
            $trimmedTagName = trim($tagName);
            if ('' !== $trimmedTagName) {
                $tag = $this->tagService->findOneByName(strtolower($trimmedTagName));
                if (!$tag instanceof Tag) {
                    $tag = new Tag();
                    $tag->setName($trimmedTagName);

                    $this->tagService->save($tag);
                }
                $tags->add($tag);
            }
        }

        return $tags;
    }
}
