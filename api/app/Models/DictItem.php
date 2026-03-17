<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 字典数据项
 *
 * @property int    $id
 * @property string $type_code 所属字典类型编码
 * @property string $label     显示标签
 * @property string $value     存储值
 * @property int    $sort      排序（越小越靠前）
 * @property int    $status    1=启用 0=禁用
 * @property string $remark    备注
 */
class DictItem extends Model
{
    protected $table = 'dict_items';

    protected $fillable = ['type_code', 'label', 'value', 'sort', 'status', 'remark'];

    protected $casts = [
        'sort'   => 'integer',
        'status' => 'integer',
    ];

    /**
     * 所属字典类型
     */
    public function dictType()
    {
        return $this->belongsTo(DictType::class, 'type_code', 'code');
    }

    /**
     * 获取某个字典类型下所有启用的字典项
     */
    public static function getActiveItems(string $typeCode)
    {
        return static::where('type_code', $typeCode)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'label', 'value']);
    }
}
