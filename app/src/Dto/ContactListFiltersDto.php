<?php

/**
 * Contact list filters DTO.
 */

namespace App\Dto;

use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Class ContactListFiltersDto.
 */
class ContactListFiltersDto
{
    /**
     * Constructor.
     *
     * @param Collection $tags
     */
    public function __construct(private Collection $tags = new ArrayCollection())
    {
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
     * @param Collection $tags
     *
     * @return ContactListFiltersDto
     */
    public function setTags(Collection $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Add tag.
     *
     * @param Tag $tag
     *
     * @return ContactListFiltersDto
     */
    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    /**
     * Delete tag.
     *
     * @param Tag $tag
     *
     * @return ContactListFiltersDto
     */
    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
