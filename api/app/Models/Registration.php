<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'registrations';

    protected $fillable = [
        'user_id', 'site_id', 'site_name',
        'order_no', 'name_pinyin', 'name_cn', 'nationality',
        'gender', 'id_card', 'id_type', 'passport_no', 'birthday', 'age_group', 'belt_color',
        'weight_gi', 'weight_nogi', 'gi_open', 'nogi_open',
        'team', 'phone', 'email',
        'package_key', 'package_label', 'amount',
        'pay_status', 'confirm_status',
        'wx_prepay_id', 'wx_transaction_id', 'paid_at',
    ];

    protected $casts = [
        'amount'    => 'float',
        'gi_open'   => 'boolean',
        'nogi_open' => 'boolean',
        'birthday'  => 'date:Y-m-d',
        'paid_at'   => 'datetime',
    ];

    /**
     * 生成 13 位订单号：YYMMDDHHmmss + 1位随机 = 13位
     * 若碰撞则重试（概率极低）
     */
    public static function generateOrderNo(): string
    {
        do {
            $no = date('ymdHis') . mt_rand(0, 9); // 12 + 1 = 13位
        } while (static::where('order_no', $no)->exists());

        return $no;
    }

    // 性别文字映射
    public function getGenderTextAttribute(): string
    {
        return $this->gender == 1 ? '男' : '女';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(DictItem::class, 'site_id');
    }
}
