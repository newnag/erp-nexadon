<?php

namespace Database\Seeders;

use App\Models\UnitConversion;
use Illuminate\Database\Seeder;

class UnitConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conversions = [
            // น้ำหนัก (Weight)
            ['from_unit' => 'g', 'to_unit' => 'kg', 'factor' => 0.001],
            ['from_unit' => 'mg', 'to_unit' => 'g', 'factor' => 0.001],
            ['from_unit' => 'mg', 'to_unit' => 'kg', 'factor' => 0.000001],
            
            // ปริมาตร (Volume)
            ['from_unit' => 'ml', 'to_unit' => 'l', 'factor' => 0.001],
            ['from_unit' => 'cc', 'to_unit' => 'ml', 'factor' => 1],
            ['from_unit' => 'cc', 'to_unit' => 'l', 'factor' => 0.001],
            
            // ช้อน (Spoon) - ประมาณการ
            ['from_unit' => 'tsp', 'to_unit' => 'ml', 'factor' => 5], // 1 ช้อนชา = 5 ml
            ['from_unit' => 'tbsp', 'to_unit' => 'ml', 'factor' => 15], // 1 ช้อนโต๊ะ = 15 ml
            ['from_unit' => 'tsp', 'to_unit' => 'tbsp', 'factor' => 0.333333],
            
            // ถ้วย (Cup)
            ['from_unit' => 'cup', 'to_unit' => 'ml', 'factor' => 240],
            ['from_unit' => 'cup', 'to_unit' => 'l', 'factor' => 0.24],
        ];

        foreach ($conversions as $conversion) {
            UnitConversion::firstOrCreate(
                [
                    'from_unit' => $conversion['from_unit'],
                    'to_unit' => $conversion['to_unit'],
                ],
                $conversion
            );
        }
    }
}
