<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\StandardStatus;
use App\Http\Controllers\HelperController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class CISCSCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert the three standards
        $standards = [
            [
                'name' => 'CIS Critical Security Controls Implementation Group 1 (IG1)',
                'code' => 'CIS-CSC-IG1',
                'authority' => 'Center for Internet Security (CIS)',
                'status' => StandardStatus::IN_SCOPE,
                'reference_url' => 'https://www.cisecurity.org/controls/cis-controls-list/',
                'description' => 'CIS Controls IG1 are basic cyber hygiene and represent essential cyber defense readiness for all enterprises.',
            ],
            [
                'name' => 'CIS Critical Security Controls Implementation Group 2 (IG2)',
                'code' => 'CIS-CSC-IG2',
                'authority' => 'Center for Internet Security (CIS)',
                'status' => StandardStatus::IN_SCOPE,
                'reference_url' => 'https://www.cisecurity.org/controls/cis-controls-list/',
                'description' => 'CIS Controls IG2 build upon IG1 and are for enterprises with sensitive client or operational information, requiring additional security.',
            ],
            [
                'name' => 'CIS Critical Security Controls Implementation Group 3 (IG3)',
                'code' => 'CIS-CSC-IG3',
                'authority' => 'Center for Internet Security (CIS)',
                'status' => StandardStatus::IN_SCOPE,
                'reference_url' => 'https://www.cisecurity.org/controls/cis-controls-list/',
                'description' => 'CIS Controls IG3 are for enterprises with critical infrastructure or assets, requiring the most comprehensive security measures.',
            ],
        ];

        foreach ($standards as $standard) {
            DB::table('standards')->insert($standard);
        }

        // Get the standard IDs
        $standardIds = [
            1 => DB::table('standards')->where('code', 'CIS-CSC-IG1')->value('id'),
            2 => DB::table('standards')->where('code', 'CIS-CSC-IG2')->value('id'),
            3 => DB::table('standards')->where('code', 'CIS-CSC-IG3')->value('id'),
        ];

        $csv = Reader::createFromPath(resource_path('data/CIS-CSC-All.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        foreach ($records as $record) {
            $code = 'CIS-CSC-'.$record['Code'];
            $title = $record['Title'];
            $description = $record['Description'];
            $type = ControlType::OTHER;
            $category = ControlCategory::UNKNOWN;
            $enforcement = ControlEnforcementCategory::OTHER;
            $group = (int) $record['Group'];

            // IG1 controls are also in IG2 and IG3, IG2 controls are also in IG3
            $groups = [];
            if ($group === 1) {
                $groups = [1, 2, 3];
            } elseif ($group === 2) {
                $groups = [2, 3];
            } elseif ($group === 3) {
                $groups = [3];
            }

            foreach ($groups as $ig) {
                DB::table('controls')->insert([
                    'standard_id' => $standardIds[$ig],
                    'code' => $code,
                    'title' => $title,
                    'type' => $type,
                    'category' => $category,
                    'enforcement' => $enforcement,
                    'discussion' => '',
                    'description' => HelperController::linesToParagraphs($description, 'control-description-text'),
                ]);
            }
        }
    }
}
