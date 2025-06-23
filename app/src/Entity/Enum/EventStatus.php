<?php

/**
 * Event status.
 */

namespace App\Entity\Enum;

/**
 * Enum EventStatus.
 */
enum EventStatus: int
{
    case PERSONAL = 1;

    case IMPORTANT = 2;

    case WORK = 3;

    /**
     * Get the human-readable label for the enum case.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PERSONAL => 'label.personal',
            self::IMPORTANT => 'label.important',
            self::WORK => 'label.work',
        };
    }

    /**
     * Get the Bootstrap badge/button class for the enum case.
     *
     * @return string
     */
    public function getButtonClass(): string
    {
        return match ($this) {
            self::PERSONAL => 'btn-success',
            self::IMPORTANT => 'btn-danger',
            self::WORK => 'btn-primary',
        };
    }

    /**
     * Get a map of all enum cases to their human-readable labels.
     * Useful for dropdowns, forms, etc.
     *
     * @return array[]
     */
    public static function getLabels(): array
    {
        return array_reduce(self::cases(), function (array $carry, self $item) {
            $carry[$item->value] = $item->getLabel();

            return $carry;
        }, []);
    }
}
