<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class FeeSettingController extends Controller
{
    /**
     * 获取费用设置（管理端）
     * GET /api/admin/settings/fees
     */
    public function index()
    {
        return $this->success(Setting::getFees());
    }

    /**
     * 获取费用设置（公开，小程序前端用）
     * GET /api/settings/fees
     */
    public function publicIndex()
    {
        return $this->success(Setting::getFees());
    }

    /**
     * 更新费用设置
     * PUT /api/admin/settings/fees
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'category_fee'    => 'required|numeric|min:0',
            'open_weight_fee' => 'required|numeric|min:0',
        ]);

        Setting::updateOrCreate(
            ['key' => 'category_fee'],
            ['value' => $request->input('category_fee'), 'label' => '组别费用（元）']
        );

        Setting::updateOrCreate(
            ['key' => 'open_weight_fee'],
            ['value' => $request->input('open_weight_fee'), 'label' => '无差别组别费用（元）']
        );

        return $this->success(Setting::getFees(), '费用设置已更新');
    }
}
