<?php

/**
 * Event list filters DTO.
 */

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;
use Doctrine\Common\Collections\Collection;

/**
 * Class EventListFiltersDto.
 */
class EventListFiltersDto
{
    /**
     * Constructor.
     *
     * @param Collection       $tags     Tags collection
     * @param Category|null    $category Category entity
     * @param EventStatus|null $status   Status entity
     */
    public function __construct(private Collection $tags, private ?Category $category = null, private ?EventStatus $status = null)
    {
    }

    /**
     * Setter for Category.
     *
     * @param Category|null $category Category entity
     *
     * @return $this
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Getter for Category.
     *
     * @return Category|null Category entity
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Setter for status.
     *
     * @param EventStatus|null $status status entity
     *
     * @return $this
     */
    public function setStatus(?EventStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Getter for Event Status.
     *
     * @return EventStatus|null Event status
     */
    public function getStatus(): ?EventStatus
    {
        return $this->status;
    }

    /**
     * Getter for tags.
     *
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Setter for tags.
     *
     * @param Collection<int, Tag> $tags
     *
     * @return $this
     */
    public function setTags(Collection $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Add tag to collection.
     *
     * @param Tag $tag Tag entity
     *
     * @return $this
     */
    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    /**
     * Remove tag from collection.
     *
     * @param Tag $tag Tag entity
     *
     * @return $this
     */
    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
