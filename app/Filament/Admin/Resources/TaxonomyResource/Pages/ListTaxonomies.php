<?php

namespace App\Filament\Admin\Resources\TaxonomyResource\Pages;

use App\Filament\Admin\Resources\TaxonomyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxonomies extends ListRecords
{
    protected static string $resource = TaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
