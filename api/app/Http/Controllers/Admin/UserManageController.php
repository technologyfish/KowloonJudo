<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    /**
     * 用户列表
     * GET /api/admin/users
     */
    public function index(Request $request)
    {
        $keyword  = $request->input('keyword');
        $pageSize = (int) $request->input('pageSize', 10);

        $query = User::query()->orderByDesc('created_at');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('nickname', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        $paginator = $query->paginate($pageSize);

        return $this->paginate($paginator);
    }

    /**
     * 用户详情
     * GET /api/admin/users/{id}
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->success($user);
    }

    /**
     * 更新用户
     * PUT /api/admin/users/{id}
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'nickname' => 'sometimes|string|max:50',
            'phone'    => 'sometimes|string|max:20',
            'status'   => 'sometimes|in:0,1',
            'gender'   => 'sometimes|integer|in:0,1,2',
            'birthday' => 'sometimes|nullable|date_format:Y-m-d',
        ]);

        $user->update($request->only(['nickname', 'phone', 'status', 'avatar', 'gender', 'birthday']));

        return $this->success($user, '更新成功');
    }

    /**
     * 删除用户
     * DELETE /api/admin/users/{id}
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $this->success(null, '删除成功');
    }
}
