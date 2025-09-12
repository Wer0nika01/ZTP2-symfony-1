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
    public function __construct(private Collection $tags, private ?Category $category = null, private ?EventStatus $status = null)
    {
    }

    /**
     * Setter for Category.
     *
     * @return $this
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Setter for Status.
     *
     * @return $this
     */
    public function setStatus(?EventStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?EventStatus
    {
        return $this->status;
    }

    /**
     * Getter for Tags.
     *
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Setter for Tags.
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
     * Add tags.
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
     * Delete tags.
     *
     * @return $this
     */
    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
