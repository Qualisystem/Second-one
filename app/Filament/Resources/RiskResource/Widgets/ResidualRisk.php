<?php

namespace App\Filament\Resources\RiskResource\Widgets;

use App\Models\Risk;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ResidualRisk extends Widget
{
    protected static string $view = 'filament.widgets.risk-map';

    public $grid;

    public $title;

    protected static ?int $sort = 3;

    public function mount($title = 'Residual Risk'): void
    {
        // Get risks based on user's institution
        $risks = $this->getRisksForUser();
        $this->grid = InherentRisk::generateGrid($risks, 'residual');
        $this->title = $title;
    }

    /**
     * Get risks filtered by user's institution (or all if super admin).
     */
    protected function getRisksForUser(): Collection
    {
        $user = Auth::user();
        $query = Risk::query();

        // If not super admin, filter by institution
        if (!$user->hasRole('Super Admin')) {
            $query->where('institution_id', $user->institution_id);
        }

        return $query->get();
    }

    public static function generateGrid(Collection $risks, string $type): array
    {
        $grid = array_fill(0, 5, array_fill(0, 5, []));

        foreach ($risks as $risk) {
            if ($type == 'inherent') {
                $likelihoodIndex = $risk->inherent_likelihood - 1;
                $impactIndex = $risk->inherent_impact - 1;
            } else {
                $likelihoodIndex = $risk->residual_likelihood - 1;
                $impactIndex = $risk->residual_impact - 1;
            }

            if (isset($grid[$impactIndex][$likelihoodIndex])) {
                $grid[$impactIndex][$likelihoodIndex][] = $risk;
            }
        }

        return $grid;
    }
}
