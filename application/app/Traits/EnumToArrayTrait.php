<?php

namespace App\Traits;

trait EnumToArrayTrait
{
    public static function names(): array
    {
        $names = [];
        foreach (self::cases() as $case) {
            $names[] = $case->name;
        }
        return $names;
    }

    public static function values(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[] = $case->value;
        }
        return $values;
    }

    public static function random(array $only = []): mixed
    {
        $result = null;
        $maxAttempts = 1000;

        while (--$maxAttempts >= 0) {
            $result = self::values()[array_rand(self::values())];
            if (count($only) && !in_array($result, $only)) {
                continue;
            } else {
                break;
            }
        }

        return $result;
    }

    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }
}
