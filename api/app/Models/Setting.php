<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['key', 'value', 'label'];

    /**
     * 获取指定 key 的值
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * 批量获取费用设置
     */
    public static function getFees(): array
    {
        $rows = static::whereIn('key', ['category_fee', 'open_weight_fee'])->get();
        $result = [
            'category_fee'    => 360,
            'open_weight_fee' => 80,
        ];
        foreach ($rows as $row) {
            $result[$row->key] = (float) $row->value;
        }
        return $result;
    }
}
