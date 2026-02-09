<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\Sector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrepareCiiImport extends Command
{
    protected $signature = 'cii:prepare';
    protected $description = 'Prepare CII.csv for import by mapping sector/institution names to IDs';

    public function handle(): void
    {
        $csvPath = public_path('CII.csv');
        
        if (!file_exists($csvPath)) {
            $this->error("CII.csv not found in public/");
            return;
        }

        // Load sectors and institutions
        $sectors = Sector::pluck('id', 'label')->toArray();
        $institutions = Institution::pluck('id', 'label')->toArray();

        $this->info("Loaded " . count($sectors) . " sectors");
        $this->info("Loaded " . count($institutions) . " institutions");

        // Read CSV
        $rows = array_map('str_getcsv', file($csvPath));
        $header = array_shift($rows);

        $this->info("CSV has " . count($rows) . " data rows");

        // Find column indices
        $sectorIdx = array_search('Sector', $header);
        $institutionIdx = array_search('Institution', $header);

        if ($sectorIdx === false || $institutionIdx === false) {
            $this->error("Could not find Sector or Institution columns");
            return;
        }

        // Track missing mappings
        $missingSectors = [];
        $missingInstitutions = [];

        foreach ($rows as &$row) {
            $sectorName = trim($row[$sectorIdx] ?? '');
            $institutionName = trim($row[$institutionIdx] ?? '');

            if ($sectorName && !isset($sectors[$sectorName])) {
                $missingSectors[$sectorName] = true;
            }

            if ($institutionName && !isset($institutions[$institutionName])) {
                $missingInstitutions[$institutionName] = true;
            }
        }

        if (!empty($missingSectors)) {
            $this->warn("Missing sectors in DB:");
            foreach (array_keys($missingSectors) as $s) {
                $this->line("  - $s");
            }
        }

        if (!empty($missingInstitutions)) {
            $this->warn("Missing institutions in DB:");
            foreach (array_keys($missingInstitutions) as $i) {
                $this->line("  - $i");
            }
        }

        $this->info("Run 'php artisan cii:import' after ensuring all sectors/institutions exist.");
    }
}