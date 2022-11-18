<?php

namespace App\Enums;

enum StatusEnum: int
{
    case ACTIVE = 1;
    case PENDING = 0;
    case DISABLED = 2;

    public function getStringValue(): string
    {
        return match ($this) {
            self::ACTIVE => 'active',
            self::PENDING => 'pending',
            self::DISABLED => 'disabled',
            default => null
        };
    }

    static function getEnumValue(string $status): ?self
    {
        return match ($status) {
            'active' => self::ACTIVE,
            'pending' => self::PENDING,
            'disabled' => self::DISABLED,
            default => null
        };
    }
}
