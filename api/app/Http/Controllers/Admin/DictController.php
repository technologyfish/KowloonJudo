<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DictType;
use App\Models\DictItem;
use Illuminate\Http\Request;

/**
 * 管理端 - 字典管理（字典类型 + 字典数据）
 */
class DictController extends Controller
{
    // ─── 字典类型 ─────────────────────────────────────────

    /**
     * 字典类型列表
     * GET /api/admin/dict/types
     */
    public function typeIndex()
    {
        $types = DictType::withCount('items')
            ->orderBy('id')
            ->get();

        return $this->success($types);
    }

    /**
     * 创建字典类型
     * POST /api/admin/dict/types
     */
    public function typeStore(Request $request)
    {
        $this->validate($request, [
            'code'   => 'required|string|max:50|unique:dict_types,code',
            'name'   => 'required|string|max:100',
            'status' => 'sometimes|integer|in:0,1',
            'remark' => 'sometimes|nullable|string|max:255',
        ]);

        $type = DictType::create([
            'code'   => $request->input('code'),
            'name'   => $request->input('name'),
            'status' => $request->input('status', 1),
            'remark' => $request->input('remark', ''),
        ]);

        return $this->success($type, '字典类型创建成功');
    }

    /**
     * 更新字典类型
     * PUT /api/admin/dict/types/{id}
     */
    public function typeUpdate(Request $request, $id)
    {
        $type = DictType::findOrFail($id);

        $this->validate($request, [
            'code'   => "sometimes|string|max:50|unique:dict_types,code,{$id}",
            'name'   => 'sometimes|string|max:100',
            'status' => 'sometimes|integer|in:0,1',
            'remark' => 'sometimes|nullable|string|max:255',
        ]);

        // 如果修改了 code，需要同步更新 dict_items 的 type_code
        $oldCode = $type->code;
        $newCode = $request->input('code', $oldCode);

        $type->update($request->only(['code', 'name', 'status', 'remark']));

        if ($newCode !== $oldCode) {
            DictItem::where('type_code', $oldCode)->update(['type_code' => $newCode]);
        }

        return $this->success($type, '字典类型更新成功');
    }

    /**
     * 删除字典类型（同时删除其下所有字典项）
     * DELETE /api/admin/dict/types/{id}
     */
    public function typeDestroy($id)
    {
        $type = DictType::findOrFail($id);

        // 删除该类型下的所有字典项
        DictItem::where('type_code', $type->code)->delete();
        $type->delete();

        return $this->success(null, '字典类型及其数据已删除');
    }

    // ─── 字典数据项 ───────────────────────────────────────

    /**
     * 字典数据项列表（按 type_code 查询）
     * GET /api/admin/dict/items?type_code=xxx
     */
    public function itemIndex(Request $request)
    {
        $typeCode = $request->input('type_code');

        $query = DictItem::orderBy('sort')->orderBy('id');

        if ($typeCode) {
            $query->where('type_code', $typeCode);
        }

        return $this->success($query->get());
    }

    /**
     * 创建字典数据项
     * POST /api/admin/dict/items
     */
    public function itemStore(Request $request)
    {
        $this->validate($request, [
            'type_code' => 'required|string|max:50|exists:dict_types,code',
            'label'     => 'required|string|max:100',
            'value'     => 'required|string|max:100',
            'sort'      => 'sometimes|integer',
            'status'    => 'sometimes|integer|in:0,1',
            'remark'    => 'sometimes|nullable|string|max:255',
        ]);

        $item = DictItem::create([
            'type_code' => $request->input('type_code'),
            'label'     => $request->input('label'),
            'value'     => $request->input('value'),
            'sort'      => $request->input('sort', 0),
            'status'    => $request->input('status', 1),
            'remark'    => $request->input('remark', ''),
        ]);

        return $this->success($item, '字典数据创建成功');
    }

    /**
     * 更新字典数据项
     * PUT /api/admin/dict/items/{id}
     */
    public function itemUpdate(Request $request, $id)
    {
        $item = DictItem::findOrFail($id);

        $this->validate($request, [
            'label'  => 'sometimes|string|max:100',
            'value'  => 'sometimes|string|max:100',
            'sort'   => 'sometimes|integer',
            'status' => 'sometimes|integer|in:0,1',
            'remark' => 'sometimes|nullable|string|max:255',
        ]);

        $item->update($request->only(['label', 'value', 'sort', 'status', 'remark']));

        return $this->success($item, '字典数据更新成功');
    }

    /**
     * 删除字典数据项
     * DELETE /api/admin/dict/items/{id}
     */
    public function itemDestroy($id)
    {
        $item = DictItem::findOrFail($id);
        $item->delete();

        return $this->success(null, '字典数据已删除');
    }
}
