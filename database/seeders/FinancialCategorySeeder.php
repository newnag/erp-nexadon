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
            ['name' => 'รายได้จากการขาย', 'type' => 'income', 'description' => 'รายได้จากการขายสินค้าและบริการ', 'color' => '#22c55e', 'is_active' => true],
            ['name' => 'รายได้อื่นๆ', 'type' => 'income', 'description' => 'รายได้อื่นๆ ที่ไม่ใช่จากการขาย', 'color' => '#10b981', 'is_active' => true],
            ['name' => 'ดอกเบี้ยรับ', 'type' => 'income', 'description' => 'ดอกเบี้ยจากเงินฝากธนาคาร', 'color' => '#14b8a6', 'is_active' => true],
            ['name' => 'เงินคืน/ส่วนลด', 'type' => 'income', 'description' => 'เงินคืนหรือส่วนลดที่ได้รับ', 'color' => '#06b6d4', 'is_active' => true],

            // รายจ่าย
            ['name' => 'วัตถุดิบ - อาหารสด', 'type' => 'expense', 'description' => 'วัตถุดิบอาหารสดประเภทต่างๆ', 'color' => '#10b981', 'is_active' => true],
            ['name' => 'วัตถุดิบ - ของแห้ง', 'type' => 'expense', 'description' => 'วัตถุดิบประเภทของแห้ง', 'color' => '#f59e0b', 'is_active' => true],
            ['name' => 'เครื่องดื่ม', 'type' => 'expense', 'description' => 'เครื่องดื่มและน้ำดื่ม', 'color' => '#3b82f6', 'is_active' => true],
            ['name' => 'บรรจุภัณฑ์', 'type' => 'expense', 'description' => 'บรรจุภัณฑ์และวัสดุห่อหุ้ม', 'color' => '#8b5cf6', 'is_active' => true],
            ['name' => 'เงินเดือน/ค่าจ้าง', 'type' => 'expense', 'description' => 'เงินเดือนและค่าจ้างพนักงาน', 'color' => '#ef4444', 'is_active' => true],
            ['name' => 'ค่าเช่าสถานที่', 'type' => 'expense', 'description' => 'ค่าเช่าสถานที่และพื้นที่', 'color' => '#ec4899', 'is_active' => true],
            ['name' => 'ค่าสาธารณูปโภค', 'type' => 'expense', 'description' => 'ค่าไฟฟ้า น้ำประปา โทรศัพท์ อินเทอร์เน็ต', 'color' => '#06b6d4', 'is_active' => true],
            ['name' => 'ค่าการตลาด', 'type' => 'expense', 'description' => 'ค่าโฆษณาและการตลาด', 'color' => '#f97316', 'is_active' => true],
            ['name' => 'ค่า GP Delivery', 'type' => 'expense', 'description' => 'ค่าบริการจัดส่ง GP Delivery', 'color' => '#14b8a6', 'is_active' => true],
            ['name' => 'ค่าใช้จ่ายเบ็ดเตล็ด', 'type' => 'expense', 'description' => 'ค่าใช้จ่ายอื่นๆ', 'color' => '#64748b', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            FinancialCategory::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}
