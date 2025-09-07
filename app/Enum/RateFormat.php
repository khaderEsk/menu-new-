<?php

namespace App\Enum;

enum RateFormat: int
{
    case EMOJI = 0;
    case QUESTION = 1;

    public function label(): string
    {
        return match ($this) {
            self::EMOJI => "Emoji",
            self::QUESTION => "Question",
        };
    }
}
