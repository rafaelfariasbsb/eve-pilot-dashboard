<?php

namespace App\Console\Commands;

use App\Models\SdeBlueprint;
use App\Models\SdeBlueprintMaterial;
use App\Models\SdeBlueprintProduct;
use App\Models\SdeType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportSde extends Command
{
    protected $signature = 'eve:import-sde {--force : Force re-download}';
    protected $description = 'Import EVE Online SDE (types and blueprints) from Fuzzwork';

    private const TYPES_URL = 'https://www.fuzzwork.co.uk/dump/latest/invTypes.csv';
    private const BLUEPRINTS_URL = 'https://www.fuzzwork.co.uk/dump/latest/industryActivity.csv';
    private const MATERIALS_URL = 'https://www.fuzzwork.co.uk/dump/latest/industryActivityMaterials.csv';
    private const PRODUCTS_URL = 'https://www.fuzzwork.co.uk/dump/latest/industryActivityProducts.csv';

    public function handle(): int
    {
        $this->info('Starting SDE import...');

        $this->importTypes();
        $this->importBlueprints();
        $this->importMaterials();
        $this->importProducts();

        $this->newLine();
        $this->info('SDE import complete!');
        $this->table(['Table', 'Rows'], [
            ['sde_types', SdeType::count()],
            ['sde_blueprints', SdeBlueprint::count()],
            ['sde_blueprint_materials', SdeBlueprintMaterial::count()],
            ['sde_blueprint_products', SdeBlueprintProduct::count()],
        ]);

        return self::SUCCESS;
    }

    private function importTypes(): void
    {
        $this->info('Downloading invTypes.csv...');
        $csv = $this->downloadCsv(self::TYPES_URL);

        $this->info('Importing types...');
        $bar = $this->output->createProgressBar(count($csv));

        DB::table('sde_types')->truncate();

        // CSV columns: typeID(0), groupID(1), typeName(2), description(3), mass(4),
        // volume(5), capacity(6), portionSize(7), raceID(8), basePrice(9),
        // published(10), marketGroupID(11), iconID(12), soundID(13), graphicID(14)
        $chunks = array_chunk($csv, 1000);
        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $row) {
                if (count($row) < 11) continue;

                $groupId = ($row[1] ?? 'None') !== 'None' ? (int) $row[1] : null;
                $volume = ($row[5] ?? 'None') !== 'None' && is_numeric($row[5]) ? (float) $row[5] : null;
                $marketGroupId = ($row[11] ?? 'None') !== 'None' && is_numeric($row[11]) ? (int) $row[11] : null;
                $iconId = ($row[12] ?? 'None') !== 'None' && is_numeric($row[12]) ? (int) $row[12] : null;

                $rows[] = [
                    'type_id' => (int) $row[0],
                    'group_id' => $groupId,
                    'name' => mb_substr($row[2] ?? '', 0, 255),
                    'description' => ($row[3] ?? '') !== 'None' ? ($row[3] ?? null) : null,
                    'volume' => $volume,
                    'market_group_id' => $marketGroupId,
                    'published' => ($row[10] ?? '0') === '1',
                    'icon_id' => $iconId,
                ];
                $bar->advance();
            }
            if (!empty($rows)) {
                DB::table('sde_types')->insert($rows);
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function importBlueprints(): void
    {
        $this->info('Downloading industryActivity.csv...');
        $csv = $this->downloadCsv(self::BLUEPRINTS_URL);

        $this->info('Importing blueprints...');
        DB::table('sde_blueprints')->truncate();

        $blueprintIds = [];
        foreach ($csv as $row) {
            if (count($row) < 3) continue;
            $typeId = (int) $row[0];
            $activityId = (int) $row[1];

            if ($activityId === 1 && !isset($blueprintIds[$typeId])) {
                $blueprintIds[$typeId] = true;
            }
        }

        $chunks = array_chunk(array_keys($blueprintIds), 1000);
        foreach ($chunks as $chunk) {
            $rows = array_map(fn($id) => [
                'blueprint_type_id' => $id,
                'max_production_limit' => 0,
            ], $chunk);
            DB::table('sde_blueprints')->insert($rows);
        }

        $this->info('  Imported ' . count($blueprintIds) . ' blueprints');
    }

    private function importMaterials(): void
    {
        $this->info('Downloading industryActivityMaterials.csv...');
        $csv = $this->downloadCsv(self::MATERIALS_URL);

        $this->info('Importing blueprint materials...');
        $bar = $this->output->createProgressBar(count($csv));

        DB::table('sde_blueprint_materials')->truncate();

        $activityMap = [1 => 'manufacturing', 3 => 'research_time', 4 => 'research_material', 5 => 'copying', 8 => 'invention', 11 => 'reaction'];

        $chunks = array_chunk($csv, 1000);
        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $row) {
                if (count($row) < 4) continue;
                $activityId = (int) ($row[1] ?? 0);
                $activity = $activityMap[$activityId] ?? 'unknown';

                $rows[] = [
                    'blueprint_type_id' => (int) $row[0],
                    'activity' => $activity,
                    'material_type_id' => (int) $row[2],
                    'quantity' => (int) $row[3],
                ];
                $bar->advance();
            }
            if (!empty($rows)) {
                DB::table('sde_blueprint_materials')->insert($rows);
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function importProducts(): void
    {
        $this->info('Downloading industryActivityProducts.csv...');
        $csv = $this->downloadCsv(self::PRODUCTS_URL);

        $this->info('Importing blueprint products...');
        $bar = $this->output->createProgressBar(count($csv));

        DB::table('sde_blueprint_products')->truncate();

        $activityMap = [1 => 'manufacturing', 3 => 'research_time', 4 => 'research_material', 5 => 'copying', 8 => 'invention', 11 => 'reaction'];

        $chunks = array_chunk($csv, 1000);
        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $row) {
                if (count($row) < 4) continue;
                $activityId = (int) ($row[1] ?? 0);
                $activity = $activityMap[$activityId] ?? 'unknown';

                $rows[] = [
                    'blueprint_type_id' => (int) $row[0],
                    'activity' => $activity,
                    'product_type_id' => (int) $row[2],
                    'quantity' => (int) $row[3],
                    'probability' => isset($row[4]) && is_numeric($row[4]) ? (float) $row[4] : null,
                ];
                $bar->advance();
            }
            if (!empty($rows)) {
                DB::table('sde_blueprint_products')->insert($rows);
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function downloadCsv(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'EVE Pilot Dashboard/1.0 (github.com/rafaelfariasbsb/eve-pilot-dashboard)',
        ])->timeout(120)->get($url);

        if (!$response->successful()) {
            $this->error("Failed to download: {$url} (HTTP {$response->status()})");
            return [];
        }

        // Write to temp file and use fgetcsv for proper multi-line CSV parsing
        $tmpFile = tempnam(sys_get_temp_dir(), 'sde_');
        file_put_contents($tmpFile, $response->body());

        $data = [];
        $handle = fopen($tmpFile, 'r');
        fgetcsv($handle); // Skip header

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);
        unlink($tmpFile);

        return $data;
    }
}
