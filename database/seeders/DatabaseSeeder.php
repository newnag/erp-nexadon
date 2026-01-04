<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // สร้างผู้ใช้งานเริ่มต้น
        User::factory()->create([
            'name' => 'Inrada',
            'email' => 'inrada.nkb@gmail.com',
        ]);

        // เรียกรัน Seeders ทั้งหมดตามลำดับที่ถูกต้อง
        $this->call([
            // 1. Seeders พื้นฐานที่ไม่ขึ้นกับตารางอื่น
            UnitConversionSeeder::class,
            FinancialCategorySeeder::class,
            
            // 2. Seeders ที่ขึ้นกับตารางอื่นๆ (เพิ่มในอนาคต)
            // SupplierSeeder::class,
            // IngredientSeeder::class,
            // RecipeSeeder::class,
        ]);
    }
}
