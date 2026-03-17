<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 字典类型
 *
 * @property int    $id
 * @property string $code   唯一编码，如 competition_site
 * @property string $name   显示名称，如 赛事站点
 * @property int    $status 1=启用 0=禁用
 * @property string $remark 备注
 */
class DictType extends Model
{
    protected $table = 'dict_types';

    protected $fillable = ['code', 'name', 'status', 'remark'];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * 该字典类型下的所有字典项
     */
    public function items()
    {
        return $this->hasMany(DictItem::class, 'type_code', 'code');
    }

    /**
     * 获取该字典类型下所有启用的字典项（按 sort 排序）
     */
    public function activeItems()
    {
        return $this->items()
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id');
    }
}
