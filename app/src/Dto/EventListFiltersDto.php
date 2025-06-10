<?php
/**
 * Event list filters DTO.
 */

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Enum\EventStatus;
use App\Entity\Tag;

/**
 * Class EventListFiltersDto.
 */
class EventListFiltersDto
{
    /**
     * Constructor.
     *
     * @param Category|null      $category   Category entity
     * @param Tag|null           $tag        Tag entity
     * @param EventStatus|null    $eventStatus Event status
     */
    public function __construct(public readonly ?Category $category, public readonly ?Tag $tag, public readonly ?EventStatus $eventStatus)
    {
    }
}