<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // รายรับ
            ['name' => 'รายได้จากการขาย', 'type' => 'income', 'description' => 'รายได้จากการขายสินค้าและบริการ', 'color' => '#22c55e'],
            ['name' => 'รายได้อื่นๆ', 'type' => 'income', 'description' => 'รายได้อื่นๆ ที่ไม่ใช่จากการขาย', 'color' => '#10b981'],
            ['name' => 'ดอกเบี้ยรับ', 'type' => 'income', 'description' => 'ดอกเบี้ยจากเงินฝากธนาคาร', 'color' => '#14b8a6'],
            ['name' => 'เงินคืน/ส่วนลด', 'type' => 'income', 'description' => 'เงินคืนหรือส่วนลดที่ได้รับ', 'color' => '#06b6d4'],

            // รายจ่าย
            ['name' => 'ค่าวัตถุดิบ', 'type' => 'expense', 'description' => 'ค่าใช้จ่ายสำหรับซื้อวัตถุดิบ', 'color' => '#ef4444'],
            ['name' => 'ค่าเช่า', 'type' => 'expense', 'description' => 'ค่าเช่าสถานที่', 'color' => '#f97316'],
            ['name' => 'ค่าน้ำ/ค่าไฟ', 'type' => 'expense', 'description' => 'ค่าสาธารณูปโภค', 'color' => '#f59e0b'],
            ['name' => 'เงินเดือน', 'type' => 'expense', 'description' => 'เงินเดือนพนักงาน', 'color' => '#eab308'],
            ['name' => 'ค่าขนส่ง', 'type' => 'expense', 'description' => 'ค่าขนส่งสินค้า', 'color' => '#84cc16'],
            ['name' => 'ค่าโฆษณา/การตลาด', 'type' => 'expense', 'description' => 'ค่าใช้จ่ายด้านการตลาด', 'color' => '#8b5cf6'],
            ['name' => 'ค่าซ่อมบำรุง', 'type' => 'expense', 'description' => 'ค่าซ่อมแซมและบำรุงรักษา', 'color' => '#a855f7'],
            ['name' => 'ค่าใช้จ่ายเบ็ดเตล็ด', 'type' => 'expense', 'description' => 'ค่าใช้จ่ายอื่นๆ', 'color' => '#64748b'],
        ];

        foreach ($categories as $category) {
            FinancialCategory::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}
