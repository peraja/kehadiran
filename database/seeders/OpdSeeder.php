<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class OpdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $response = Http::timeout(10)->get('http://apps.sinjaikab.go.id/api/pegawai/get_unit');
            if ($response->successful()) {
                $units = $response->json();
                if (is_array($units)) {
                    foreach ($units as $unit) {
                        $cleaned = Opd::cleanAndFormatData($unit);
                        if (!empty($cleaned['name'])) {
                            Opd::updateOrCreate(
                                ['unit_id' => $cleaned['unit_id'] ?? null],
                                $cleaned
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log or fallback
        }
    }
}
