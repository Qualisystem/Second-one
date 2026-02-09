<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use App\Models\Application;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AssetsMap extends Page
{
    protected static string $resource = ApplicationResource::class;

    protected static string $view = 'filament.resources.application-resource.pages.assets-map';

    // Set the custom route URL
    protected static ?string $slug = 'map';

    public function getAssetsWithCoordinates()
    {
        $user = Auth::user();
        // Fetch only assets that have coordinates set
        return Application::whereNotNull('latitude')
            ->whereNotNull('longitude')
            // ->where('user_code', $user->code ?? (string) $user->id) // Allow this to show assets on the map only for the users that created them
            ->get(['name', 'latitude', 'longitude']);
    }
}