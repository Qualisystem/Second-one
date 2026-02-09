<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'Active (In use)';
    case CANDIDATE = 'Candidate (Under review)';
    case RETIRED = 'Retired (No longer in use)';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'Active (In use)',
            self::CANDIDATE => 'Candidate (Under review)',
            self::RETIRED => 'Retired (No longer in use)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::CANDIDATE => 'gray',
            self::RETIRED => 'warning',
        };
    }
}
