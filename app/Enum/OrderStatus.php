<?php

namespace App\Enum;

enum OrderStatus: string
{
    case ACCEPTED = 'accepted';
    case PREPARATION = 'preparation';
    case DONE = 'done';
    
    // You can add other statuses here as needed

    /**
     * Attempts to create an Enum instance from a string.
     */
    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        throw new \ValueError("$value is not a valid backing value for enum " . self::class);
    }
}
