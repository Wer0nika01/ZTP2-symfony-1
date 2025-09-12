<?php

/**
 * Event entity.
 */

namespace App\Entity;

use App\Entity\Enum\EventStatus;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Event.
 */
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    /**
     * Primary key.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Title.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    /**
     * Description of the event.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'event.description.length_max'
    )]
    private ?string $description = null;

    /**
     * Start time of the event.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startTime = null;

    /**
     * End time of the event.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    /**
     * Location of the event.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'event.location.length_max'
    )]
    private ?string $location = null;

    /**
     * Is this an all-day event?
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAllDay = false;

    /**
     * Category.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * Tags.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, fetch: 'EXTRA_LAZY')]
    #[ORM\InverseJoinColumn(nullable: true)]
    #[ORM\JoinTable(name: 'events_tags')]
    private Collection $tags;

    /**
     * Author.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    /**
     * Status.
     */
    #[ORM\Column(type: Types::INTEGER, enumType: EventStatus::class)]
    private EventStatus $status;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * Getter for id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Getter for title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Setter for title.
     *
     * @param string|null $title Title
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Getter for description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Setter for description.
     *
     * @param string|null $description Description of the event
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Getter for start time.
     */
    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    /**
     * Setter for start time.
     *
     * @param \DateTimeImmutable|null $startTime Start time of the event
     */
    public function setStartTime(?\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Getter for end time.
     */
    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    /**
     * Setter for end time.
     *
     * @param \DateTimeImmutable|null $endTime End time of the event
     */
    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Getter for location.
     *
     * @return string|null Location
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Setter for location.
     *
     * @param string|null $location Location
     */
    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Getter for isAllDay.
     *
     * @return bool Is all day
     */
    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    /**
     * Setter for isAllDay.
     *
     * @param bool $isAllDay Is all day
     */
    public function setIsAllDay(bool $isAllDay): static
    {
        $this->isAllDay = $isAllDay;

        return $this;
    }

    /**
     * Getter for category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Setter for category.
     *
     * @param Category|null $category Category
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
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
     * Add tag.
     *
     * @param Tag $tag Tag to add
     */
    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    /**
     * Remove tag.
     *
     * @param Tag $tag Tag to remove
     */
    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * Getter for author.
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Setter for author.
     *
     * @param User|null $author Author
     */
    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Getter for status.
     */
    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    /**
     * Setter for status.
     *
     * @param EventStatus $status Status
     */
    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
