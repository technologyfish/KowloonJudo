<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * 获取当前用户信息
     * GET /api/user/info
     */
    public function info()
    {
        $user = JWTAuth::parseToken()->authenticate();

        return $this->success([
            'id'       => $user->id,
            'nickname' => $user->nickname,
            'avatar'   => $user->avatar,
            'phone'    => $user->phone,
            'gender'   => $user->gender,
            'birthday' => $user->birthday ? $user->birthday->format('Y-m-d') : null,
            'openid'   => $user->openid,
        ]);
    }

    /**
     * 更新当前用户信息
     * POST /api/user/info
     */
    public function update(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $this->validate($request, [
            'nickname' => 'sometimes|string|max:50',
            'avatar'   => 'sometimes|string|max:500',
            'phone'    => 'sometimes|string|max:20',
            'gender'   => 'sometimes|integer|in:0,1,2',
            'birthday' => 'sometimes|nullable|date|date_format:Y-m-d',
        ]);

        $user->update($request->only(['nickname', 'avatar', 'phone', 'gender', 'birthday']));

        return $this->success([
            'id'       => $user->id,
            'nickname' => $user->nickname,
            'avatar'   => $user->avatar,
            'phone'    => $user->phone,
            'gender'   => $user->gender,
            'birthday' => $user->birthday ? $user->birthday->format('Y-m-d') : null,
            'openid'   => $user->openid,
        ], '更新成功');
    }
}
