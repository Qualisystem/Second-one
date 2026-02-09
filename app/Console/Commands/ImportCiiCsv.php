<?php

namespace App\Console\Commands;

use App\Enums\ApplicationType;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Support\Facades\Schema;
use App\Models\Institution;
use App\Models\ApplicationGeolocation;

#[AsCommand(name: 'opengrc:import-cii-csv')]
class ImportCiiCsv extends Command
{
    protected $signature = 'opengrc:import-cii-csv
        {--path=public/CII.csv : Path relative to project root (or absolute path)}
        {--dry-run : Parse and show what would be imported, without writing}
        {--update-existing : Update existing records (matched by generated code)}
        {--institution-id= : Force institution_id for imported rows}
        {--owner-id= : Force owner_id for imported rows (users.id)}
        {--vendor-id= : Force vendor_id for imported rows (vendors.id)}
        {--limit= : Import only first N rows (for testing)}';

    protected $description = 'Import Assets (CIIs) from CII.csv, and try to convert Plus Codes to lat/long.';

    public function handle(): void
    {
        $csvPath = (string) $this->option('path');
        $fullPath = $this->resolvePath($csvPath);

        if (! is_file($fullPath)) {
            $this->components->error("CSV not found: {$fullPath}");
            return;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            $this->components->error("Unable to open: {$fullPath}");
            return;
        }

        $header = fgetcsv($handle);
        if (! is_array($header) || $header === []) {
            fclose($handle);
            $this->components->error('CSV header row missing/invalid.');
            return;
        }

        $header[0] = $this->stripUtf8Bom((string) $header[0]);
        $header = array_map(fn ($h) => trim((string) $h), $header);

        $required = [
            'CMCII_Institution',
            'Sector',
            'SERVICE',
            'CII_Name',
            'CII_Type',
            'Description',
            'Location',
            'Dependencies (Upstream/Downstream Asset)',
            'Ownership (Public, Private, Hybrid)',
            'Custodian',
            'Geolocation Parameters',
            'Status (A/C/R)',
            'Impact Users Affected (0-4)',
            'Economic Impact (1-4)',
            'Recovery Time (1-4)',
            'Availability of Alternatives (1-4)',
            'Cross-sector Dependencies (1-4)',
            'Score',
            'Tier',
        ];

        $missing = array_values(array_diff($required, $header));
        if ($missing !== []) {
            fclose($handle);
            $this->components->error('CSV header is missing columns: '.implode(', ', $missing));
            return;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updateExisting = (bool) $this->option('update-existing');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;

        $institutionId = $this->option('institution-id') !== null ? (int) $this->option('institution-id') : null;

        $ownerId = $this->option('owner-id') !== null ? (int) $this->option('owner-id') : null;
        $vendorId = $this->option('vendor-id') !== null ? (int) $this->option('vendor-id') : null;

        $appTable = (new Application())->getTable();
        $appColumns = Schema::getColumnListing($appTable);

        // Validate institution exists
        if (! $dryRun && $institutionId && ! DB::table('institutions')->where('id', $institutionId)->exists()) {
            $this->components->error("Invalid --institution-id={$institutionId} (no matching institutions.id).");
            fclose($handle);
            return;
        }

        // If the table has owner_id/vendor_id columns, require valid ids
        if (! $dryRun && in_array('owner_id', $appColumns, true)) {
            if (! $ownerId || ! DB::table('users')->where('id', $ownerId)->exists()) {
                $this->components->error('Missing/invalid --owner-id (required because applications.owner_id exists).');
                fclose($handle);
                return;
            }
        }
        if (! $dryRun && in_array('vendor_id', $appColumns, true)) {
            if (! $vendorId || ! DB::table('vendors')->where('id', $vendorId)->exists()) {
                $this->components->error('Missing/invalid --vendor-id (required because applications.vendor_id exists).');
                fclose($handle);
                return;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $rowNum = 1;

        $institutionLookup = null;
        if ($institutionId === null) {
            $institutionLookup = Institution::query()
                ->pluck('id', 'label')
                ->mapWithKeys(fn ($id, $label) => [mb_strtolower(trim((string) $label)) => (int) $id])
                ->all();

            if (! $dryRun && $institutionLookup === []) {
                $this->components->error('No institutions found in DB; create/import institutions first.');
                fclose($handle);
                return;
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($limit !== null && ($created + $updated + $skipped) >= $limit) {
                break;
            }

            if (! is_array($row) || count($row) === 0) {
                continue;
            }

            $assoc = $this->combineRow($header, $row);
            if ($assoc === null) {
                $this->warn("Row {$rowNum}: column count mismatch; skipped.");
                $skipped++;
                continue;
            }

            $ciiName = trim((string) $assoc['CII_Name']);
            if ($ciiName === '') {
                $this->warn("Row {$rowNum}: missing CII_Name; skipped.");
                $skipped++;
                continue;
            }

            $institution = trim((string) $assoc['CMCII_Institution']);
            $rowInstitutionId = $institutionId ?? ($institutionLookup[mb_strtolower($institution)] ?? null);

            if (! $rowInstitutionId) {
                $this->warn("Row {$rowNum}: institution not found in DB: {$institution}; skipped.");
                $skipped++;
                continue;
            }
            $sector = trim((string) $assoc['Sector']);
            $service = trim((string) $assoc['SERVICE']);

            $code = $this->stableCode($institution, $sector, $service, $ciiName);

            // [$lat, $lng, $geoNote] = $this->parseGeolocation((string) $assoc['Geolocation Parameters']);
            $geos = $this->parseGeolocations((string) $assoc['Geolocation Parameters']);
            $lat = $geos[0]['latitude'] ?? null;
            $lng = $geos[0]['longitude'] ?? null;
            // (optional) keep a note if you want
            $geoNote = $geos !== [] ? 'Geolocation (raw): '.trim((string) $assoc['Geolocation Parameters']) : null;

            $rawType = trim((string) $assoc['CII_Type']);
            $typeEnum = ApplicationType::tryFrom($rawType) ?? ApplicationType::cases()[0];

            $notesLines = [
                "Institution: {$institution}",
                "Sector: {$sector}",
                "Service: {$service}",
                "Ownership: ".trim((string) $assoc['Ownership (Public, Private, Hybrid)']),
                "Tier: ".trim((string) $assoc['Tier']),
                "Score (CSV): ".trim((string) $assoc['Score']),
                "Assessment: users=".trim((string) $assoc['Impact Users Affected (0-4)'])
                    .", econ=".trim((string) $assoc['Economic Impact (1-4)'])
                    .", rto=".trim((string) $assoc['Recovery Time (1-4)'])
                    .", alt=".trim((string) $assoc['Availability of Alternatives (1-4)'])
                    .", xsector=".trim((string) $assoc['Cross-sector Dependencies (1-4)']),
            ];

            if ($rawType !== '' && ApplicationType::tryFrom($rawType) === null) {
                $notesLines[] = "CII_Type (raw): {$rawType}";
            }

            if ($geoNote !== null) {
                $notesLines[] = $geoNote;
            }

            $payload = [
                'code' => $code,
                'name' => $ciiName,
                'institution_id' => $rowInstitutionId,
                'type' => $rawType !== '' ? $rawType : null,
                'service' => $service,
                'owner_id' => $ownerId,
                'vendor_id' => $vendorId,
                'dependencies' => $this->nullIfBlank((string) $assoc['Dependencies (Upstream/Downstream Asset)']),
                // 'dependencies' => collect(preg_split('/[,\n;]+/', (string) $assoc['Dependencies (Upstream/Downstream Asset)']))
                //     ->map(fn ($v) => trim((string) $v))
                //     ->filter()
                //     ->values()
                //     ->all(),
                'description' => $this->nullIfBlank((string) $assoc['Description']),
                'location' => $this->nullIfBlank((string) $assoc['Location']),
                // 'dependencies' => $this->nullIfBlank((string) $assoc['Dependencies (Upstream/Downstream Asset)']),
                'owner_name' => $this->nullIfBlank((string) $assoc['Ownership (Public, Private, Hybrid)']) ?? $institution,
                'custodian' => $this->nullIfBlank((string) $assoc['Custodian']) ?? $institution,
                'status' => $this->normalizeStatus((string) $assoc['Status (A/C/R)']),
                'tier' => preg_match('/([1-4])/', (string) $assoc['Tier'], $m) ? (int) $m[1] : null,
                'impact_users_affected' => is_numeric($assoc['Impact Users Affected (0-4)']) ? (int) $assoc['Impact Users Affected (0-4)'] : null,
                'economic_impact' => is_numeric($assoc['Economic Impact (1-4)']) ? (int) $assoc['Economic Impact (1-4)'] : null,
                'recovery_time' => is_numeric($assoc['Recovery Time (1-4)']) ? (int) $assoc['Recovery Time (1-4)'] : null,
                'availability_of_alternatives' => is_numeric($assoc['Availability of Alternatives (1-4)']) ? (int) $assoc['Availability of Alternatives (1-4)'] : null,
                'cross_sector_dependencies' => is_numeric($assoc['Cross-sector Dependencies (1-4)']) ? (int) $assoc['Cross-sector Dependencies (1-4)'] : null,
                'latitude' => $lat,
                'longitude' => $lng,
                'notes' => implode("\n", array_values(array_filter($notesLines, fn ($v) => trim((string) $v) !== ''))),
            ];

            
            $columns = Schema::getColumnListing((new Application())->getTable());
            $payload = array_intersect_key($payload, array_flip($columns));

            if ($dryRun) {
                $this->line("Row {$rowNum}: {$payload['code']} | {$payload['name']}".($lat !== null && $lng !== null ? " | {$lat},{$lng}" : ''));
                continue;
            }

            $rawGeo = trim((string) $assoc['Geolocation Parameters']);

            $geoLabel = null;
            $geoRawValue = null;

            if ($rawGeo !== '') {
                [$codePart, $placePart] = array_pad(array_map('trim', explode(',', $rawGeo, 2)), 2, null);

                $isPlus = $this->looksLikePlusCode((string) $codePart);

                $geoLabel = $isPlus ? ($placePart ?: null) : $rawGeo;
                $geoRawValue = $isPlus ? strtoupper(str_replace(' ', '', (string) $codePart)) : null;
            }

            DB::transaction(function () use ($payload, $updateExisting, &$created, &$updated, $geos) {
                $app = Application::query()->where('code', $payload['code'])->first();

                if ($app) {
                    if (! $updateExisting) {
                        return;
                    }

                    $app->fill($payload);
                    $app->save();
                    $updated++;
                } else {
                    $app = Application::query()->create($payload);
                    $created++;
                }

                if ($geos !== []) {
                    // keep importer idempotent: replace geolocations from CSV
                    $app->geolocations()->delete();

                    foreach ($geos as $geo) {
                        $app->geolocations()->create($geo);
                    }
                }
            });
        }

        fclose($handle);

        if ($dryRun) {
            $this->components->info('Dry-run complete (no changes written).');
            return;
        }

        $this->components->info("Import complete. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");
        if ($this->missingPlusCodeLib()) {
            $this->components->warn('Plus Code decoding library not detected; Plus Codes may not have been converted to lat/long.');
            $this->components->warn('Install it in your container/project, then re-run: composer require c3t4r4/openlocationcode');
        }
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return base_path('public/CII.csv');
        }

        // Absolute on Linux or Windows drive path
        if (Str::startsWith($path, ['/','\\']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function stripUtf8Bom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    private function combineRow(array $header, array $row): ?array
    {
        if (count($row) < count($header)) {
            $row = array_pad($row, count($header), '');
        } elseif (count($row) > count($header)) {
            return null;
        }

        $assoc = array_combine($header, $row);

        return is_array($assoc) ? $assoc : null;
    }

    private function stableCode(string $institution, string $sector, string $service, string $ciiName): string
    {
        $key = mb_strtolower(trim($institution.'|'.$sector.'|'.$service.'|'.$ciiName));
        return 'AST-'.strtoupper(substr(md5($key), 0, 6));
    }

    private function nullIfBlank(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function normalizeStatus(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Active';
        }

        $map = [
            'A' => 'Active',
            'C' => 'Candidate',
            'R' => 'Retired',
        ];

        return $map[strtoupper($value)] ?? $value;
    }

    private function parseGeolocations(string $value): array
    {
        $raw = trim($value);
        if ($raw === '') {
            return [];
        }

        // Case 1: "lat, lng"
        if (preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $raw, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];

            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return [[
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'label' => null,
                    'raw_value' => $raw,
                ]];
            }
        }

        // Extract all plus codes from the string (handles "FC3G+3X ,F88C+FV7")
        $normalized = strtoupper(str_replace(' ', '', $raw));
        preg_match_all('/[23456789CFGHJMPQRVWX]{2,8}\+[23456789CFGHJMPQRVWX]{2,8}/', $normalized, $matches);
        $codes = array_values(array_unique($matches[0] ?? []));

        // Optional place hint only when it looks like "CODE, Place" (not another code)
        $placeHint = null;
        if (str_contains($raw, ',')) {
            [$first, $rest] = array_pad(explode(',', $raw, 2), 2, '');
            $rest = trim($rest);

            if ($rest !== '' && ! $this->looksLikePlusCode($rest)) {
                // also avoid treating "13.4, -16.5" as place hint
                if (! preg_match('/^\s*-?\d{1,2}(?:\.\d+)?\s*,\s*-?\d{1,3}(?:\.\d+)?\s*$/', $raw)) {
                    $placeHint = $rest;
                }
            }
        }

        // Case 2: one or many plus codes
        if ($codes !== []) {
            $out = [];

            foreach ($codes as $code) {
                [$lat, $lng] = $this->tryDecodePlusCode($code, $placeHint);

                $out[] = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'label' => $placeHint ?: null,
                    'raw_value' => $code, // Plus Code only (never free text)
                ];
            }

            return $out;
        }

        // Case 3: free-text label only
        return [[
            'latitude' => null,
            'longitude' => null,
            'label' => $raw,
            'raw_value' => null, // IMPORTANT: don’t fill Google Plus Code for free text
        ]];
    }

    /**
     * Returns: [lat|null, lng|null, note|null]
     * - If value is free text (e.g. "Across the Gambia"), note is "Geolocation label: ...".
     * - If value looks like a Plus Code, tries to decode; note stores the raw value regardless.
     */
    private function parseGeolocation(string $value): array
    {
        $raw = trim($value);
        if ($raw === '') {
            return [null, null, null];
        }

        [$codePart, $placePart] = array_pad(array_map('trim', explode(',', $raw, 2)), 2, null);
        $codePart = $codePart ?? $raw;

        // Not a Plus Code -> keep as label text
        if (! $this->looksLikePlusCode($codePart)) {
            return [null, null, 'Geolocation label: '.$raw];
        }

        // Always store the raw string in notes for traceability
        $note = 'Geolocation (raw): '.$raw;

        // Try decoding with Open Location Code library if present
        [$lat, $lng] = $this->tryDecodePlusCode($codePart, $placePart);

        return [$lat, $lng, $note];
    }

    private function looksLikePlusCode(string $value): bool
    {
        $value = strtoupper(trim($value));
        return str_contains($value, '+') && preg_match('/^[23456789CFGHJMPQRVWX\+]+$/', str_replace(' ', '', $value)) === 1;
    }

    private function missingPlusCodeLib(): bool
    {
        return ! class_exists('\\OpenLocationCode\\OpenLocationCode');
    }

    private function tryDecodePlusCode(string $plusCode, ?string $placeHint): array
    {
        $plusCode = strtoupper(str_replace(' ', '', trim($plusCode)));

        $olcClass = '\\OpenLocationCode\\OpenLocationCode';
        if (! class_exists($olcClass) || ! method_exists($olcClass, 'decode')) {
            return [null, null];
        }

        // If it's a short code and we have a place hint, try to geocode the place and recover a full code.
        if ($placeHint && method_exists($olcClass, 'isShort') && $olcClass::isShort($plusCode) && method_exists($olcClass, 'recoverNearest')) {
            [$refLat, $refLng] = $this->geocodePlace($placeHint);
            if ($refLat !== null && $refLng !== null) {
                $plusCode = $olcClass::recoverNearest($plusCode, $refLat, $refLng);
            }
        }

        try {
            $decoded = $olcClass::decode($plusCode);
        } catch (\Throwable) {
            return [null, null];
        }

        // Support common return shapes (object/array)
        $lat = $this->extractDecodedLatitude($decoded);
        $lng = $this->extractDecodedLongitude($decoded);

        return [$lat, $lng];
    }

    private function geocodePlace(string $place): array
    {
        // Nominatim usage policy: keep it light; this is best-effort.
        try {
            $resp = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'OpenGRC CSV Import'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $place,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if (! $resp->ok()) {
                return [null, null];
            }

            $json = $resp->json();
            if (! is_array($json) || ! isset($json[0]['lat'], $json[0]['lon'])) {
                return [null, null];
            }

            return [(float) $json[0]['lat'], (float) $json[0]['lon']];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    private function extractDecodedLatitude(mixed $decoded): ?float
    {
        foreach (['latitudeCenter', 'latitude_center', 'centerLatitude', 'center_latitude'] as $prop) {
            if (is_array($decoded) && isset($decoded[$prop])) return (float) $decoded[$prop];
            if (is_object($decoded) && isset($decoded->{$prop})) return (float) $decoded->{$prop};
        }

        foreach (['getCenterLatitude', 'getLatitudeCenter'] as $method) {
            if (is_object($decoded) && method_exists($decoded, $method)) return (float) $decoded->{$method}();
        }

        return null;
    }

    private function extractDecodedLongitude(mixed $decoded): ?float
    {
        foreach (['longitudeCenter', 'longitude_center', 'centerLongitude', 'center_longitude'] as $prop) {
            if (is_array($decoded) && isset($decoded[$prop])) return (float) $decoded[$prop];
            if (is_object($decoded) && isset($decoded->{$prop})) return (float) $decoded->{$prop};
        }

        foreach (['getCenterLongitude', 'getLongitudeCenter'] as $method) {
            if (is_object($decoded) && method_exists($decoded, $method)) return (float) $decoded->{$method}();
        }

        return null;
    }
}