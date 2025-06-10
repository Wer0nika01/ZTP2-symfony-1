<?php

namespace App\Entity\Enum;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Enum TaskStatus.
 *
 * Represents the possible statuses for a task.
 * This is a Backed Enum with integer values.
 */
enum TaskStatus: int
{
    case NEW = 1;
    case ACTIVE = 2;
    case DONE = 3;
    case CANCELED = 4;

    /**
     * Get the human-readable label for the enum case.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::NEW => 'label.new',
            self::ACTIVE => 'label.active',
            self::DONE => 'label.done',
            self::CANCELED => 'label.canceled',
        };
    }

    /**
     * Get the Bootstrap badge/button class for the enum case.
     *
     * @return string
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::NEW => 'btn-secondary',
            self::ACTIVE => 'btn-primary',
            self::DONE => 'btn-success',
            self::CANCELED => 'btn-danger',
        };
    }


    /**
     * Get a map of all enum cases to their human-readable labels.
     * Useful for dropdowns, forms, etc.
     *
     * @return array<int, string>
     */
    public static function getLabels(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $item) {
            $carry[$item->value] = $item->getLabel();
            return $carry;
        }, []);
    }

}