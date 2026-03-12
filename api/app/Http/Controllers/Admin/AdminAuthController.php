<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    /**
     * 管理员登录
     * POST /api/admin/login
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $admin = Admin::where('email', $request->input('email'))->first();

        if (!$admin || !Hash::check($request->input('password'), $admin->password)) {
            return $this->error('邮箱或密码错误', 401);
        }

        if ($admin->status !== 1) {
            return $this->error('账号已被禁用', 403);
        }

        $token = JWTAuth::fromUser($admin);

        return $this->success([
            'token' => $token,
            'user'  => [
                'id'     => $admin->id,
                'name'   => $admin->name,
                'email'  => $admin->email,
                'role'   => $admin->role,
                'avatar' => $admin->avatar,
            ],
        ], '登录成功');
    }

    /**
     * 获取当前管理员信息
     * GET /api/admin/profile
     */
    public function profile()
    {
        $admin = Auth::guard('admin')->user();

        return $this->success([
            'id'         => $admin->id,
            'name'       => $admin->name,
            'email'      => $admin->email,
            'role'       => $admin->role,
            'avatar'     => $admin->avatar,
            'created_at' => $admin->created_at,
        ]);
    }

    /**
     * 退出登录
     * POST /api/admin/logout
     */
    public function logout()
    {
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Exception $e) {
            // ignore
        }
        return $this->success(null, '退出成功');
    }
}
