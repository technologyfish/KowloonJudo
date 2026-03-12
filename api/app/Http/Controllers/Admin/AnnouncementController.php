<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * 公告列表
     * GET /api/admin/announcements
     */
    public function index(Request $request)
    {
        $query = Announcement::orderByDesc('sort')->orderByDesc('id');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $list = $query->paginate($request->input('per_page', 20));

        return $this->success($list);
    }

    /**
     * 公告详情
     * GET /api/admin/announcements/{id}
     */
    public function show($id)
    {
        $item = Announcement::findOrFail($id);
        return $this->success($item);
    }

    /**
     * 新建公告
     * POST /api/admin/announcements
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title'   => 'required|string|max:100',
            'content' => 'required|string',
            'status'  => 'sometimes|integer|in:0,1',
            'sort'    => 'sometimes|integer',
        ]);

        $item = Announcement::create([
            'title'   => $request->input('title'),
            'content' => $request->input('content'),
            'status'  => $request->input('status', 1),
            'sort'    => $request->input('sort', 0),
        ]);

        return $this->success($item, '创建成功');
    }

    /**
     * 更新公告
     * PUT /api/admin/announcements/{id}
     */
    public function update(Request $request, $id)
    {
        $item = Announcement::findOrFail($id);

        $this->validate($request, [
            'title'   => 'sometimes|string|max:100',
            'content' => 'sometimes|string',
            'status'  => 'sometimes|integer|in:0,1',
            'sort'    => 'sometimes|integer',
        ]);

        $item->update($request->only(['title', 'content', 'status', 'sort']));

        return $this->success($item, '更新成功');
    }

    /**
     * 删除公告
     * DELETE /api/admin/announcements/{id}
     */
    public function destroy($id)
    {
        $item = Announcement::findOrFail($id);
        $item->delete();
        return $this->success(null, '删除成功');
    }
}
