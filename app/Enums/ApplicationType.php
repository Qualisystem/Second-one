<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationType: string implements HasColor, HasLabel
{
    // case TE = 'Telecom Exchange';
    // case PGCC = 'Power Grid Control Center';
    // case SCADA = 'SCADA System';
    // case SAAS = 'SaaS';
    // case DESKTOP = 'Desktop';
    // case SERVER = 'Server';
    // case APPLIANCE = 'Appliance';
    // case OTHER = 'Other';
    
    case NetworksAndCommunication = 'Networks and Communication';
    case Data = 'Data';
    case HardwareAndComputingInfrastructure = 'Hardware and Computing Infrastructure';
    case SoftwareSystems = 'Software Systems';
    case Human = 'Human';
    case Facilities = 'Facilities';

    public function getLabel(): ?string
    {
        // return match ($this) {
        //     self::TE => 'Telecom Exchange',
        //     self::PGCC => 'Power Grid Control Center',
        //     self::SCADA => 'SCADA System',
        //     self::SAAS => 'SaaS',
        //     self::DESKTOP => 'Desktop',
        //     self::SERVER => 'Server',
        //     self::APPLIANCE => 'Appliance',
        //     self::OTHER => 'Other',
        // };
        return match ($this) {
            self::NetworksAndCommunication => 'Networks and Communication',
            self::Data => 'Data',
            self::HardwareAndComputingInfrastructure => 'Hardware and Computing Infrastructure',
            self::SoftwareSystems => 'Software Systems',
            self::Human => 'Human',
            self::Facilities => 'Facilities',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NetworksAndCommunication => 'primary',
            self::Data => 'primary',
            self::HardwareAndComputingInfrastructure => 'primary',
            self::SoftwareSystems => 'secondary',
            self::Human => 'info',
            self::Facilities => 'success',
        };
    }
}
