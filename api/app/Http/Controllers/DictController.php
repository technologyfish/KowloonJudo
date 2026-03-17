<?php

namespace App\Http\Controllers;

use App\Models\DictItem;
use Illuminate\Http\Request;

/**
 * 公开接口 - 字典数据查询（小程序下拉框等）
 */
class DictController extends Controller
{
    /**
     * 获取某字典类型下所有启用的数据项
     * GET /api/dict/items?type_code=competition_site
     */
    public function publicItems(Request $request)
    {
        $this->validate($request, [
            'type_code' => 'required|string|max:50',
        ]);

        $items = DictItem::getActiveItems($request->input('type_code'));

        return $this->success($items);
    }
}
