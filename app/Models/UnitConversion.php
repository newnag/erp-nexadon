<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    protected $fillable = [
        'from_unit',
        'to_unit',
        'factor',
    ];

    protected $casts = [
        'factor' => 'decimal:10',
    ];

    /**
     * แปลงค่าจากหน่วยหนึ่งไปอีกหน่วยหนึ่ง
     * 
     * @param float $value ค่าที่ต้องการแปลง
     * @param string $fromUnit หน่วยต้นทาง
     * @param string $toUnit หน่วยปลายทาง
     * @return float|null ค่าที่แปลงแล้ว หรือ null ถ้าแปลงไม่ได้
     */
    public static function convert(float $value, string $fromUnit, string $toUnit): ?float
    {
        // ถ้าหน่วยเดียวกัน ไม่ต้องแปลง
        if (strtolower($fromUnit) === strtolower($toUnit)) {
            return $value;
        }

        // หาตัวแปลงโดยตรง
        $conversion = self::where('from_unit', strtolower($fromUnit))
            ->where('to_unit', strtolower($toUnit))
            ->first();

        if ($conversion) {
            return $value * $conversion->factor;
        }

        // หาตัวแปลงย้อนกลับ
        $reverseConversion = self::where('from_unit', strtolower($toUnit))
            ->where('to_unit', strtolower($fromUnit))
            ->first();

        if ($reverseConversion) {
            return $value / $reverseConversion->factor;
        }

        // แปลงไม่ได้
        return null;
    }

    /**
     * ตรวจสอบว่าสามารถแปลงหน่วยได้หรือไม่
     */
    public static function canConvert(string $fromUnit, string $toUnit): bool
    {
        if (strtolower($fromUnit) === strtolower($toUnit)) {
            return true;
        }

        return self::where(function ($query) use ($fromUnit, $toUnit) {
            $query->where('from_unit', strtolower($fromUnit))
                  ->where('to_unit', strtolower($toUnit));
        })->orWhere(function ($query) use ($fromUnit, $toUnit) {
            $query->where('from_unit', strtolower($toUnit))
                  ->where('to_unit', strtolower($fromUnit));
        })->exists();
    }
}
