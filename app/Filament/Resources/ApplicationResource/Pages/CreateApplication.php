<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // Set institution from logged-in user
        if ($user && $user->institution_id) {
            $data['institution_id'] = $user->institution_id;
        }

        return $data;
    }
}
